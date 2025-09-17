<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

// 1. Validar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// 2. Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// 3. Validar CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// 4. Validar ID de salón
$salon_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$salon_id) {
    echo json_encode(['success' => false, 'error' => 'ID de salón inválido']);
    exit;
}

// 5. Validar campos requeridos
$required_fields = ['codigo_salon', 'piso', 'capacidad'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => "El campo $field es requerido"]);
        exit;
    }
}

// 6. Validar formato del código de salón
$codigo_salon = trim($_POST['codigo_salon']);
if (!preg_match('/^[A-Za-z0-9\-]+$/', $codigo_salon)) {
    echo json_encode(['success' => false, 'error' => 'El código de salón solo puede contener letras, números y guiones']);
    exit;
}

// 7. Validar duplicado de código de salón en la misma sede (excepto el actual)
try {
    // Primero obtenemos la sede_id del salón que estamos editando
    $stmt = $conn->prepare("SELECT sede_id FROM salones WHERE id = :id");
    $stmt->bindParam(':id', $salon_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_salon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_salon) {
        echo json_encode(['success' => false, 'error' => 'El salón no existe']);
        exit;
    }

    $sede_id = $current_salon['sede_id'];
    
    // Verificamos si el código ya existe en otra sala de la misma sede
    $stmt = $conn->prepare("SELECT id FROM salones 
                          WHERE codigo_salon = :codigo 
                          AND sede_id = :sede_id
                          AND id != :salon_id");
    $stmt->bindParam(':codigo', $codigo_salon);
    $stmt->bindParam(':sede_id', $sede_id, PDO::PARAM_INT);
    $stmt->bindParam(':salon_id', $salon_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate',
            'message' => 'Ya existe un salón con este código en la sede',
            'field' => 'codigo_salon'
        ]);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al validar duplicados: ' . $e->getMessage()]);
    exit;
}

// 8. Validar valores numéricos
$piso = intval($_POST['piso']);
$capacidad = intval($_POST['capacidad']);
$numero_computadores = intval($_POST['numero_computadores'] ?? 0);

if ($piso <= 0) {
    echo json_encode(['success' => false, 'error' => 'El piso debe ser un número positivo']);
    exit;
}

if ($capacidad <= 0) {
    echo json_encode(['success' => false, 'error' => 'La capacidad debe ser un número positivo']);
    exit;
}

if ($numero_computadores < 0) {
    echo json_encode(['success' => false, 'error' => 'El número de computadoras no puede ser negativo']);
    exit;
}

// 9. Actualizar en base de datos
try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("UPDATE salones SET 
                          codigo_salon = :codigo,
                          piso = :piso,
                          capacidad = :capacidad,
                          numero_computadores = :num_computadoras,
                          descripcion = :descripcion
                          WHERE id = :id");
    
    $stmt->execute([
        ':codigo' => $codigo_salon,
        ':piso' => $piso,
        ':capacidad' => $capacidad,
        ':num_computadoras' => $numero_computadores,
        ':descripcion' => trim($_POST['descripcion'] ?? ''),
        ':id' => $salon_id
    ]);
    
    $rows_affected = $stmt->rowCount();
    $conn->commit();
    
    if ($rows_affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Salón actualizado exitosamente',
            'changes' => $rows_affected,
            'codigo_salon' => $codigo_salon
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se realizaron cambios'
        ]);
    }
    
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos',
        'details' => $e->getMessage()
    ]);
}
?>