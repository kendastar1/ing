<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Acceso no autorizado');
}

require 'conexion.php';

try {
    // Primero verificamos si ya existe otra reparación para esta computadora (excluyendo la actual)
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM reparaciones WHERE computadora_id = :computadora_id AND id != :id");
    $stmtCheck->bindParam(':computadora_id', $_POST['computadora_id']);
    $stmtCheck->bindParam(':id', $_POST['id']);
    $stmtCheck->execute();
    $count = $stmtCheck->fetchColumn();

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe otro registro de reparación para esta computadora'
        ]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE reparaciones SET
                          computadora_id = :computadora_id,
                          fecha_reparacion = :fecha_reparacion,
                          fecha_completada = :fecha_completada,
                          estado_reparacion = :estado_reparacion,
                          persona_reporto = :persona_reporto,
                          persona_realizo = :persona_realizo,
                          descripcion = :descripcion,
                          solucion = :solucion
                          WHERE id = :id");
    
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->bindParam(':computadora_id', $_POST['computadora_id']);
    $stmt->bindParam(':fecha_reparacion', $_POST['fecha_reparacion']);
    $stmt->bindParam(':fecha_completada', $_POST['fecha_completada']);
    
    // Establecer estado_reparacion por defecto si no se proporciona
    $estado_reparacion = isset($_POST['estado_reparacion']) ? $_POST['estado_reparacion'] : 'pendiente';
    $stmt->bindParam(':estado_reparacion', $estado_reparacion);
    
    $stmt->bindParam(':persona_reporto', $_POST['persona_reporto']);
    $stmt->bindParam(':persona_realizo', $_POST['persona_realizo']);
    $stmt->bindParam(':descripcion', $_POST['descripcion']);
    $stmt->bindParam(':solucion', $_POST['solucion']);
    
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reparación actualizada correctamente'
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar la reparación: ' . $e->getMessage()
    ]);
}
?>