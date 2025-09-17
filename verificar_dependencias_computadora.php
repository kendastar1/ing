<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die(json_encode(['error' => 'No autorizado']));
}

if (empty($_GET['id'])) {
    die(json_encode(['error' => 'ID no proporcionado']));
}

$computadora_id = $_GET['id'];

try {
    // Contar reparaciones de esta computadora
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reparaciones WHERE computadora_id = :computadora_id");
    $stmt->bindParam(':computadora_id', $computadora_id);
    $stmt->execute();
    $reparaciones = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'reparaciones' => $reparaciones,
        'total_dependencias' => $reparaciones
    ]);
    
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
}
?>