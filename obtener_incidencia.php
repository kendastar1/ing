<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $incidencia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($incidencia_id) {
        try {
            $query = "SELECT i.*, 
                             c.codigo_patrimonio AS computadora_codigo,
                             s.nombre AS sede_nombre,
                             s.id AS sede_id,
                             sl.codigo_salon AS salon_codigo,
                             sl.id AS salon_id,
                             u.nombre AS reportador_nombre
                      FROM incidencias i
                      LEFT JOIN computadores c ON i.computador_id = c.id
                      LEFT JOIN salones sl ON c.salon_id = sl.id
                      LEFT JOIN sedes s ON sl.sede_id = s.id
                      LEFT JOIN usuarios u ON i.usuario_reporte_id = u.id
                      WHERE i.id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $incidencia_id);
            $stmt->execute();
            $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($incidencia) {
                echo json_encode($incidencia);
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