<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incidencia_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($incidencia_id) {
        try {
            // Eliminar la incidencia
            $stmt = $conn->prepare("DELETE FROM incidencias WHERE id = :id");
            $stmt->bindParam(':id', $incidencia_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Incidencia eliminada correctamente']);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la incidencia: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}
?>