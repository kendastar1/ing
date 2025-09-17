<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

try {
    // Obtener conteo por estado
    $stmt = $conn->query("SELECT estado, COUNT(*) as cantidad FROM incidencias GROUP BY estado");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $estadisticas = [
        'resueltas' => 0,
        'pendientes' => 0,
        'en_proceso' => 0,
        'asignadas' => 0
    ];
    
    foreach ($estados as $estado) {
        switch ($estado['estado']) {
            case 'resuelto':
                $estadisticas['resueltas'] = $estado['cantidad'];
                break;
            case 'pendiente':
                $estadisticas['pendientes'] = $estado['cantidad'];
                break;
            case 'en_proceso':
                $estadisticas['en_proceso'] = $estado['cantidad'];
                break;
            case 'asignado':
                $estadisticas['asignadas'] = $estado['cantidad'];
                break;
        }
    }
    
    // Obtener tendencia de últimos 7 días
    $fechaInicio = date('Y-m-d', strtotime('-6 days'));
    $stmt = $conn->prepare("
        SELECT DATE(fecha_reporte) as fecha, COUNT(*) as cantidad 
        FROM incidencias 
        WHERE fecha_reporte >= :fecha_inicio 
        GROUP BY DATE(fecha_reporte) 
        ORDER BY fecha
    ");
    $stmt->bindParam(':fecha_inicio', $fechaInicio);
    $stmt->execute();
    $tendencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        ...$estadisticas,
        'tendencia' => $tendencia
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
?>