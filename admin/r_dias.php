<?php
require '..\config.php';
session_start();

// Obtener semestres activos para select
$stmt = $conn->query("SELECT id_semestre, nombre FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descripcion = $_POST['descripcion'];
    $fecha = $_POST['fecha'];
    $id_semestre = $_POST['semestre']; 
    
    $stmt = $conn->prepare("INSERT INTO diasnohabiles (id_semestre, fecha, descripcion) VALUES (?, ?, ?)");
    $stmt->execute([$id_semestre, $fecha, $descripcion]);
    
    header("Location: p_admin.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Días Feriados</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <h2>Registrar Días Feriados</h2>
    <form method="POST">
        <input type="text" name="descripcion" placeholder="Descripción" required>
        <input type="date" name="fecha" required>
        
        <label>Semestre:</label>
        <select name="semestre" required>
            <option value="">Seleccione un semestre</option>
            <?php foreach ($semestres as $semestre): ?>
                <option value="<?php echo $semestre['id_semestre']; ?>"><?php echo htmlspecialchars($semestre['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit">Registrar Día</button>
        
    </form>
    <a href="p_admin.html">Volver al menú</a>
</body>
</html>