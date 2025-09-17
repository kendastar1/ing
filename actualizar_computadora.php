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

// 4. Validar ID de computadora
$computadora_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$computadora_id) {
    echo json_encode(['success' => false, 'error' => 'ID de computadora inválido']);
    exit;
}

// 5. Validar campos requeridos
$required_fields = ['codigo_patrimonio', 'marca', 'modelo', 'ram_gb', 'almacenamiento_gb', 'tipo_almacenamiento'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => "El campo $field es requerido"]);
        exit;
    }
}

// 6. Validar duplicado de código patrimonial (excepto para el registro actual)
try {
    $stmt = $conn->prepare("SELECT id FROM computadores 
                          WHERE codigo_patrimonio = :codigo 
                          AND id != :id");
    $stmt->bindParam(':codigo', $_POST['codigo_patrimonio']);
    $stmt->bindParam(':id', $computadora_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate',
            'message' => 'Esta computadora ya existe',
            'field' => 'codigo_patrimonio'
        ]);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al validar duplicados: ' . $e->getMessage()]);
    exit;
}

// 7. Validar valores numéricos
if ($_POST['ram_gb'] <= 0 || $_POST['almacenamiento_gb'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'RAM y almacenamiento deben ser mayores a 0']);
    exit;
}

// 8. Validar estado
$estados_permitidos = ['operativo', 'mantenimiento', 'dañado'];
$estado = in_array($_POST['estado'], $estados_permitidos) ? $_POST['estado'] : 'operativo';

// 9. Procesar fechas
$fecha_instalacion = !empty($_POST['fecha_instalacion']) ? 
    date('Y-m-d', strtotime($_POST['fecha_instalacion'])) : null;
$ultimo_mantenimiento = !empty($_POST['ultimo_mantenimiento']) ? 
    date('Y-m-d', strtotime($_POST['ultimo_mantenimiento'])) : null;

// 10. Actualizar en base de datos
try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("UPDATE computadores SET 
                          codigo_patrimonio = :codigo,
                          marca = :marca,
                          modelo = :modelo,
                          sistema_operativo = :so,
                          ram_gb = :ram,
                          almacenamiento_gb = :almacenamiento,
                          tipo_almacenamiento = :tipo_alm,
                          estado = :estado,
                          fecha_instalacion = :fecha_inst,
                          ultimo_mantenimiento = :ult_mant,
                          observaciones = :obs
                          WHERE id = :id");
    
    $stmt->execute([
        ':codigo' => trim($_POST['codigo_patrimonio']),
        ':marca' => trim($_POST['marca']),
        ':modelo' => trim($_POST['modelo']),
        ':so' => trim($_POST['sistema_operativo'] ?? ''),
        ':ram' => intval($_POST['ram_gb']),
        ':almacenamiento' => intval($_POST['almacenamiento_gb']),
        ':tipo_alm' => $_POST['tipo_almacenamiento'],
        ':estado' => $estado,
        ':fecha_inst' => $fecha_instalacion,
        ':ult_mant' => $ultimo_mantenimiento,
        ':obs' => trim($_POST['observaciones'] ?? ''),
        ':id' => $computadora_id
    ]);
    
    $rows_affected = $stmt->rowCount();
    $conn->commit();
    
    if ($rows_affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Computadora actualizada exitosamente',
            'changes' => $rows_affected
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No se realizaron cambios o la computadora no existe'
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