
<?php
require 'config.php';
session_start();

// Verificar si hay sesión de administrador
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contraseña_hash, rol) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $correo, $password, $rol]);
    
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
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        
        <label>Rol de Usuario:</label>
        <select name="rol" required>
            <option value="">Seleccione un rol</option>
            <option value="admin">Administrador</option>
            <option value="profesor">Profesor</option>
            <option value="estudiante">Estudiante</option>
        </select>
        
        <button type="submit">Registrar Usuario</button>
    </form>
    <a href="p_admin.html">Volver al menú</a>
</body>
</html>