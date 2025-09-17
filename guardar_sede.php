<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Operación no permitida');
}

require 'conexion.php';

try {
    $stmt = $conn->prepare("INSERT INTO sedes (nombre, direccion, telefono, responsable, activa) 
                           VALUES (:nombre, :direccion, :telefono, :responsable, 1)");
    
    $stmt->bindParam(':nombre', $_POST['nombre_sede']);
    $stmt->bindParam(':direccion', $_POST['direccion_sede']);
    $stmt->bindParam(':telefono', $_POST['telefono_sede']);
    $stmt->bindParam(':responsable', $_POST['responsable_sede']);
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Sede agregada correctamente']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la sede: ' . $e->getMessage()]);
}
?>