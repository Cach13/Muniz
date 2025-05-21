<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'alumno') {
    header('Location: ../index.php');
    exit();
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Usuario</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="menu-container">
        <h1>Panel Usuario</h1>
        <a href="r_planificacion.php">Registrar Planificacion</a>
        <a href="r_disponibilidad.php">Registrar Disponibilidad</a>
        <a href="r_evaluaciones.php">Registrar Evaluaciones</a>
        <a href="gestion_dosificacion.php">Generar Reportes</a>
    </div>

</body>
</html>
