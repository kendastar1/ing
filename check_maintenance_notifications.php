<?php
session_start();
require 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

// Calcular la fecha de hace 6 meses
$sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

// Buscar computadoras instaladas hace exactamente 6 meses
$stmt = $conn->prepare("
    SELECT c.id, c.codigo_patrimonio, c.marca, c.modelo, c.fecha_instalacion, 
           s.codigo_salon, sed.nombre as sede_nombre
    FROM computadores c
    JOIN salones s ON c.salon_id = s.id
    JOIN sedes sed ON s.sede_id = sed.id
    WHERE DATE(c.fecha_instalacion) = :six_months_ago
    AND c.estado != 'dañado'
");
$stmt->bindParam(':six_months_ago', $sixMonthsAgo);
$stmt->execute();
$computers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];
foreach ($computers as $computer) {
    $timeAgo = "6 meses"; // Podría calcularse de forma más precisa si se desea
    
    $notifications[] = [
        'id' => $computer['id'],
        'message' => "La computadora {$computer['codigo_patrimonio']} ({$computer['marca']} {$computer['modelo']}) 
                      en {$computer['sede_nombre']}, salón {$computer['codigo_salon']} 
                      requiere mantenimiento preventivo. Fue instalada el " . 
                      date('d/m/Y', strtotime($computer['fecha_instalacion'])),
        'time_ago' => $timeAgo
    ];
}

// También verificar computadoras con más de 6 meses sin mantenimiento
$stmt = $conn->prepare("
    SELECT c.id, c.codigo_patrimonio, c.marca, c.modelo, c.ultimo_mantenimiento,
           s.codigo_salon, sed.nombre as sede_nombre
    FROM computadores c
    JOIN salones s ON c.salon_id = s.id
    JOIN sedes sed ON s.sede_id = sed.id
    WHERE (c.ultimo_mantenimiento IS NULL OR c.ultimo_mantenimiento <= :six_months_ago)
    AND c.fecha_instalacion <= :six_months_ago
    AND c.estado != 'dañado'
");
$stmt->bindParam(':six_months_ago', $sixMonthsAgo);
$stmt->execute();
$overdueComputers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($overdueComputers as $computer) {
    $lastMaintenance = $computer['ultimo_mantenimiento'] ? 
        date('d/m/Y', strtotime($computer['ultimo_mantenimiento'])) : 'nunca';
    
    $notifications[] = [
        'id' => $computer['id'],
        'message' => "La computadora {$computer['codigo_patrimonio']} ({$computer['marca']} {$computer['modelo']}) 
                      en {$computer['sede_nombre']}, salón {$computer['codigo_salon']} 
                      tiene mantenimiento atrasado. Último mantenimiento: $lastMaintenance",
        'time_ago' => "6+ meses"
    ];
}

echo json_encode([
    'count' => count($notifications),
    'notifications' => $notifications
]);
?>