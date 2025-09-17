<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

// Validación de autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Validación de método HTTP
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validación CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

// Campos requeridos
$required_fields = ['computador_id', 'titulo', 'descripcion', 'prioridad', 'estado'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
        exit;
    }
}

// Normalización y validación del estado
$estado = $_POST['estado'];
if (is_array($estado)) {
    echo json_encode(['success' => false, 'message' => 'Formato de estado inválido']);
    exit;
}

// Convertir a minúsculas y reemplazar espacios
$estado = mb_strtolower(trim($estado), 'UTF-8');
$estado = preg_replace('/\s+/', '_', $estado);

// Estados permitidos
$estados_permitidos = ['pendiente', 'asignado', 'en_proceso', 'resuelto'];
if (!in_array($estado, $estados_permitidos)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Estado no válido. Los estados permitidos son: pendiente, asignado, en_proceso, resuelto'
    ]);
    exit;
}

try {
    // Primero verificar si ya existe una incidencia activa para esta computadora
    $stmt_check = $conn->prepare("SELECT id FROM incidencias 
                                WHERE computador_id = :computador_id 
                                AND estado != 'resuelto'");
    $stmt_check->bindParam(':computador_id', $_POST['computador_id']);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe un registro activo para esta computadora'
        ]);
        exit;
    }

    // Manejo de fechas
    $fecha_actual = date('Y-m-d');
    $fecha_reporte = !empty($_POST['fecha_reporte']) ? date('Y-m-d', strtotime($_POST['fecha_reporte'])) : $fecha_actual;
    $fecha_asignacion = !empty($_POST['fecha_asignacion']) ? date('Y-m-d', strtotime($_POST['fecha_asignacion'])) : null;
    $fecha_resolucion = !empty($_POST['fecha_resolucion']) ? date('Y-m-d', strtotime($_POST['fecha_resolucion'])) : null;
    
    // Lógica automática para fechas
    if (!empty($_POST['asignado_nombre']) && empty($_POST['fecha_asignacion'])) {
        $fecha_asignacion = $fecha_actual;
    }
    
    if ($estado == 'resuelto' && empty($_POST['fecha_resolucion'])) {
        $fecha_resolucion = $fecha_actual;
    }
    
    // Preparar consulta SQL
    $stmt = $conn->prepare("INSERT INTO incidencias 
        (computador_id, usuario_reporte_id, asignado_nombre, titulo, descripcion, 
         estado, prioridad, solucion, fecha_reporte, fecha_asignacion, fecha_resolucion)
        VALUES 
        (:computador_id, :usuario_id, :asignado_nombre, :titulo, :descripcion, 
         :estado, :prioridad, :solucion, :fecha_reporte, 
         :fecha_asignacion, :fecha_resolucion)");
    
    // Asignar valores
    $asignado_nombre = !empty($_POST['asignado_nombre']) ? $_POST['asignado_nombre'] : null;
    $solucion = !empty($_POST['solucion']) ? $_POST['solucion'] : null;
    
    // Bind de parámetros
    $stmt->bindParam(':computador_id', $_POST['computador_id']);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->bindParam(':asignado_nombre', $asignado_nombre);
    $stmt->bindParam(':titulo', $_POST['titulo']);
    $stmt->bindParam(':descripcion', $_POST['descripcion']);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':prioridad', $_POST['prioridad']);
    $stmt->bindParam(':solucion', $solucion);
    $stmt->bindParam(':fecha_reporte', $fecha_reporte);
    $stmt->bindParam(':fecha_asignacion', $fecha_asignacion);
    $stmt->bindParam(':fecha_resolucion', $fecha_resolucion);
    
    // Ejecutar consulta
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Incidencia reportada correctamente',
            'estado_guardado' => $estado
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la incidencia']);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos',
        'error' => $e->getMessage()
    ]);
}
?>