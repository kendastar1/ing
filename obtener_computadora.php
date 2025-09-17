<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $computadora_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($computadora_id) {
        try {
            $stmt = $conn->prepare("SELECT * FROM computadores WHERE id = :id");
            $stmt->bindParam(':id', $computadora_id);
            $stmt->execute();
            $computadora = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($computadora) {
                // Formatear fechas para el input date
                if ($computadora['fecha_instalacion']) {
                    $computadora['fecha_instalacion'] = date('Y-m-d', strtotime($computadora['fecha_instalacion']));
                }
                if ($computadora['ultimo_mantenimiento']) {
                    $computadora['ultimo_mantenimiento'] = date('Y-m-d', strtotime($computadora['ultimo_mantenimiento']));
                }
                
                header('Content-Type: application/json');
                echo json_encode($computadora);
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