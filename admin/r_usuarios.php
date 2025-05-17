<?php
// funciona completamente

require_once '..\config.php';
session_start();

// Verificar si hay sesión de administrador
if (!isset($_SESSION['user_id'])) {
    header("Location: ..\login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, contraseña, rol) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $password, $rol]);
    
    header("Location: p_admin.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuarios</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <h2>Registrar Usuario</h2>
    <form method="POST">
        <input type="text" name="nombre" placeholder="Nombre de usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        
        <label>Rol de Usuario:</label>
        <select name="rol" required>
            <option value="">Seleccione un rol</option>
            <option value="admin">Admin</option>
            <option value="alumno">Usuario</option>
        </select>
        
        <button type="submit">Registrar Usuario</button>
    </form>
    <a href="p_admin.html">Volver al menú</a>
</body>
</html>