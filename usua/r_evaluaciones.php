<?php
require_once '../config.php';
session_start();

// Verificar que el usuario está logueado y es un alumno
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'alumno') {
    header('Location: ../index.php');
    exit();
}

$id_usuario = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';

// Función para formatear la fecha
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Obtener los semestres y sus fechas límite
$stmt = $conn->query("SELECT id_semestre, nombre, fecha_inicio, fecha_fin FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$semestre_fechas = [];
foreach ($semestres as $semestre) {
    $semestre_fechas[$semestre['id_semestre']] = [
        'fecha_inicio' => $semestre['fecha_inicio'],
        'fecha_fin' => $semestre['fecha_fin'],
        'nombre' => $semestre['nombre']
    ];
}

// Obtener programas disponibles para el alumno
$programas = [];
if (!empty($semestres)) {
    $placeholders = implode(',', array_fill(0, count($semestres), '?'));
    $semestre_ids = array_column($semestres, 'id_semestre');
    
    $stmt = $conn->prepare("SELECT id_programa, nombre_materia, id_semestre FROM programas WHERE id_semestre IN ($placeholders)");
    $stmt->execute($semestre_ids);
    $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener unidades por programa
$unidades_por_programa = [];
foreach ($programas as $programa) {
    $stmt = $conn->prepare("
        SELECT id_unidad, nombre_unidad, numero_unidad 
        FROM unidades 
        WHERE id_programa = ? 
        ORDER BY numero_unidad
    ");
    $stmt->execute([$programa['id_programa']]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unidades_por_programa[$programa['id_programa']] = $unidades;
}

// Obtener temas por unidad
$temas_por_unidad = [];
$stmt = $conn->query("
    SELECT t.id_tema, t.nombre_tema, t.id_unidad, u.id_programa
    FROM temas t
    JOIN unidades u ON t.id_unidad = u.id_unidad
    ORDER BY t.nombre_tema
");
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($temas as $tema) {
    if (!isset($temas_por_unidad[$tema['id_unidad']])) {
        $temas_por_unidad[$tema['id_unidad']] = [];
    }
    $temas_por_unidad[$tema['id_unidad']][] = $tema;
}

// Obtener evaluaciones del alumno
$stmt = $conn->prepare("
    SELECT e.id_evaluacion, e.fecha_evaluacion, u.nombre_unidad, p.nombre_materia, u.id_unidad, p.id_programa 
    FROM evaluaciones e
    JOIN unidades u ON e.id_unidad = u.id_unidad
    JOIN programas p ON u.id_programa = p.id_programa
    WHERE e.id_usuario = ?
    ORDER BY e.fecha_evaluacion DESC
");
$stmt->execute([$id_usuario]);
$evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el registro de evaluaciones
if (isset($_POST['registrar_evaluacion'])) {
    $id_programa = $_POST['programa_evaluacion'];
    $id_unidades = isset($_POST['unidades_evaluacion']) ? $_POST['unidades_evaluacion'] : [];
    $fecha_evaluacion = $_POST['fecha_evaluacion'];
    
    // Validar que la fecha esté dentro del semestre del programa seleccionado
    $programa_semestre = null;
    foreach ($programas as $programa) {
        if ($programa['id_programa'] == $id_programa) {
            $programa_semestre = $programa['id_semestre'];
            break;
        }
    }
    
    if ($programa_semestre) {
        $fecha_inicio = $semestre_fechas[$programa_semestre]['fecha_inicio'];
        $fecha_fin = $semestre_fechas[$programa_semestre]['fecha_fin'];
        
        if ($fecha_evaluacion < $fecha_inicio || $fecha_evaluacion > $fecha_fin) {
            $mensaje = "La fecha de evaluación debe estar dentro del periodo del semestre (" . 
                       formatDate($fecha_inicio) . " - " . formatDate($fecha_fin) . ").";
            $tipo_mensaje = "danger";
        } else {
            // Validar que no se supere el número de unidades del programa
            $count_unidades_programa = count($unidades_por_programa[$id_programa]);
            
            // Contar evaluaciones existentes para esta fecha y programa
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM evaluaciones e
                JOIN unidades u ON e.id_unidad = u.id_unidad
                WHERE e.id_usuario = ? AND e.fecha_evaluacion = ? AND u.id_programa = ?
            ");
            $stmt->execute([$id_usuario, $fecha_evaluacion, $id_programa]);
            $evaluaciones_existentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $total_evaluaciones = $evaluaciones_existentes + count($id_unidades);
            
            if ($total_evaluaciones > $count_unidades_programa) {
                $mensaje = "No puede tener más evaluaciones que el número de unidades del programa (" . 
                           $count_unidades_programa . ").";
                $tipo_mensaje = "danger";
            } else {
                // Todo validado, insertar evaluaciones
                $conn->beginTransaction();
                try {
                    foreach ($id_unidades as $id_unidad) {
                        $stmt = $conn->prepare("
                            INSERT INTO evaluaciones (id_unidad, id_usuario, fecha_evaluacion) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$id_unidad, $id_usuario, $fecha_evaluacion]);
                    }
                    
                    $conn->commit();
                    $mensaje = "Evaluación(es) registrada(s) correctamente.";
                    $tipo_mensaje = "success";
                    
                    // Actualizar la lista de evaluaciones
                    $stmt = $conn->prepare("
                        SELECT e.id_evaluacion, e.fecha_evaluacion, u.nombre_unidad, p.nombre_materia, u.id_unidad, p.id_programa 
                        FROM evaluaciones e
                        JOIN unidades u ON e.id_unidad = u.id_unidad
                        JOIN programas p ON u.id_programa = p.id_programa
                        WHERE e.id_usuario = ?
                        ORDER BY e.fecha_evaluacion DESC
                    ");
                    $stmt->execute([$id_usuario]);
                    $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $mensaje = "Error al registrar la evaluación: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
        }
    } else {
        $mensaje = "Programa no válido.";
        $tipo_mensaje = "danger";
    }
}

// Eliminar evaluación
if (isset($_GET['eliminar_evaluacion']) && is_numeric($_GET['eliminar_evaluacion'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM evaluaciones WHERE id_evaluacion = ? AND id_usuario = ?");
        $stmt->execute([$_GET['eliminar_evaluacion'], $id_usuario]);
        
        $mensaje = "Evaluación eliminada correctamente.";
        $tipo_mensaje = "success";
        
        // Actualizar la lista de evaluaciones
        $stmt = $conn->prepare("
            SELECT e.id_evaluacion, e.fecha_evaluacion, u.nombre_unidad, p.nombre_materia, u.id_unidad, p.id_programa 
            FROM evaluaciones e
            JOIN unidades u ON e.id_unidad = u.id_unidad
            JOIN programas p ON u.id_programa = p.id_programa
            WHERE e.id_usuario = ?
            ORDER BY e.fecha_evaluacion DESC
        ");
        $stmt->execute([$id_usuario]);
        $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar la evaluación: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener la fecha mínima y máxima entre todos los semestres
$min_date = null;
$max_date = null;
foreach ($semestre_fechas as $fechas) {
    if ($min_date === null || $fechas['fecha_inicio'] < $min_date) {
        $min_date = $fechas['fecha_inicio'];
    }
    if ($max_date === null || $fechas['fecha_fin'] > $max_date) {
        $max_date = $fechas['fecha_fin'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Evaluaciones</title>
    <link rel="stylesheet" href="css/usuario.css">
    <script>
        function cargarUnidades(programaId) {
            // Ocultar todas las secciones de unidades
            var contenedoresUnidades = document.querySelectorAll('.unidades-container');
            for (var i = 0; i < contenedoresUnidades.length; i++) {
                contenedoresUnidades[i].style.display = 'none';
            }
            
            // Mostrar solo las unidades del programa seleccionado
            var contenedorSeleccionado = document.getElementById('unidades-programa-' + programaId);
            if (contenedorSeleccionado) {
                contenedorSeleccionado.style.display = 'block';
            }
            
            // Actualizar información del semestre
            var semestreInfo = document.getElementById('semestre-info');
            var selectPrograma = document.getElementById('programa-evaluacion');
            var selectedOption = selectPrograma.options[selectPrograma.selectedIndex];
            var semestreId = selectedOption.getAttribute('data-semestre');
            
            if (semestreId) {
                var semestreNombre = selectedOption.getAttribute('data-semestre-nombre');
                var fechaInicio = selectedOption.getAttribute('data-fecha-inicio');
                var fechaFin = selectedOption.getAttribute('data-fecha-fin');
                
                semestreInfo.innerHTML = 'Semestre: ' + semestreNombre + ' (Periodo: ' + 
                                         formatearFecha(fechaInicio) + ' - ' + formatearFecha(fechaFin) + ')';
                
                // Actualizar los límites de fecha en el selector
                var fechaInput = document.getElementById('fecha-evaluacion');
                fechaInput.min = fechaInicio;
                fechaInput.max = fechaFin;
            }
        }
        
        function formatearFecha(fechaStr) {
            var fecha = new Date(fechaStr);
            return fecha.getDate() + '/' + (fecha.getMonth() + 1) + '/' + fecha.getFullYear();
        }
    </script>
</head>
<body>
    <h2>Registro de Evaluaciones</h2>
    
    <?php if ($mensaje): ?>
    <div class="mensaje <?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <h3>Registrar Evaluación</h3>
    <form method="POST">
        <label>Seleccionar Programa:</label>
        <select name="programa_evaluacion" id="programa-evaluacion" required onchange="cargarUnidades(this.value)">
            <option value="">Seleccione un programa</option>
            <?php foreach ($programas as $programa): ?>
                <?php 
                $semestre_id = $programa['id_semestre'];
                $fecha_inicio = $semestre_fechas[$semestre_id]['fecha_inicio'];
                $fecha_fin = $semestre_fechas[$semestre_id]['fecha_fin'];
                $semestre_nombre = $semestre_fechas[$semestre_id]['nombre'];
                ?>
                <option value="<?php echo $programa['id_programa']; ?>" 
                        data-semestre="<?php echo $semestre_id; ?>"
                        data-semestre-nombre="<?php echo htmlspecialchars($semestre_nombre); ?>"
                        data-fecha-inicio="<?php echo $fecha_inicio; ?>"
                        data-fecha-fin="<?php echo $fecha_fin; ?>">
                    <?php echo htmlspecialchars($programa['nombre_materia']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <div id="semestre-info" class="semestre-info"></div>
        
        <label>Seleccionar Unidades:</label>
        <?php foreach ($programas as $programa): ?>
            <div id="unidades-programa-<?php echo $programa['id_programa']; ?>" class="unidades-container" style="display: none;">
                <?php if (isset($unidades_por_programa[$programa['id_programa']])): ?>
                    <?php foreach ($unidades_por_programa[$programa['id_programa']] as $unidad): ?>
                        <label class="unidad-checkbox">
                            <input type="checkbox" name="unidades_evaluacion[]" value="<?php echo $unidad['id_unidad']; ?>">
                            Unidad <?php echo $unidad['numero_unidad']; ?>: <?php echo htmlspecialchars($unidad['nombre_unidad']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay unidades disponibles para este programa.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <label>Fecha de evaluación:</label>
        <input type="date" name="fecha_evaluacion" id="fecha-evaluacion" required 
               min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>">
        
        <button type="submit" name="registrar_evaluacion">Registrar Evaluación</button>
    </form>
    
    <h3>Evaluaciones Registradas</h3>
    <?php if (!empty($evaluaciones)): ?>
        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Unidad</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($evaluaciones as $e): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['nombre_materia']); ?></td>
                        <td><?php echo htmlspecialchars($e['nombre_unidad']); ?></td>
                        <td><?php echo formatDate($e['fecha_evaluacion']); ?></td>
                        <td>
                            <a href="?eliminar_evaluacion=<?php echo $e['id_evaluacion']; ?>" class="accion" 
                               onclick="return confirm('¿Está seguro de eliminar esta evaluación?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay evaluaciones registradas.</p>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="r_planificacion.php">Ir a Planificación</a>
        <a href="r_disponibilidad.php">Ir a Disponibilidad</a>
        <a href="p_reportes.html">Ir a Reportes</a>
        <a href="p_usuario.html">Ir a Inicio</a>
    </div>
</body>
</html>