<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sede_id'])) {
    $sede_id = filter_input(INPUT_GET, 'sede_id', FILTER_VALIDATE_INT);
    
    if ($sede_id) {
        try {
            $stmt = $conn->prepare("SELECT id, codigo_salon FROM salones WHERE sede_id = :sede_id");
            $stmt->bindParam(':sede_id', $sede_id);
            $stmt->execute();
            $salones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($salones);
        } catch(PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        header("HTTP/1.1 400 Bad Request");
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
}
?>