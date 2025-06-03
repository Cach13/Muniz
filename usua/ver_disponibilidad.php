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
    <title>Ver Disponibilidad Programada</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Disponibilidad Programada</h2>
    
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
    
    <div class="disponibilidad-container">
        <h3>Disponibilidad Registrada</h3>
        <div id="filtro-programa-container">
            <label>Filtrar por programa:</label>
            <select id="filtro-programa">
                <option value="" selected>Seleccionar programa</option>
                <option value="todos">Todos los programas</option>
                <?php foreach ($programas as $programa): ?>
                    <option value="<?php echo $programa['id_programa']; ?>">
                        <?php echo htmlspecialchars($programa['nombre_materia']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="tabla-disponibilidad">
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
                            <tr data-programa-id="<?php echo $d['id_programa'] ?? ''; ?>" style="display: none;">
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
                <div id="mensaje-seleccionar" style="display: block; text-align: center; padding: 20px; color: #666;">
                    Seleccione un programa para ver la disponibilidad registrada.
                </div>
            <?php else: ?>
                <p>No hay disponibilidad registrada.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="r_disponibilidad.php" class="btn-primary">Registrar Nueva Disponibilidad</a>
        <a href="r_planificacion.php">Ir a Planificación</a>
        <a href="r_evaluaciones.php">Ir a Evaluaciones</a>
        <a href="gestion_dosificacion.php">Ir a Reportes</a>
        <a href="p_usuario.php">Ir a Inicio</a>
    </div>
    
    <script>
        const filtroPrograma = document.getElementById('filtro-programa');
        const mensajeSeleccionar = document.getElementById('mensaje-seleccionar');
        const tabla = document.querySelector('#tabla-disponibilidad table');
        
        // Función para filtrar la tabla de disponibilidad
        function filtrarTablaDisponibilidad() {
            const programaSeleccionado = filtroPrograma.value;
            const filas = document.querySelectorAll('#tabla-disponibilidad tbody tr');
            
            // Si no hay programa seleccionado (opción por defecto)
            if (programaSeleccionado === '') {
                filas.forEach(fila => {
                    fila.style.display = 'none';
                });
                if (mensajeSeleccionar) {
                    mensajeSeleccionar.style.display = 'block';
                }
                if (tabla && tabla.querySelector('thead')) {
                    tabla.querySelector('thead').style.display = 'none';
                }
                return;
            }
            
            // Ocultar mensaje y mostrar encabezados de tabla
            if (mensajeSeleccionar) {
                mensajeSeleccionar.style.display = 'none';
            }
            if (tabla && tabla.querySelector('thead')) {
                tabla.querySelector('thead').style.display = '';
            }
            
            // Mostrar todas las filas si se selecciona "Todos los programas"
            if (programaSeleccionado === 'todos') {
                filas.forEach(fila => {
                    fila.style.display = '';
                });
                return;
            }
            
            // Filtrar por programa específico
            filas.forEach(fila => {
                const programaId = fila.getAttribute('data-programa-id');
                
                if (programaSeleccionado === programaId) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }
        
        // Evento: Cambio en el filtro de programa
        filtroPrograma.addEventListener('change', filtrarTablaDisponibilidad);
        
        // Ejecutar filtro al cargar la página para establecer estado inicial
        document.addEventListener('DOMContentLoaded', function() {
            filtrarTablaDisponibilidad();
        });
    </script>
</body>
</html>