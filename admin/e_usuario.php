
<?php
require 'config.php';
session_start();

// Obtener lista de usuarios para el select
$stmt = $conn->query("SELECT id_usuario, nombre FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['usuario'];
    
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    
    header("Location: p_admin.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Usuario</title>
    <link rel="stylesheet" href="css/acciones.css">
</head>
<body>

    <h2>Eliminar Usuario</h2>
    <form method="POST">
        <label>Seleccionar Usuario:</label>
        <select name="usuario" required>
            <option value="">Seleccione un usuario</option>
            <?php foreach ($usuarios as $usuario): ?>
                <option value="<?php echo $usuario['id_usuario']; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="eliminar">Eliminar Usuario</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

</body>
</html>