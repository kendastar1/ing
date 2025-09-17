<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Acceso no autorizado');
}

require 'conexion.php';

try {
    $stmt = $conn->prepare("DELETE FROM reparaciones WHERE id = :id");
    $stmt->bindParam(':id', $_POST['id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reparación eliminada correctamente'
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar la reparación: ' . $e->getMessage()
    ]);
}
?>