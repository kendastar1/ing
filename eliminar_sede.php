<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    die(json_encode(['success' => false, 'message' => 'No autorizado']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

if (!isset($_POST['id']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'message' => 'Datos inválidos']));
}

$sede_id = $_POST['id'];

try {
    $conn->beginTransaction();
    
    // 1. Obtener todos los salones de esta sede
    $stmt = $conn->prepare("SELECT id FROM salones WHERE sede_id = :sede_id");
    $stmt->bindParam(':sede_id', $sede_id);
    $stmt->execute();
    $salones = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($salones)) {
        // 2. Obtener todas las computadoras en estos salones
        $placeholders = implode(',', array_fill(0, count($salones), '?'));
        $stmt = $conn->prepare("SELECT id FROM computadores WHERE salon_id IN ($placeholders)");
        $stmt->execute($salones);
        $computadoras = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($computadoras)) {
            // 3. Eliminar las reparaciones asociadas a estas computadoras
            $placeholders = implode(',', array_fill(0, count($computadoras), '?'));
            $stmt = $conn->prepare("DELETE FROM reparaciones WHERE computadora_id IN ($placeholders)");
            $stmt->execute($computadoras);
            
            // 4. Eliminar las computadoras
            $stmt = $conn->prepare("DELETE FROM computadores WHERE id IN ($placeholders)");
            $stmt->execute($computadoras);
        }
        
        // 5. Eliminar los salones
        $placeholders = implode(',', array_fill(0, count($salones), '?'));
        $stmt = $conn->prepare("DELETE FROM salones WHERE id IN ($placeholders)");
        $stmt->execute($salones);
    }
    
    // 6. Finalmente eliminar la sede
    $stmt = $conn->prepare("DELETE FROM sedes WHERE id = :sede_id");
    $stmt->bindParam(':sede_id', $sede_id);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Sede y todos sus datos asociados eliminados correctamente']);
    
} catch (PDOException $e) {
    $conn->rollBack();
    die(json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]));
}
?>