<?php
require 'config.php';
session_start();

// Obtener lista de programas para el select
$stmt = $conn->query("SELECT id_programa, nombre_materia FROM programas");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['programa'];
    
    // Primero eliminar temas y unidades relacionadas
    // (Implementar transacciones para mayor seguridad)
    
    $stmt = $conn->prepare("DELETE FROM programas WHERE id_programa = ?");
    $stmt->execute([$id]);
    
    header("Location: p_admin.html");
    exit;
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Programa</title>
    <link rel="stylesheet" href="css/acciones.css">
</head>
<body>

    <h2>Eliminar Programa</h2>
    <form>
        <label>Seleccionar Programa:</label>
        <select required>
            <option value="">Seleccione un programa</option>
            <option value="matematicas">Matemáticas</option>
            <option value="historia">Historia</option>
        </select>

        <button type="submit" class="eliminar">Eliminar Programa</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

</body>
</html>
