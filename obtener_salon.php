<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $salon_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($salon_id) {
        try {
            $stmt = $conn->prepare("SELECT * FROM salones WHERE id = :id");
            $stmt->bindParam(':id', $salon_id);
            $stmt->execute();
            $salon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($salon) {
                header('Content-Type: application/json');
                echo json_encode($salon);
            } else {
                header("HTTP/1.1 404 Not Found");
            }
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