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

// 4. Validar sede
if (empty($_POST['sede_id']) || !is_numeric($_POST['sede_id']) || intval($_POST['sede_id']) <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Debe seleccionar una sede antes de registrar un salón.',
        'field' => 'sede_id'
    ]);
    exit;
}

// 5. Validar código de salón
if (empty($_POST['codigo_salon'])) {
    echo json_encode([
        'success' => false,
        'error' => 'El campo código de salón es requerido.',
        'field' => 'codigo_salon'
    ]);
    exit;
}

// 6. Validar formato del código de salón
$codigo_salon = trim($_POST['codigo_salon']);
if (!preg_match('/^[A-Za-z0-9\-]+$/', $codigo_salon)) {
    echo json_encode(['success' => false, 'error' => 'El código de salón solo puede contener letras, números y guiones']);
    exit;
}

// 7. Validar duplicado de código de salón en la misma sede
try {
    $stmt = $conn->prepare("SELECT id FROM salones WHERE codigo_salon = :codigo AND sede_id = :sede_id");
    $stmt->bindParam(':codigo', $codigo_salon);
    $stmt->bindParam(':sede_id', $_POST['sede_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate',
            'message' => 'Ya existe un salón con este código en la sede seleccionada',
            'field' => 'codigo_salon'
        ]);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al validar duplicados: ' . $e->getMessage()]);
    exit;
}

// 8. Procesar datos
$sede_id = intval($_POST['sede_id']);
$piso = !empty($_POST['piso']) ? intval($_POST['piso']) : null;
$capacidad = !empty($_POST['capacidad']) ? intval($_POST['capacidad']) : null;
$numero_computadores = !empty($_POST['numero_computadores']) ? intval($_POST['numero_computadores']) : 0;
$descripcion = trim($_POST['descripcion'] ?? '');

// 9. Validar valores numéricos
if ($piso !== null && $piso <= 0) {
    echo json_encode(['success' => false, 'error' => 'El piso debe ser un número positivo']);
    exit;
}

if ($capacidad !== null && $capacidad <= 0) {
    echo json_encode(['success' => false, 'error' => 'La capacidad debe ser un número positivo']);
    exit;
}

if ($numero_computadores < 0) {
    echo json_encode(['success' => false, 'error' => 'El número de computadoras no puede ser negativo']);
    exit;
}

// 10. Insertar en base de datos
try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("INSERT INTO salones 
                          (sede_id, codigo_salon, piso, capacidad, numero_computadores, descripcion) 
                          VALUES 
                          (:sede_id, :codigo_salon, :piso, :capacidad, :num_computadoras, :descripcion)");
    
    $stmt->execute([
        ':sede_id' => $sede_id,
        ':codigo_salon' => $codigo_salon,
        ':piso' => $piso,
        ':capacidad' => $capacidad,
        ':num_computadoras' => $numero_computadores,
        ':descripcion' => $descripcion
    ]);
    
    $salon_id = $conn->lastInsertId();

    // ⚠️ ESTA PARTE FUE ELIMINADA PORQUE LA COLUMNA NO EXISTE
    // $stmt = $conn->prepare("UPDATE sedes SET total_salones = total_salones + 1 WHERE id = :id");
    // $stmt->execute([':id' => $sede_id]);

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'id' => $salon_id,
        'message' => 'Salón creado exitosamente',
        'codigo_salon' => $codigo_salon
    ]);
    
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos',
        'details' => $e->getMessage()
    ]);
}
?>
