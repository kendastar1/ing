<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

try {
    // Contar reparaciones por estado
    $query = "SELECT 
        estado_reparacion,
        COUNT(*) as cantidad 
    FROM reparaciones 
    GROUP BY estado_reparacion";
    
    $stmt = $conn->query($query);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inicializar contadores
    $pendientes = 0;
    $en_proceso = 0;
    $completadas = 0;
    
    // Procesar resultados
    foreach ($resultados as $fila) {
        switch ($fila['estado_reparacion']) {
            case 'pendiente':
                $pendientes = $fila['cantidad'];
                break;
            case 'en_proceso':
                $en_proceso = $fila['cantidad'];
                break;
            case 'completada':
                $completadas = $fila['cantidad'];
                break;
        }
    }
    
    // Devolver datos en formato JSON
    echo json_encode([
        'pendientes' => $pendientes,
        'en_proceso' => $en_proceso,
        'completadas' => $completadas,
        'total' => $pendientes + $en_proceso + $completadas
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Error al obtener estadísticas: ' . $e->getMessage(),
        'pendientes' => 0,
        'en_proceso' => 0,
        'completadas' => 0
    ]);
}
?>