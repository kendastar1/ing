<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

try {
    $query = "SELECT 
                i.id,
                i.computador_id,
                i.usuario_reporte_id,
                i.asignado_nombre,
                i.titulo,
                i.descripcion,
                i.estado,
                i.prioridad,
                i.fecha_reporte,
                i.fecha_asignacion,
                i.fecha_resolucion,
                i.solucion,
                c.codigo_patrimonio AS computadora_codigo,
                u1.nombre AS reportador_nombre
              FROM incidencias i
              LEFT JOIN computadores c ON i.computador_id = c.id
              LEFT JOIN usuarios u1 ON i.usuario_reporte_id = u1.id
              ORDER BY i.fecha_reporte DESC";
    
    $stmt = $conn->query($query);
    $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($incidencias);
} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'error' => $e->getMessage(),
        'query' => $query
    ]);
}
?>