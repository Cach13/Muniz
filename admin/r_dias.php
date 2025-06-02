<?php
require_once '../config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}


// Obtener semestres activos para select
$stmt = $conn->query("SELECT id_semestre, nombre, fecha_inicio, fecha_fin FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descripcion = $_POST['descripcion'];
    $fecha = $_POST['fecha'];
    $id_semestre = $_POST['semestre']; 
    
    try {
        // Obtener el periodo del semestre seleccionado
        $stmt = $conn->prepare("SELECT fecha_inicio, fecha_fin FROM semestres WHERE id_semestre = ?");
        $stmt->execute([$id_semestre]);
        $semestre = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Validar que la fecha esté dentro del período del semestre
        if ($fecha >= $semestre['fecha_inicio'] && $fecha <= $semestre['fecha_fin']) {
            $stmt = $conn->prepare("INSERT INTO diasnohabiles (id_semestre, fecha, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$id_semestre, $fecha, $descripcion]);
            
            $_SESSION['mensaje'] = "El día feriado se ha registrado correctamente.";
            $_SESSION['tipo_mensaje'] = "exito";
        } else {
            $_SESSION['mensaje'] = "Error: La fecha del día feriado debe estar dentro del período del semestre seleccionado (" . 
                      date('d/m/Y', strtotime($semestre['fecha_inicio'])) . " - " . 
                      date('d/m/Y', strtotime($semestre['fecha_fin'])) . ").";
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error al registrar el día feriado: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: r_dias.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Días Feriados</title>
    <link rel="stylesheet" href="css/styles.css">

    <script>
        function validarFecha() {
            var semestre = document.getElementById("semestre").value;
            var fecha = document.getElementById("fecha").value;
            
            if (semestre && fecha) {
                // Obtener las fechas del semestre seleccionado
                var semestres = <?php echo json_encode($semestres); ?>;
                var semestreSeleccionado = semestres.find(function(s) {
                    return s.id_semestre == semestre;
                });
                
                // Convertir las fechas a objetos Date para comparación
                var inicioSemestre = new Date(semestreSeleccionado.fecha_inicio);
                var finSemestre = new Date(semestreSeleccionado.fecha_fin);
                var fechaSeleccionada = new Date(fecha);
                
                // Validar que esté dentro del semestre
                if (fechaSeleccionada < inicioSemestre || fechaSeleccionada > finSemestre) {
                    alert("La fecha del día feriado debe estar dentro del período del semestre: " + 
                          formatDate(inicioSemestre) + " - " + formatDate(finSemestre));
                    return false;
                }
            }
            return true;
        }
        
        function formatDate(date) {
            return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear();
        }
        
        function actualizarLimitesFecha() {
            var semestre = document.getElementById("semestre").value;
            var fechaInput = document.getElementById("fecha");
            
            if (semestre) {
                // Obtener las fechas del semestre seleccionado
                var semestres = <?php echo json_encode($semestres); ?>;
                var semestreSeleccionado = semestres.find(function(s) {
                    return s.id_semestre == semestre;
                });
                
                // Establecer los atributos min y max en el campo de fecha
                fechaInput.min = semestreSeleccionado.fecha_inicio;
                fechaInput.max = semestreSeleccionado.fecha_fin;
            }
        }
    </script>
</head>
<body>
    <h2>Registrar Días Feriados</h2>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="mensaje <?php echo $_SESSION['tipo_mensaje']; ?>">
        <?php 
        echo $_SESSION['mensaje']; 
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
        ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" onsubmit="return validarFecha()">
        <input type="text" name="descripcion" placeholder="Descripción" required>
        
        <label>Semestre:</label>
        <select name="semestre" id="semestre" required onchange="actualizarLimitesFecha()">
            <option value="">Seleccione un semestre</option>
            <?php foreach ($semestres as $semestre): ?>
                <option value="<?php echo $semestre['id_semestre']; ?>"><?php echo htmlspecialchars($semestre['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Fecha del día feriado:</label>
        <input type="date" name="fecha" id="fecha" required>
        
        <button type="submit">Registrar Día</button>
    </form>
    
     <div class="center-link">
    <a href="p_admin.php">← Volver al inicio</a>
</div>
    
    <script>
        // Inicializar la validación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarLimitesFecha();
        });
    </script>
    
</body>
</html>