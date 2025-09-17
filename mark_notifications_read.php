<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// En un sistema real, aquí marcaríamos las notificaciones como leídas en la base de datos
// Por ahora, simplemente devolvemos éxito

echo json_encode(['success' => true]);
?>