<?php
require_once '..\config.php';
session_start();

// Verificar si hay sesión de administrador
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Obtener semestres disponibles
$stmt = $conn->query("SELECT id_semestre, nombre FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        
        // Insertar programa
        $stmt = $conn->prepare("INSERT INTO programas (nombre_materia, horas_teoricas, horas_practicas, id_semestre, id_admin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['materia'],
            $_POST['horas_teoricas'],
            $_POST['horas_practicas'],
            $_POST['semestre'],
            $_SESSION['user_id']
        ]);
        
        $id_programa = $conn->lastInsertId();
        
        // Insertar unidades y temas
        $num_unidades = $_POST['num_unidades'];
        
        for ($i = 1; $i <= $num_unidades; $i++) {
            $nombre_unidad = "Unidad " . $i;
            
            $stmt = $conn->prepare("INSERT INTO unidades (id_programa, nombre_unidad, numero_unidad) VALUES (?, ?, ?)");
            $stmt->execute([$id_programa, $nombre_unidad, $i]);
            $id_unidad = $conn->lastInsertId();
            
            if (isset($_POST['temas_unidad_' . $i]) && !empty($_POST['temas_unidad_' . $i])) {
                $temas_array = explode(',', trim($_POST['temas_unidad_' . $i]));
                foreach ($temas_array as $tema) {
                    if (trim($tema) !== '') {
                        $stmt = $conn->prepare("INSERT INTO temas (id_unidad, nombre_tema) VALUES (?, ?)");
                        $stmt->execute([$id_unidad, trim($tema)]);
                    }
                }
            }
        }
        
        $conn->commit();
        header("Location: r_programa.php");
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
    <title>Registrar Programa Académico</title>
    <link rel="stylesheet" href="css/form.css">
    <script>
    function mostrarCamposTemas() {
        var numUnidades = document.getElementById('num_unidades').value;
        var contenedorTemas = document.getElementById('temas_container');
        contenedorTemas.innerHTML = '';
        
        for (var i = 1; i <= numUnidades; i++) {
            var div = document.createElement('div');
            div.innerHTML = `
                <label>Temas de Unidad ${i} (Separados por coma):</label>
                <textarea name="temas_unidad_${i}" placeholder="Tema 1, Tema 2, Tema 3..." required></textarea>
            `;
            contenedorTemas.appendChild(div);
        }
    }
    </script>
</head>
<body>
    <h2>Registrar Programa Académico</h2>
    <form method="POST">
        <input type="text" name="materia" placeholder="Materia" required>
        <input type="number" name="horas_teoricas" placeholder="Horas Teóricas" required min="0">
        <input type="number" name="horas_practicas" placeholder="Horas Prácticas" required min="0">
        
        <label>Semestre:</label>
        <select name="semestre" required>
            <option value="">Seleccione un semestre</option>
            <?php foreach ($semestres as $semestre): ?>
                <option value="<?php echo $semestre['id_semestre']; ?>"><?php echo htmlspecialchars($semestre['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Número de Unidades:</label>
        <input type="number" id="num_unidades" name="num_unidades" min="1" required onchange="mostrarCamposTemas()">
        
        <div id="temas_container">
            <!-- Aquí se generarán dinámicamente los campos para los temas de cada unidad -->
        </div>
        
        <button type="submit">Registrar Programa</button>
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