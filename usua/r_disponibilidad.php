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

// Obtener disponibilidad del alumno
$disponibilidad = [];
$stmt = $conn->prepare("
    SELECT d.id_disponibilidad, d.fecha, d.hora_inicio, d.hora_fin, 
           d.id_tema, t.nombre_tema, u.nombre_unidad, p.nombre_materia, 
           p.id_programa, p.horas_teoricas, p.horas_practicas
    FROM disponibilidad d
    LEFT JOIN temas t ON d.id_tema = t.id_tema
    LEFT JOIN unidades u ON t.id_unidad = u.id_unidad
    LEFT JOIN programas p ON u.id_programa = p.id_programa
    WHERE d.id_usuario = ?
    ORDER BY d.fecha, d.hora_inicio
");
$stmt->execute([$id_usuario]);
$disponibilidad = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener semestres activos
$stmt = $conn->query("SELECT id_semestre, nombre, fecha_inicio, fecha_fin FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los programas disponibles para el alumno
$programas = [];
if (!empty($semestres)) {
    $placeholders = implode(',', array_fill(0, count($semestres), '?'));
    $semestre_ids = array_column($semestres, 'id_semestre');
    
    $stmt = $conn->prepare("SELECT id_programa, nombre_materia, id_semestre, horas_teoricas, horas_practicas FROM programas WHERE id_semestre IN ($placeholders)");
    $stmt->execute($semestre_ids);
    $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Asociar cada programa con su semestre para acceder a las fechas
    foreach ($programas as &$programa) {
        foreach ($semestres as $semestre) {
            if ($programa['id_semestre'] == $semestre['id_semestre']) {
                $programa['fecha_inicio'] = $semestre['fecha_inicio'];
                $programa['fecha_fin'] = $semestre['fecha_fin'];
                break;
            }
        }
    }
    unset($programa); // Romper la referencia
}

// Obtener todas las unidades y temas por programa
$unidades_por_programa = [];
$temas_por_unidad = [];

foreach ($programas as $programa) {
    // Obtener unidades del programa
    $stmt = $conn->prepare("SELECT id_unidad, nombre_unidad FROM unidades WHERE id_programa = ?");
    $stmt->execute([$programa['id_programa']]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unidades_por_programa[$programa['id_programa']] = $unidades;
    
    foreach ($unidades as $unidad) {
        // Obtener temas de la unidad
        $stmt = $conn->prepare("SELECT id_tema, nombre_tema FROM temas WHERE id_unidad = ?");
        $stmt->execute([$unidad['id_unidad']]);
        $temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $temas_por_unidad[$unidad['id_unidad']] = $temas;
    }
}

// Procesar el registro de disponibilidad
if (isset($_POST['registrar_disponibilidad'])) {
    $id_programa = $_POST['id_programa'];
    $id_tema = $_POST['id_tema'];
    $fecha = $_POST['fecha_disponibilidad'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    
    // Obtener información del programa seleccionado
    $programa_seleccionado = null;
    foreach ($programas as $programa) {
        if ($programa['id_programa'] == $id_programa) {
            $programa_seleccionado = $programa;
            break;
        }
    }
    
    if (!$programa_seleccionado) {
        $mensaje = "Programa no encontrado.";
        $tipo_mensaje = "danger";
    } else {
        $horas_totales_programa = $programa_seleccionado['horas_teoricas'] + $programa_seleccionado['horas_practicas'];
        $fecha_inicio_semestre = $programa_seleccionado['fecha_inicio'];
        $fecha_fin_semestre = $programa_seleccionado['fecha_fin'];
        
        // Validar que la fecha esté dentro del semestre
        if ($fecha < $fecha_inicio_semestre || $fecha > $fecha_fin_semestre) {
            $mensaje = "La fecha seleccionada debe estar dentro del periodo del semestre (" . formatDate($fecha_inicio_semestre) . " - " . formatDate($fecha_fin_semestre) . ").";
            $tipo_mensaje = "warning";
        } 
        // Validar que la hora de inicio sea menor que la hora de fin
        else if ($hora_inicio >= $hora_fin) {
            $mensaje = "La hora de inicio debe ser menor que la hora de fin.";
            $tipo_mensaje = "warning";
        } 
        else {
            // Calcular horas de disponibilidad actuales para este programa
            $horas_disponibilidad_actual = 0;
            $inicio_timestamp = strtotime("$fecha $hora_inicio");
            $fin_timestamp = strtotime("$fecha $hora_fin");
            $nueva_duracion_horas = ($fin_timestamp - $inicio_timestamp) / 3600;
            
            // Obtener todas las horas de disponibilidad existentes para el programa seleccionado
            $stmt = $conn->prepare("
                SELECT d.hora_inicio, d.hora_fin
                FROM disponibilidad d
                JOIN temas t ON d.id_tema = t.id_tema
                JOIN unidades u ON t.id_unidad = u.id_unidad
                WHERE u.id_programa = ? AND d.id_usuario = ?
            ");
            $stmt->execute([$id_programa, $id_usuario]);
            $disponibilidades_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($disponibilidades_existentes as $disp) {
                $inicio = strtotime($disp['hora_inicio']);
                $fin = strtotime($disp['hora_fin']);
                $duracion_horas = ($fin - $inicio) / 3600;
                $horas_disponibilidad_actual += $duracion_horas;
            }
            
            // Validar que no exceda el total de horas del programa
            if (($horas_disponibilidad_actual + $nueva_duracion_horas) > $horas_totales_programa) {
                $mensaje = "El total de horas de disponibilidad (" . number_format($horas_disponibilidad_actual + $nueva_duracion_horas, 2) . ") supera el límite de horas del programa (" . $horas_totales_programa . ").";
                $tipo_mensaje = "warning";
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO disponibilidad (id_usuario, fecha, hora_inicio, hora_fin, id_tema) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$id_usuario, $fecha, $hora_inicio, $hora_fin, $id_tema]);
                    
                    $mensaje = "Disponibilidad registrada correctamente.";
                    $tipo_mensaje = "success";
                    
                    // Actualizar la lista de disponibilidad
                    $stmt = $conn->prepare("
                        SELECT d.id_disponibilidad, d.fecha, d.hora_inicio, d.hora_fin, 
                               d.id_tema, t.nombre_tema, u.nombre_unidad, p.nombre_materia,
                               p.id_programa, p.horas_teoricas, p.horas_practicas
                        FROM disponibilidad d
                        LEFT JOIN temas t ON d.id_tema = t.id_tema
                        LEFT JOIN unidades u ON t.id_unidad = u.id_unidad
                        LEFT JOIN programas p ON u.id_programa = p.id_programa
                        WHERE d.id_usuario = ?
                        ORDER BY d.fecha, d.hora_inicio
                    ");
                    $stmt->execute([$id_usuario]);
                    $disponibilidad = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $mensaje = "Error al registrar la disponibilidad: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Eliminar disponibilidad
if (isset($_GET['eliminar_disponibilidad']) && is_numeric($_GET['eliminar_disponibilidad'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM disponibilidad WHERE id_disponibilidad = ? AND id_usuario = ?");
        $stmt->execute([$_GET['eliminar_disponibilidad'], $id_usuario]);
        
        $mensaje = "Disponibilidad eliminada correctamente.";
        $tipo_mensaje = "success";
        
        // Actualizar la lista de disponibilidad
        $stmt = $conn->prepare("
            SELECT d.id_disponibilidad, d.fecha, d.hora_inicio, d.hora_fin, 
                   d.id_tema, t.nombre_tema, u.nombre_unidad, p.nombre_materia,
                   p.id_programa, p.horas_teoricas, p.horas_practicas
            FROM disponibilidad d
            LEFT JOIN temas t ON d.id_tema = t.id_tema
            LEFT JOIN unidades u ON t.id_unidad = u.id_unidad
            LEFT JOIN programas p ON u.id_programa = p.id_programa
            WHERE d.id_usuario = ?
            ORDER BY d.fecha, d.hora_inicio
        ");
        $stmt->execute([$id_usuario]);
        $disponibilidad = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar la disponibilidad: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Calcular horas totales por programa
$horas_por_programa = [];
foreach ($programas as $programa) {
    $horas_por_programa[$programa['id_programa']] = [
        'nombre' => $programa['nombre_materia'],
        'total_permitido' => $programa['horas_teoricas'] + $programa['horas_practicas'],
        'total_actual' => 0
    ];
}

foreach ($disponibilidad as $disp) {
    if (isset($disp['id_programa']) && isset($horas_por_programa[$disp['id_programa']])) {
        $inicio = strtotime($disp['hora_inicio']);
        $fin = strtotime($disp['hora_fin']);
        $duracion_horas = ($fin - $inicio) / 3600;
        $horas_por_programa[$disp['id_programa']]['total_actual'] += $duracion_horas;
    }
}

// Función para formatear la fecha
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Función para formatear la hora
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Función para calcular duración entre dos horas
function calcularDuracionHoras($hora_inicio, $hora_fin) {
    $inicio = strtotime($hora_inicio);
    $fin = strtotime($hora_fin);
    return ($fin - $inicio) / 3600;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Disponibilidad</title>
    <link rel="stylesheet" href="css/styles_u.css">
</head>
<body>
    <h2>Registro de Disponibilidad</h2>
    
    <?php if ($mensaje): ?>
    <div class="mensaje <?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <div class="programas-disponibles">
        <h3>Horas Disponibles por Programa</h3>
        <?php if (!empty($horas_por_programa)): ?>
            <?php foreach ($horas_por_programa as $id_programa => $info): ?>
                <?php 
                $porcentaje = ($info['total_permitido'] > 0) ? 
                    min(100, ($info['total_actual'] / $info['total_permitido']) * 100) : 0;
                $excedido = $info['total_actual'] > $info['total_permitido'];
                ?>
                <div class="programa-info <?php echo $excedido ? 'limite-excedido' : ''; ?>">
                    <div>
                        <h4><?php echo htmlspecialchars($info['nombre']); ?></h4>
                        <p>
                            Horas asignadas: <?php echo number_format($info['total_actual'], 2); ?> / 
                            <?php echo $info['total_permitido']; ?> horas
                        </p>
                        <div class="programa-barra">
                            <div class="programa-progreso <?php echo $excedido ? 'excedido' : ''; ?>" 
                                 style="width: <?php echo $porcentaje; ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay programas disponibles.</p>
        <?php endif; ?>
    </div>
    
    <div class="form-section">
        <h3>Registrar Disponibilidad</h3>
        <form method="POST">
            <label>Programa:</label>
            <select name="id_programa" id="select-programa" required>
                <option value="">Seleccione un programa</option>
                <?php foreach ($programas as $programa): ?>
                    <?php
                    $total_horas = $programa['horas_teoricas'] + $programa['horas_practicas'];
                    $horas_usadas = isset($horas_por_programa[$programa['id_programa']]) ? 
                        $horas_por_programa[$programa['id_programa']]['total_actual'] : 0;
                    $horas_disponibles = $total_horas - $horas_usadas;
                    ?>
                    <option value="<?php echo $programa['id_programa']; ?>" 
                            data-fecha-inicio="<?php echo $programa['fecha_inicio']; ?>"
                            data-fecha-fin="<?php echo $programa['fecha_fin']; ?>"
                            data-horas-disponibles="<?php echo $horas_disponibles; ?>">
                        <?php echo htmlspecialchars($programa['nombre_materia']); ?> 
                        (<?php echo number_format($horas_disponibles, 2); ?> horas disponibles)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div class="date-range-info" id="fecha-rango-info">
                Seleccione un programa para ver el rango de fechas disponible.
            </div>
            
            <label>Unidad:</label>
            <select name="id_unidad" id="select-unidad" required>
                <option value="">Primero seleccione un programa</option>
            </select>
            
            <label>Tema:</label>
            <select name="id_tema" id="select-tema" required>
                <option value="">Primero seleccione una unidad</option>
            </select>
            
            <label>Fecha:</label>
            <input type="date" name="fecha_disponibilidad" id="fecha-disponibilidad" required>
            
            <label>Hora de inicio:</label>
            <input type="time" name="hora_inicio" id="hora-inicio" required>
            
            <label>Hora de fin:</label>
            <input type="time" name="hora_fin" id="hora-fin" required>
            
            <div id="duracion-info"></div>
            
            <button type="submit" name="registrar_disponibilidad" id="btn-registrar">Registrar Disponibilidad</button>
        </form>
    </div>
    
    <h3>Disponibilidad Registrada</h3>
    <?php if (!empty($disponibilidad)): ?>
        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Unidad</th>
                    <th>Tema</th>
                    <th>Fecha</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                    <th>Duración</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disponibilidad as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['nombre_materia'] ?? 'No asignado'); ?></td>
                        <td><?php echo htmlspecialchars($d['nombre_unidad'] ?? 'No asignado'); ?></td>
                        <td><?php echo htmlspecialchars($d['nombre_tema'] ?? 'No asignado'); ?></td>
                        <td><?php echo formatDate($d['fecha']); ?></td>
                        <td><?php echo formatTime($d['hora_inicio']); ?></td>
                        <td><?php echo formatTime($d['hora_fin']); ?></td>
                        <td>
                            <?php
                            $duracion = calcularDuracionHoras($d['hora_inicio'], $d['hora_fin']);
                            echo number_format($duracion, 2) . ' hrs';
                            ?>
                        </td>
                        <td>
                            <a href="?eliminar_disponibilidad=<?php echo $d['id_disponibilidad']; ?>" class="accion" 
                               onclick="return confirm('¿Está seguro de eliminar esta disponibilidad?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay disponibilidad registrada.</p>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="r_planificacion.php">Ir a Planificación</a>
        <a href="r_evaluaciones.php">Ir a Evaluaciones</a>
        <a href="gestion_dosificacion.php">Ir a Reportes</a>
        <a href="p_usuario.php">Ir a Inicio</a>
    </div>
    
    <script>
        // Datos de programas, unidades y temas
        const unidadesPorPrograma = <?php echo json_encode($unidades_por_programa); ?>;
        const temasPorUnidad = <?php echo json_encode($temas_por_unidad); ?>;
        
        // Elementos del DOM
        const selectPrograma = document.getElementById('select-programa');
        const selectUnidad = document.getElementById('select-unidad');
        const selectTema = document.getElementById('select-tema');
        const fechaDisponibilidad = document.getElementById('fecha-disponibilidad');
        const fechaRangoInfo = document.getElementById('fecha-rango-info');
        const horaInicio = document.getElementById('hora-inicio');
        const horaFin = document.getElementById('hora-fin');
        const duracionInfo = document.getElementById('duracion-info');
        const btnRegistrar = document.getElementById('btn-registrar');
        
        // Evento: Cambio de programa
        selectPrograma.addEventListener('change', function() {
            const programaId = this.value;
            
            // Limpiar y actualizar unidades
            selectUnidad.innerHTML = '<option value="">Seleccione una unidad</option>';
            selectTema.innerHTML = '<option value="">Primero seleccione una unidad</option>';
            
            if (programaId) {
                // Obtener fechas del semestre
                const fechaInicio = this.options[this.selectedIndex].getAttribute('data-fecha-inicio');
                const fechaFin = this.options[this.selectedIndex].getAttribute('data-fecha-fin');
                const horasDisponibles = this.options[this.selectedIndex].getAttribute('data-horas-disponibles');
                
                // Actualizar información de fechas
                fechaRangoInfo.textContent = `Período válido: ${formatDateJS(fechaInicio)} - ${formatDateJS(fechaFin)} | Horas disponibles: ${horasDisponibles}`;
                
                // Establecer restricciones en el campo de fecha
                fechaDisponibilidad.min = fechaInicio;
                fechaDisponibilidad.max = fechaFin;
                
                // Cargar unidades del programa seleccionado
                if (unidadesPorPrograma[programaId]) {
                    unidadesPorPrograma[programaId].forEach(unidad => {
                        const option = document.createElement('option');
                        option.value = unidad.id_unidad;
                        option.textContent = unidad.nombre_unidad;
                        selectUnidad.appendChild(option);
                    });
                }
            } else {
                fechaRangoInfo.textContent = 'Seleccione un programa para ver el rango de fechas disponible.';
                fechaDisponibilidad.removeAttribute('min');
                fechaDisponibilidad.removeAttribute('max');
            }
        });
        
        // Evento: Cambio de unidad
        selectUnidad.addEventListener('change', function() {
            const unidadId = this.value;
            
            // Limpiar y actualizar temas
            selectTema.innerHTML = '<option value="">Seleccione un tema</option>';
            
            if (unidadId && temasPorUnidad[unidadId]) {
                temasPorUnidad[unidadId].forEach(tema => {
                    const option = document.createElement('option');
                    option.value = tema.id_tema;
                    option.textContent = tema.nombre_tema;
                    selectTema.appendChild(option);
                });
            }
        });
        
        // Evento: Cambio de horas
        function actualizarDuracion() {
            if (horaInicio.value && horaFin.value) {
                const inicio = new Date(`2000-01-01T${horaInicio.value}`);
                const fin = new Date(`2000-01-01T${horaFin.value}`);
                
                if (fin <= inicio) {
                    duracionInfo.textContent = 'Error: La hora de inicio debe ser menor que la hora de fin.';
                    duracionInfo.style.color = 'red';
                    btnRegistrar.disabled = true;
                    return;
                }
                
                // Calcular duración en horas
                const duracionMs = fin - inicio;
                const duracionHoras = duracionMs / (1000 * 60 * 60);
                
                // Verificar si hay suficientes horas disponibles
                const programaId = selectPrograma.value;
                if (programaId) {
                    const horasDisponibles = parseFloat(selectPrograma.options[selectPrograma.selectedIndex].getAttribute('data-horas-disponibles'));
                    
                    if (duracionHoras > horasDisponibles) {
                        duracionInfo.textContent = `Advertencia: La duración (${duracionHoras.toFixed(2)} horas) supera las horas disponibles (${horasDisponibles.toFixed(2)} horas).`;
                        duracionInfo.style.color = 'red';
                        btnRegistrar.disabled = true;
                    } else {
                        duracionInfo.textContent = `Duración: ${duracionHoras.toFixed(2)} horas`;
                        duracionInfo.style.color = 'green';
                        btnRegistrar.disabled = false;
                    }
                } else {
                    duracionInfo.textContent = `Duración: ${duracionHoras.toFixed(2)} horas`;
                    duracionInfo.style.color = 'inherit';
                    btnRegistrar.disabled = false;
                }
            } else {
                duracionInfo.textContent = '';
                btnRegistrar.disabled = false;
            }
        }
        
        horaInicio.addEventListener('change', actualizarDuracion);
        horaFin.addEventListener('change', actualizarDuracion);
        selectPrograma.addEventListener('change', actualizarDuracion);
        
        // Función para formatear fechas en JavaScript
        function formatDateJS(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-ES');
        }
    </script>
</body>
</html>