<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $computadora_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($computadora_id) {
        try {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // 1. Obtener información de la computadora antes de eliminar
            $stmt = $conn->prepare("SELECT imagen, salon_id FROM computadores WHERE id = :id");
            $stmt->bindParam(':id', $computadora_id);
            $stmt->execute();
            $computadora = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 2. Eliminar las reparaciones asociadas a esta computadora
            $stmt = $conn->prepare("DELETE FROM reparaciones WHERE computadora_id = :id");
            $stmt->bindParam(':id', $computadora_id);
            $stmt->execute();
            
            // 3. Eliminar la computadora
            $stmt = $conn->prepare("DELETE FROM computadores WHERE id = :id");
            $stmt->bindParam(':id', $computadora_id);
            $stmt->execute();
            
            // 4. Eliminar la imagen si existe
            if (!empty($computadora['imagen'])) {
                $ruta_imagen = 'uploads/computadoras/' . $computadora['imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
            
            // 5. Actualizar contador en salones
            if (!empty($computadora['salon_id'])) {
                $stmt = $conn->prepare("UPDATE salones SET numero_computadores = numero_computadores - 1 WHERE id = :id");
                $stmt->bindParam(':id', $computadora['salon_id']);
                $stmt->execute();
            }
            
            // Confirmar transacción
            $conn->commit();
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            // Revertir transacción en caso de error
            $conn->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}
?>