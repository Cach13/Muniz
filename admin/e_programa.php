<?php
require_once '..\config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$mensaje = "";

// Obtener lista de programas para el select
$stmt = $conn->query("SELECT id_programa, nombre_materia FROM programas");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['programa'];

    try {
        $conn->beginTransaction();

        // Eliminar temas
        $stmt = $conn->prepare("DELETE temas FROM temas 
                               INNER JOIN unidades ON temas.id_unidad = unidades.id_unidad 
                               WHERE unidades.id_programa = ?");
        $stmt->execute([$id]);

        // Eliminar unidades
        $stmt = $conn->prepare("DELETE FROM unidades WHERE id_programa = ?");
        $stmt->execute([$id]);

        // Eliminar programa
        $stmt = $conn->prepare("DELETE FROM programas WHERE id_programa = ?");
        $stmt->execute([$id]);

        $conn->commit();
        $mensaje = "Programa eliminado correctamente.";

        // Recargar lista actualizada
        $stmt = $conn->query("SELECT id_programa, nombre_materia FROM programas");
        $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $conn->rollBack();
        $mensaje = "Error al eliminar el programa.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Programa</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <h2>Eliminar Programa</h2>

    <?php if (!empty($mensaje)): ?>
        <p><?= $mensaje ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Seleccionar Programa:</label>
        <select name="programa" required>
            <option value="">Seleccione un programa</option>
            <?php foreach ($programas as $programa): ?>
                <option value="<?php echo $programa['id_programa']; ?>"><?php echo htmlspecialchars($programa['nombre_materia']); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="eliminar">Eliminar Programa</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

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
