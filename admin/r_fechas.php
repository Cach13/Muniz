<?php
//funciona
require_once '..\config.php';
session_start();

// Verificar si hay sesión de administrador
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?: "Semestre " . date('Y-m');
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    try {
        $stmt = $conn->prepare("INSERT INTO semestres (nombre, fecha_inicio, fecha_fin, id_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $fecha_inicio, $fecha_fin, $_SESSION['user_id']]);
        $mensaje = "Registro exitoso.";
    } catch (Exception $e) {
        $mensaje = "Error al registrar.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Fechas de Semestre</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <h2>Registrar Fechas de Semestre</h2>

    <?php if (!empty($mensaje)) : ?>
        <p><?= $mensaje ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nombre del Semestre:</label>
        <input type="text" name="nombre" placeholder="Nombre del semestre (opcional)">
        
        <label>Fecha de Inicio:</label>
        <input type="date" name="fecha_inicio" required>
        
        <label>Fecha de Fin:</label>
        <input type="date" name="fecha_fin" required>
        
        <button type="submit">Registrar Fechas</button>
    </form>
    <a href="p_admin.html">Volver al menú</a>

    <script>
        // Inicializar la validación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarLimitesFecha();
        });
    </script>

</body>
</html>
