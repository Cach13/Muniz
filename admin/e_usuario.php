
<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$mensaje = "";

// Obtener lista de usuarios para el select
$stmt = $conn->query("SELECT id_usuario, nombre FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['usuario'];

    try {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $mensaje = "Usuario eliminado correctamente.";
        
        // Actualizar lista de usuarios
        $stmt = $conn->query("SELECT id_usuario, nombre FROM usuarios");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = "Error al eliminar el usuario.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Usuario</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <h2>Eliminar Usuario</h2>

    <?php if (!empty($mensaje)): ?>
        <p><?= $mensaje ?></p>
    <?php endif; ?>

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

     <div class="center-link">
    <a href="p_admin.php">← Volver al inicio</a>
</div>

    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            const confirmacion = confirm("¿Estás seguro de que deseas eliminar esto? Esta acción no se puede deshacer.");
            if (!confirmacion) {
                e.preventDefault();
            }
        });
    </script>

</body>
</html>
