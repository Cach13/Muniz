<?php
require '..\config.php';
session_start();

// Obtener lista de programas para el select
$stmt = $conn->query("SELECT id_programa, nombre_materia FROM programas");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['programa'];
    
    // Primero eliminar temas y unidades relacionadas
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
        
        header("Location: p_admin.html");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
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

</body>
</html>