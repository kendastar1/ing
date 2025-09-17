<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Acceso no autorizado');
}

require 'conexion.php';

try {
    // Primero verificamos si ya existe una reparación para esta computadora
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM reparaciones WHERE computadora_id = :computadora_id");
    $stmtCheck->bindParam(':computadora_id', $_POST['computadora_id']);
    $stmtCheck->execute();
    $count = $stmtCheck->fetchColumn();

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe un registro de reparación para esta computadora'
        ]);
        exit;
    }

    // Establecer estado_reparacion por defecto si no se proporciona
    $estado_reparacion = isset($_POST['estado_reparacion']) ? $_POST['estado_reparacion'] : 'pendiente';

    $stmt = $conn->prepare("INSERT INTO reparaciones 
                          (computadora_id, fecha_reparacion, fecha_completada, estado_reparacion, persona_reporto, persona_realizo, descripcion, solucion)
                          VALUES (:computadora_id, :fecha_reparacion, :fecha_completada, :estado_reparacion, :persona_reporto, :persona_realizo, :descripcion, :solucion)");
    
    $stmt->bindParam(':computadora_id', $_POST['computadora_id']);
    $stmt->bindParam(':fecha_reparacion', $_POST['fecha_reparacion']);
    $stmt->bindParam(':fecha_completada', $_POST['fecha_completada']);
    $stmt->bindParam(':estado_reparacion', $estado_reparacion);
    $stmt->bindParam(':persona_reporto', $_POST['persona_reporto']);
    $stmt->bindParam(':persona_realizo', $_POST['persona_realizo']);
    $stmt->bindParam(':descripcion', $_POST['descripcion']);
    $stmt->bindParam(':solucion', $_POST['solucion']);
    
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reparación registrada correctamente'
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar la reparación: ' . $e->getMessage()
    ]);
}
?>