<?php
require '..\config.php';
session_start();

// Obtener lista de usuarios para el select
$stmt = $conn->query("SELECT id_usuario, nombre FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cargar datos del usuario seleccionado vía AJAX
if(isset($_GET['usuario_id']) && !empty($_GET['usuario_id'])) {
    $id = $_GET['usuario_id'];
    $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($usuario) {
        echo json_encode($usuario);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['usuario'];
    $nombre = $_POST['nuevo_nombre'];
    
    if (!empty($_POST['nueva_password'])) {
        $password = password_hash($_POST['nueva_password'], PASSWORD_DEFAULT);
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
    <script>
    function cargarDatosUsuario(select) {
        var id = select.value;
        if (id) {
            fetch('m_usuario.php?usuario_id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('nuevo_nombre').value = data.nombre;
            });
        }
    }
    </script>
</head>
<body>

    <h2>Modificar Usuario</h2>
    <form method="POST">
        <label>Seleccionar Usuario:</label>
        <select name="usuario" required onchange="cargarDatosUsuario(this)">
            <option value="">Seleccione un usuario</option>
            <?php foreach ($usuarios as $usuario): ?>
                <option value="<?php echo $usuario['id_usuario']; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nuevo Nombre:</label>
        <input type="text" name="nuevo_nombre" id="nuevo_nombre" placeholder="Nuevo nombre de usuario" required>

        <label>Nueva Contraseña:</label>
        <input type="password" name="nueva_password" placeholder="Nueva contraseña (dejar en blanco para no cambiar)">

        <button type="submit">Modificar Usuario</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

</body>
</html>