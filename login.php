<?php
require 'conexion.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];
    
    try {
        // Modificar la consulta para unir con la tabla roles
        $stmt = $conn->prepare("SELECT u.*, r.nombre as rol_nombre 
                               FROM usuarios u 
                               JOIN roles r ON u.rol_id = r.id 
                               WHERE u.email = :email AND u.activo = TRUE");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar la contraseña directamente (sin hash)
            if ($contraseña === $usuario['contraseña']) {
                // Actualizar último login
                $update = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
                $update->bindParam(':id', $usuario['id']);
                $update->execute();
                
                // Guardar datos en sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol_nombre'];  // Usar rol_nombre en lugar de rol
                
                echo "success";
            } else {
                echo "Contraseña incorrecta";
            }
        } else {
            echo "Usuario no encontrado o inactivo";
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>