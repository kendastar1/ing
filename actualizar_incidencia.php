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

// Validación de ID
if (empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de incidencia no proporcionado']);
    exit;
}

// Campos requeridos
$required_fields = ['titulo', 'descripcion', 'prioridad', 'estado'];
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
    // Manejo de fechas
    $fecha_asignacion = !empty($_POST['fecha_asignacion']) ? date('Y-m-d', strtotime($_POST['fecha_asignacion'])) : null;
    $fecha_resolucion = !empty($_POST['fecha_resolucion']) ? date('Y-m-d', strtotime($_POST['fecha_resolucion'])) : null;
    
    // Valores opcionales
    $asignado_nombre = !empty($_POST['asignado_nombre']) ? $_POST['asignado_nombre'] : null;
    $solucion = !empty($_POST['solucion']) ? $_POST['solucion'] : null;
    
    // Preparar consulta SQL
    $stmt = $conn->prepare("UPDATE incidencias SET
        titulo = :titulo,
        descripcion = :descripcion,
        estado = :estado,
        prioridad = :prioridad,
        asignado_nombre = :asignado_nombre,
        solucion = :solucion,
        fecha_asignacion = CASE 
            WHEN :asignado_nombre IS NOT NULL AND asignado_nombre IS NULL THEN COALESCE(:fecha_asignacion_manual, CURDATE())
            WHEN :fecha_asignacion_manual IS NOT NULL THEN :fecha_asignacion_manual
            ELSE fecha_asignacion
        END,
        fecha_resolucion = CASE 
            WHEN :estado = 'resuelto' AND estado != 'resuelto' THEN COALESCE(:fecha_resolucion_manual, CURDATE())
            WHEN :fecha_resolucion_manual IS NOT NULL THEN :fecha_resolucion_manual
            ELSE fecha_resolucion
        END
        WHERE id = :id");
    
    // Bind de parámetros
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->bindParam(':titulo', $_POST['titulo']);
    $stmt->bindParam(':descripcion', $_POST['descripcion']);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':prioridad', $_POST['prioridad']);
    $stmt->bindParam(':asignado_nombre', $asignado_nombre);
    $stmt->bindParam(':solucion', $solucion);
    $stmt->bindParam(':fecha_asignacion_manual', $fecha_asignacion);
    $stmt->bindParam(':fecha_resolucion_manual', $fecha_resolucion);
    
    // Ejecutar consulta
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Incidencia actualizada correctamente',
            'estado_actualizado' => $estado
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la incidencia']);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos',
        'error' => $e->getMessage()
    ]);
}
?>