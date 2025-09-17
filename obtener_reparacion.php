<?php
require 'conexion.php';

if (!isset($_GET['id'])) {
    die('ID no proporcionado');
}

try {
    $stmt = $conn->prepare("SELECT r.*, c.codigo_patrimonio 
                           FROM reparaciones r
                           JOIN computadores c ON r.computadora_id = c.id
                           WHERE r.id = :id");
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    $reparacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparacion) {
        die('Reparación no encontrada');
    }
    
    header('Content-Type: application/json');
    echo json_encode($reparacion);
} catch(PDOException $e) {
    die('Error al obtener la reparación: ' . $e->getMessage());
}
?>