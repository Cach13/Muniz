<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo']; // Agregar campo en el formulario
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol']; // Agregar select para rol en el formulario
    
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
    <form>
        <input type="text" placeholder="Nombre de usuario" required>
        <input type="password" placeholder="Contraseña" required>
        <button type="submit">Registrar Usuario</button>
    </form>
    <a href="p_admin.html">Volver al menú</a>
</body>
</html>
