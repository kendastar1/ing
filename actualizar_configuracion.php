<?php
session_start();

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'message' => 'Token de seguridad inválido']));
}

// Verificar que el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    die(json_encode(['success' => false, 'message' => 'Debe iniciar sesión']));
}

require 'conexion.php';

$usuario_id = $_POST['usuario_id'];
$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$rol = $_POST['rol'];
$password_actual = $_POST['password_actual'] ?? '';
$nueva_password = $_POST['nueva_password'] ?? '';
$confirmar_password = $_POST['confirmar_password'] ?? '';

// Validaciones básicas
if (empty($nombre) || empty($email) || empty($rol)) {
    die(json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']));
}

// Verificar si el email ya existe para otro usuario
try {
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :usuario_id");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'El email ya está en uso por otro usuario']));
    }
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Error al verificar el email: ' . $e->getMessage()]));
}

// Si se quiere cambiar la contraseña, validar
if (!empty($nueva_password)) {
    if (empty($password_actual)) {
        die(json_encode(['success' => false, 'message' => 'Debe ingresar la contraseña actual para cambiarla']));
    }
    
    if ($nueva_password !== $confirmar_password) {
        die(json_encode(['success' => false, 'message' => 'Las contraseñas nuevas no coinciden']));
    }
    
    // Verificar contraseña actual
    try {
        $stmt = $conn->prepare("SELECT contraseña FROM usuarios WHERE id = :usuario_id");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || $usuario['contraseña'] !== $password_actual) {
            die(json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']));
        }
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Error al verificar la contraseña: ' . $e->getMessage()]));
    }
    
    // Actualizar con nueva contraseña
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol, contraseña = :password WHERE id = :usuario_id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':password', $nueva_password);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        // Actualizar nombre en sesión si es el usuario actual
        if ($_SESSION['usuario_id'] == $usuario_id) {
            $_SESSION['nombre'] = $nombre;
        }
        
        echo json_encode(['success' => true, 'message' => 'Configuración actualizada correctamente']);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]));
    }
} else {
    // Actualizar sin cambiar contraseña
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol WHERE id = :usuario_id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        // Actualizar nombre en sesión si es el usuario actual
        if ($_SESSION['usuario_id'] == $usuario_id) {
            $_SESSION['nombre'] = $nombre;
        }
        
        echo json_encode(['success' => true, 'message' => 'Configuración actualizada correctamente']);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]));
    }
}
?>