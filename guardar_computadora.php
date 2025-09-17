<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

// 1. Validar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// 2. Validar que exista al menos una sede activa
try {
    $stmt = $conn->query("SELECT id FROM sedes WHERE activa = TRUE");
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sedes)) {
        echo json_encode([
            'success' => false,
            'error' => 'no_sedes',
            'message' => 'Debe crear al menos una sede antes de agregar computadoras',
            'action_required' => true
        ]);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al verificar sedes: ' . $e->getMessage()]);
    exit;
}

// 3. Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// 4. Validar CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// 5. Validar campos requeridos
$required_fields = ['salon_id', 'codigo_patrimonio', 'marca', 'modelo', 'ram_gb', 'almacenamiento_gb', 'tipo_almacenamiento'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode([
            'success' => false,
            'error' => 'missing_field',
            'message' => "El campo $field es requerido",
            'field' => $field
        ]);
        exit;
    }
}

// 6. Validar que el salón exista y pertenezca a una sede activa
$salon_id = intval($_POST['salon_id']);
try {
    $stmt = $conn->prepare("SELECT s.id, se.activa 
                           FROM salones s
                           JOIN sedes se ON s.sede_id = se.id
                           WHERE s.id = :salon_id");
    $stmt->bindParam(':salon_id', $salon_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $salon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$salon) {
        echo json_encode([
            'success' => false,
            'error' => 'invalid_salon',
            'message' => 'El salón seleccionado no existe',
            'field' => 'salon_id'
        ]);
        exit;
    }
    
    if (!$salon['activa']) {
        echo json_encode([
            'success' => false,
            'error' => 'inactive_sede',
            'message' => 'La sede a la que pertenece este salón no está activa',
            'field' => 'salon_id'
        ]);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al validar salón: ' . $e->getMessage()]);
    exit;
}

// 7. Validar formato del código patrimonial
$codigo_patrimonio = trim($_POST['codigo_patrimonio']);
if (!preg_match('/^[A-Za-z0-9\-]+$/', $codigo_patrimonio)) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_format',
        'message' => 'El código patrimonial solo puede contener letras, números y guiones',
        'field' => 'codigo_patrimonio'
    ]);
    exit;
}

// 8. Validar duplicado de código patrimonial
try {
    $stmt = $conn->prepare("SELECT id FROM computadores WHERE codigo_patrimonio = :codigo");
    $stmt->bindParam(':codigo', $codigo_patrimonio);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'duplicate',
            'message' => 'Ya existe una computadora con este código patrimonial',
            'field' => 'codigo_patrimonio'
        ]);
        exit;
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al validar duplicados: ' . $e->getMessage()]);
    exit;
}

// 9. Procesar y validar datos numéricos
$ram_gb = intval($_POST['ram_gb']);
$almacenamiento_gb = intval($_POST['almacenamiento_gb']);

if ($ram_gb <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_ram',
        'message' => 'La RAM debe ser mayor a 0',
        'field' => 'ram_gb'
    ]);
    exit;
}

if ($almacenamiento_gb <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_storage',
        'message' => 'El almacenamiento debe ser mayor a 0',
        'field' => 'almacenamiento_gb'
    ]);
    exit;
}

// 10. Procesar otros datos
$marca = trim($_POST['marca']);
$modelo = trim($_POST['modelo']);
$sistema_operativo = trim($_POST['sistema_operativo'] ?? '');
$tipo_almacenamiento = $_POST['tipo_almacenamiento'];
$estado = $_POST['estado'] ?? 'operativo';
$fecha_instalacion = !empty($_POST['fecha_instalacion']) ? $_POST['fecha_instalacion'] : null;
$ultimo_mantenimiento = !empty($_POST['ultimo_mantenimiento']) ? $_POST['ultimo_mantenimiento'] : null;
$observaciones = trim($_POST['observaciones'] ?? '');

// 11. Validar fechas si existen
if ($fecha_instalacion && !strtotime($fecha_instalacion)) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_date',
        'message' => 'Fecha de instalación no válida',
        'field' => 'fecha_instalacion'
    ]);
    exit;
}

if ($ultimo_mantenimiento && !strtotime($ultimo_mantenimiento)) {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_date',
        'message' => 'Fecha de último mantenimiento no válida',
        'field' => 'ultimo_mantenimiento'
    ]);
    exit;
}

// 12. Insertar en base de datos
try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("INSERT INTO computadores 
                          (salon_id, codigo_patrimonio, marca, modelo, sistema_operativo, 
                           ram_gb, almacenamiento_gb, tipo_almacenamiento, estado, 
                           fecha_instalacion, ultimo_mantenimiento, observaciones) 
                          VALUES 
                          (:salon_id, :codigo, :marca, :modelo, :so, 
                           :ram, :almacenamiento, :tipo_alm, :estado, 
                           :fecha_inst, :ult_mant, :obs)");
    
    $stmt->execute([
        ':salon_id' => $salon_id,
        ':codigo' => $codigo_patrimonio,
        ':marca' => $marca,
        ':modelo' => $modelo,
        ':so' => $sistema_operativo,
        ':ram' => $ram_gb,
        ':almacenamiento' => $almacenamiento_gb,
        ':tipo_alm' => $tipo_almacenamiento,
        ':estado' => $estado,
        ':fecha_inst' => $fecha_instalacion,
        ':ult_mant' => $ultimo_mantenimiento,
        ':obs' => $observaciones
    ]);
    
    $computadora_id = $conn->lastInsertId();
    
    // Actualizar contador en salones
    $stmt = $conn->prepare("UPDATE salones SET numero_computadores = numero_computadores + 1 WHERE id = :id");
    $stmt->execute([':id' => $salon_id]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Computadora registrada exitosamente',
        'computadora_id' => $computadora_id,
        'codigo_patrimonio' => $codigo_patrimonio,
        'salon_id' => $salon_id
    ]);
    
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Error en la base de datos',
        'details' => $e->getMessage()
    ]);
}
?>