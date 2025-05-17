<?php
require '..\config.php';
session_start();

// Obtener lista de programas para el select
$stmt = $conn->query("SELECT id_programa, nombre_materia, horas_teoricas, horas_practicas FROM programas");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cargar datos del programa seleccionado vía AJAX
if(isset($_GET['programa_id']) && !empty($_GET['programa_id'])) {
    $id = $_GET['programa_id'];
    $stmt = $conn->prepare("SELECT nombre_materia, horas_teoricas, horas_practicas FROM programas WHERE id_programa = ?");
    $stmt->execute([$id]);
    $programa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($programa) {
        echo json_encode($programa);
        exit;
    }
}

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
    <script>
    function cargarDatosPrograma(select) {
        var id = select.value;
        if (id) {
            fetch('m_programa.php?programa_id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('nuevo_nombre').value = data.nombre_materia;
                document.getElementById('horas_teoricas').value = data.horas_teoricas;
                document.getElementById('horas_practicas').value = data.horas_practicas;
            });
        }
    }
    </script>
</head>
<body>

    <h2>Modificar Programa</h2>
    <form method="POST">
        <label>Seleccionar Programa:</label>
        <select name="programa" required onchange="cargarDatosPrograma(this)">
            <option value="">Seleccione un programa</option>
            <?php foreach ($programas as $programa): ?>
                <option value="<?php echo $programa['id_programa']; ?>"><?php echo htmlspecialchars($programa['nombre_materia']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nuevo Nombre:</label>
        <input type="text" name="nuevo_nombre" id="nuevo_nombre" placeholder="Nuevo nombre de programa" required>

        <label>Nuevas Horas Teóricas:</label>
        <input type="number" name="horas_teoricas" id="horas_teoricas" placeholder="Horas teóricas" required>

        <label>Nuevas Horas Prácticas:</label>
        <input type="number" name="horas_practicas" id="horas_practicas" placeholder="Horas prácticas" required>

        <button type="submit">Modificar Programa</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

</body>
</html>