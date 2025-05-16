<?php
require 'config.php';
session_start();

// Obtener lista de usuarios para el select
$stmt = $conn->query("SELECT id_usuario, nombre FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['usuario'];
    $nombre = $_POST['nuevo_nombre'];
    $password = !empty($_POST['nueva_password']) ? password_hash($_POST['nueva_password'], PASSWORD_DEFAULT) : null;
    
    if ($password) {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, contraseña_hash = ? WHERE id_usuario = ?");
        $stmt->execute([$nombre, $password, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id_usuario = ?");
        $stmt->execute([$nombre, $id]);
    }
    
    header("Location: p_admin.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuario</title>
    <link rel="stylesheet" href="css/acciones.css">
</head>
<body>

    <h2>Modificar Usuario</h2>
    <form>
        <label>Seleccionar Usuario:</label>
        <select required>
            <option value="">Seleccione un usuario</option>
            <option value="usuario1">usuario1</option>
            <option value="usuario2">usuario2</option>
        </select>

        <label>Nuevo Nombre:</label>
        <input type="text" placeholder="Nuevo nombre de usuario">

        <label>Nueva Contraseña:</label>
        <input type="password" placeholder="Nueva contraseña">

        <button type="submit">Modificar Usuario</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

</body>
</html>
