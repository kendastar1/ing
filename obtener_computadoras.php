<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['salon_id'])) {
    $salon_id = filter_input(INPUT_GET, 'salon_id', FILTER_VALIDATE_INT);
    
    if ($salon_id) {
        try {
            $stmt = $conn->prepare("SELECT id, codigo_patrimonio, marca, modelo FROM computadores WHERE salon_id = :salon_id");
            $stmt->bindParam(':salon_id', $salon_id);
            $stmt->execute();
            $computadoras = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($computadoras);
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
