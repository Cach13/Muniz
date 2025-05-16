<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Insertar programa
    $stmt = $conn->prepare("INSERT INTO programas (nombre_materia, horas_teoricas, horas_practicas, id_semestre, id_admin) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['materia'],
        $_POST['horas_teoricas'],
        $_POST['horas_practicas'],
        $_POST['semestre'], // Agregar select para semestre en el formulario
        $_SESSION['user_id']
    ]);
    
    $id_programa = $conn->lastInsertId();
    
    // Insertar unidades y temas
    $unidades = explode(',', $_POST['temas_unidades']);
    foreach ($unidades as $index => $temas) {
        $stmt = $conn->prepare("INSERT INTO unidades (id_programa, nombre_unidad, numero_unidad) VALUES (?, ?, ?)");
        $stmt->execute([$id_programa, "Unidad " . ($index + 1), $index + 1]);
        $id_unidad = $conn->lastInsertId();
        
        $temas_array = explode(',', trim($temas));
        foreach ($temas_array as $tema) {
            $stmt = $conn->prepare("INSERT INTO temas (id_unidad, nombre_tema) VALUES (?, ?)");
            $stmt->execute([$id_unidad, trim($tema)]);
        }
    }
    
    header("Location: p_admin.html");
    exit;
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Programa Académico</title>
    <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <h2>Registrar Programa Académico</h2>
    <form>
        <input type="text" placeholder="Materia" required>
        <input type="number" placeholder="Horas Teóricas" required>
        <input type="number" placeholder="Horas Prácticas" required>
        <input type="number" placeholder="Número de Unidades" required>
        <textarea placeholder="Temas de Unidades (Separados por coma)" required></textarea>
        <button type="submit">Registrar Programa</button>
    </form>
    <a href="p_admin.html">Volver al menú</a>
</body>
</html>
