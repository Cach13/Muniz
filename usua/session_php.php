<?php
// Iniciar sesión
session_start();

// Verificar si hay una sesión activa
function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

// Redirigir si no está autenticado
function verificarSesion() {
    if(!estaAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

// Verificar si el usuario es administrador
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// Verificar permiso de administrador
function verificarAdmin() {
    verificarSesion();
    if(!esAdmin()) {
        header('Location: p_usuario.html');
        exit;
    }
}

// Cerrar sesión
function cerrarSesion() {
    // Eliminar todas las variables de sesión
    $_SESSION = array();
    
    // Si está habilitada, borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
}
?>