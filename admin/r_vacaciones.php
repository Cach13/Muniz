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

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descripcion = $_POST['descripcion'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_final'];
    $id_semestre = $_POST['semestre']; 
    
    // Obtener el periodo del semestre seleccionado
    $stmt = $conn->prepare("SELECT fecha_inicio, fecha_fin FROM semestres WHERE id_semestre = ?");
    $stmt->execute([$id_semestre]);
    $semestre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Validar que las fechas estén dentro del período del semestre
    if ($fecha_inicio >= $semestre['fecha_inicio'] && $fecha_fin <= $semestre['fecha_fin']) {
        // Validar que la fecha de inicio sea menor o igual a la fecha final
        if ($fecha_inicio <= $fecha_fin) {
            try {
                $stmt = $conn->prepare("INSERT INTO vacaciones (id_semestre, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_semestre, $fecha_inicio, $fecha_fin, $descripcion]);

                $mensaje = "Vacaciones registradas correctamente.";
                $tipo_mensaje = "success";
            } catch (PDOException $e) {
                $mensaje = "Error al registrar las vacaciones: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "La fecha de inicio debe ser menor o igual a la fecha final.";
            $tipo_mensaje = "warning";
        }
    } else {
        $mensaje = "Las fechas deben estar dentro del período del semestre.";
        $tipo_mensaje = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Vacaciones</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        function validarFechas() {
            var semestre = document.getElementById("semestre").value;
            var fechaInicio = document.getElementById("fecha_inicio").value;
            var fechaFin = document.getElementById("fecha_final").value;
            
            if (semestre && fechaInicio && fechaFin) {
                // Obtener las fechas del semestre seleccionado
                var semestres = <?php echo json_encode($semestres); ?>;
                var semestreSeleccionado = semestres.find(function(s) {
                    return s.id_semestre == semestre;
                });
                
                // Convertir las fechas a objetos Date para comparación
                var inicioSemestre = new Date(semestreSeleccionado.fecha_inicio);
                var finSemestre = new Date(semestreSeleccionado.fecha_fin);
                var inicioVacaciones = new Date(fechaInicio);
                var finVacaciones = new Date(fechaFin);
                
                // Validar que estén dentro del semestre
                if (inicioVacaciones < inicioSemestre || finVacaciones > finSemestre) {
                    alert("Las fechas de vacaciones deben estar dentro del período del semestre: " + 
                          formatDate(inicioSemestre) + " - " + formatDate(finSemestre));
                    return false;
                }
                
                // Validar que la fecha de inicio sea anterior a la fecha final
                if (inicioVacaciones > finVacaciones) {
                    alert("La fecha de inicio debe ser anterior o igual a la fecha final");
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
            var fechaInicio = document.getElementById("fecha_inicio");
            var fechaFin = document.getElementById("fecha_final");
            
            if (semestre) {
                // Obtener las fechas del semestre seleccionado
                var semestres = <?php echo json_encode($semestres); ?>;
                var semestreSeleccionado = semestres.find(function(s) {
                    return s.id_semestre == semestre;
                });
                
                // Establecer los atributos min y max en los campos de fecha
                fechaInicio.min = semestreSeleccionado.fecha_inicio;
                fechaInicio.max = semestreSeleccionado.fecha_fin;
                
                fechaFin.min = semestreSeleccionado.fecha_inicio;
                fechaFin.max = semestreSeleccionado.fecha_fin;
            }
        }
    </script>
</head>
<body>
    <h2>Registrar Vacaciones</h2>
    
    <?php if ($mensaje): ?>
    <div class="mensaje <?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" onsubmit="return validarFechas()">
        <input type="text" name="descripcion" placeholder="Descripción" required>
        
        <label>Semestre:</label>
        <select name="semestre" id="semestre" required onchange="actualizarLimitesFecha()">
            <option value="">Seleccione un semestre</option>
            <?php foreach ($semestres as $semestre): ?>
                <option value="<?php echo $semestre['id_semestre']; ?>"><?php echo htmlspecialchars($semestre['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Fecha de inicio:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" required>
        
        <label>Fecha final:</label>
        <input type="date" name="fecha_final" id="fecha_final" required>
        
        <button type="submit">Registrar Vacaciones</button>
    </form>
    
    <a href="p_admin.php">Volver al menú</a>
    
    <script>
        // Inicializar la validación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarLimitesFecha();
        });
    </script>
    
</body>
</html>