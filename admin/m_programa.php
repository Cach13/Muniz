<?php
require 'config.php';
session_start();

// Obtener lista de programas para el select
$stmt = $conn->query("SELECT id_programa, nombre_materia FROM programas");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['programa'];
    $nombre = $_POST['nuevo_nombre'];
    $horas_teoricas = $_POST['horas_teoricas'];
    $horas_practicas = $_POST['horas_practicas'];
    
    $stmt = $conn->prepare("UPDATE programas SET nombre_materia = ?, horas_teoricas = ?, horas_practicas = ? WHERE id_programa = ?");
    $stmt->execute([$nombre, $horas_teoricas, $horas_practicas, $id]);
    
    header("Location: p_admin.html");
    exit;
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Programa</title>
    <link rel="stylesheet" href="css/acciones.css">
</head>
<body>

    <h2>Modificar Programa</h2>
    <form>
        <label>Seleccionar Programa:</label>
        <select required>
            <option value="">Seleccione un programa</option>
            <option value="matematicas">Matemáticas</option>
            <option value="historia">Historia</option>
        </select>

        <label>Nuevo Nombre:</label>
        <input type="text" placeholder="Nuevo nombre de programa">

        <label>Nuevas Horas Teóricas:</label>
        <input type="number" placeholder="Horas teóricas">

        <label>Nuevas Horas Prácticas:</label>
        <input type="number" placeholder="Horas prácticas">

        <button type="submit">Modificar Programa</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

</body>
</html>
