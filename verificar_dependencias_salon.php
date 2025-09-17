<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die(json_encode(['error' => 'No autorizado']));
}

if (empty($_GET['id'])) {
    die(json_encode(['error' => 'ID no proporcionado']));
}

$salon_id = $_GET['id'];

try {
    // Contar computadoras en este salón
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM computadores WHERE salon_id = :salon_id");
    $stmt->bindParam(':salon_id', $salon_id);
    $stmt->execute();
    $computadoras = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Contar reparaciones de las computadoras en este salón
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reparaciones WHERE computadora_id IN (SELECT id FROM computadores WHERE salon_id = :salon_id)");
    $stmt->bindParam(':salon_id', $salon_id);
    $stmt->execute();
    $reparaciones = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'computadoras' => $computadoras,
        'reparaciones' => $reparaciones,
        'total_dependencias' => $computadoras + $reparaciones
    ]);
    
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
}
?>