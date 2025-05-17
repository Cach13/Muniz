<?php
require_once 'config.php';
require_once 'session.php';

// Verificar si hay una sesión activa
header('Content-Type: application/json');

if (estaAutenticado()) {
    $usuario_id = $_SESSION['usuario_id'];
    
    try {
        // Obtener información del usuario
        $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            echo json_encode([
                'autenticado' => true,
                'usuario' => [
                    'id' => $usuario_id,
                    'nombre' => $usuario['nombre'],
                    'rol' => $usuario['rol']
                ]
            ]);
        } else {
            // Sesión existe pero usuario no encontrado en DB
            cerrarSesion();
            echo json_encode(['autenticado' => false, 'error' => 'Usuario no encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['autenticado' => false, 'error' => 'Error de base de datos']);
    }
} else {
    echo json_encode(['autenticado' => false]);
}
?>