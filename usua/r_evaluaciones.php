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

// Inicializar evaluaciones como array vacío
$evaluaciones = [];

// Manejar peticiones AJAX para filtrar evaluaciones
if (isset($_POST['ajax_filtrar_evaluaciones'])) {
    $programa_filtro = $_POST['programa_filtro'];
    
    $html = '';
    
    // Solo buscar evaluaciones si hay un programa seleccionado
    if (!empty($programa_filtro)) {
        $sql = "
            SELECT e.id_evaluacion, e.fecha_evaluacion, u.nombre_unidad, p.nombre_materia, u.id_unidad, p.id_programa 
            FROM evaluaciones e
            JOIN unidades u ON e.id_unidad = u.id_unidad
            JOIN programas p ON u.id_programa = p.id_programa
            WHERE e.id_usuario = ? AND p.id_programa = ?
            ORDER BY e.fecha_evaluacion DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_usuario, $programa_filtro]);
        $evaluaciones_filtradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generar HTML de las filas de la tabla
        if (!empty($evaluaciones_filtradas)) {
            foreach ($evaluaciones_filtradas as $e) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($e['nombre_materia']) . '</td>';
                $html .= '<td>' . htmlspecialchars($e['nombre_unidad']) . '</td>';
                $html .= '<td>' . formatDate($e['fecha_evaluacion']) . '</td>';
                $html .= '<td>';
                $html .= '<a href="?eliminar_evaluacion=' . $e['id_evaluacion'] . '" ';
                $html .= 'class="btn btn-danger" ';
                $html .= 'onclick="return confirm(\'¿Está seguro de eliminar esta evaluación?\');" ';
                $html .= 'style="padding: 0.5rem 1rem; font-size: 0.875rem;">';
                $html .= 'Eliminar</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="4" class="no-data" style="text-align: center; padding: 2rem;">No hay evaluaciones registradas para el programa seleccionado.</td></tr>';
        }
    } else {
        // Si no hay programa seleccionado, mostrar mensaje
        $html = '<tr><td colspan="4" class="no-data" style="text-align: center; padding: 2rem;">Selecciona un programa para ver las evaluaciones.</td></tr>';
    }
    
    // Retornar solo el HTML para AJAX
    echo $html;
    exit();
}

// Procesar el registro de evaluaciones
if (isset($_POST['registrar_evaluacion'])) {
    $id_programa = $_POST['programa_evaluacion'];
    $id_unidades = isset($_POST['unidades_evaluacion']) ? $_POST['unidades_evaluacion'] : [];
    $fecha_evaluacion = $_POST['fecha_evaluacion'];
    
    // Validar que no se seleccionen más de 2 unidades
    if (count($id_unidades) > 2) {
        $mensaje = "Solo puedes seleccionar máximo 2 unidades por evaluación.";
        $tipo_mensaje = "danger";
    } else if (empty($id_unidades)) {
        $mensaje = "Debes seleccionar al menos una unidad.";
        $tipo_mensaje = "danger";
    } else {
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
}

// Eliminar evaluación
if (isset($_GET['eliminar_evaluacion']) && is_numeric($_GET['eliminar_evaluacion'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM evaluaciones WHERE id_evaluacion = ? AND id_usuario = ?");
        $stmt->execute([$_GET['eliminar_evaluacion'], $id_usuario]);
        
        $mensaje = "Evaluación eliminada correctamente.";
        $tipo_mensaje = "success";
        
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Evaluaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
                
                semestreInfo.innerHTML = '<strong>Semestre:</strong> ' + semestreNombre + ' <br><strong>Periodo:</strong> ' + 
                                         formatearFecha(fechaInicio) + ' - ' + formatearFecha(fechaFin);
                semestreInfo.style.display = 'block';
                
                // Actualizar los límites de fecha en el selector
                var fechaInput = document.getElementById('fecha-evaluacion');
                fechaInput.min = fechaInicio;
                fechaInput.max = fechaFin;
            } else {
                semestreInfo.style.display = 'none';
            }
        }
        
        function formatearFecha(fechaStr) {
            var fecha = new Date(fechaStr);
            return fecha.getDate() + '/' + (fecha.getMonth() + 1) + '/' + fecha.getFullYear();
        }
        
        // Función para limitar la selección de checkboxes a máximo 2
        function limitarCheckboxes() {
            var checkboxes = document.querySelectorAll('input[name="unidades_evaluacion[]"]');
            var checkedCount = 0;
            
            // Contar cuántos están seleccionados
            checkboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    checkedCount++;
                }
            });
            
            // Si hay más de 2 seleccionados, deshabilitar los no seleccionados
            checkboxes.forEach(function(checkbox) {
                if (!checkbox.checked && checkedCount >= 2) {
                    checkbox.disabled = true;
                } else if (checkedCount < 2) {
                    checkbox.disabled = false;
                }
            });
            
            // Mostrar mensaje de aviso
            var avisoMaximo = document.getElementById('aviso-maximo');
            if (checkedCount >= 2) {
                avisoMaximo.style.display = 'block';
            } else {
                avisoMaximo.style.display = 'none';
            }
        }
        
        // Función para filtrar evaluaciones con AJAX
        function filtrarEvaluaciones() {
            var programaFiltro = document.getElementById('programa-evaluacion').value;
            var tbody = document.querySelector('#tabla-evaluaciones tbody');
            
            // Mostrar indicador de carga
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem;">Cargando...</td></tr>';
            
            // Crear petición AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    tbody.innerHTML = xhr.responseText;
                }
            };
            
            // Enviar datos
            var data = 'ajax_filtrar_evaluaciones=1&programa_filtro=' + encodeURIComponent(programaFiltro);
            xhr.send(data);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="header">
                <h1>Registro de Evaluaciones</h1>
                <p class="subtitle">Gestiona tus evaluaciones académicas</p>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>
            
            <div class="section">
                <h2 class="section-title">Registrar Nueva Evaluación</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Seleccionar Programa:</label>
                        <select name="programa_evaluacion" id="programa-evaluacion" required onchange="cargarUnidades(this.value); filtrarEvaluaciones();">
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
                        
                        <div id="semestre-info" class="semestre-info" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Seleccionar Unidades (máximo 2):</label>
                        <div id="aviso-maximo" style="display: none; color: #e74c3c; font-size: 0.9em; margin-bottom: 10px;">
                            ⚠️ Has alcanzado el límite máximo de 2 unidades por evaluación
                        </div>
                        <?php foreach ($programas as $programa): ?>
                            <div id="unidades-programa-<?php echo $programa['id_programa']; ?>" class="unidades-container" style="display: none;">
                                <?php if (isset($unidades_por_programa[$programa['id_programa']])): ?>
                                    <?php foreach ($unidades_por_programa[$programa['id_programa']] as $unidad): ?>
                                        <label class="unidad-checkbox">
                                            <input type="checkbox" name="unidades_evaluacion[]" value="<?php echo $unidad['id_unidad']; ?>" onchange="limitarCheckboxes()">
                                            <span>Unidad <?php echo $unidad['numero_unidad']; ?>: <?php echo htmlspecialchars($unidad['nombre_unidad']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-data">No hay unidades disponibles para este programa.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de evaluación:</label>
                        <input type="date" name="fecha_evaluacion" id="fecha-evaluacion" required 
                               min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>">
                    </div>
                    
                    <button type="submit" name="registrar_evaluacion" class="btn">Registrar Evaluación</button>
                </form>
            </div>
            
            <div class="section">
                <h2 class="section-title">Evaluaciones Registradas</h2>
                <p style="margin-bottom: 15px; color: #666; font-size: 0.9em;">
                    💡 Selecciona un programa arriba para filtrar las evaluaciones
                </p>
                
                <div class="table-container">
                    <table id="tabla-evaluaciones">
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>Unidad</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="no-data" style="text-align: center; padding: 2rem;">
                                    Selecciona un programa para ver las evaluaciones.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="nav-buttons">
                <a href="r_planificacion.php">Ir a Planificación</a>
                <a href="r_disponibilidad.php">Ir a Disponibilidad</a>
                <a href="gestion_dosificacion.php">Ir a Reportes</a>
                <a href="p_usuario.php">Ir a Inicio</a>
            </div>
        </div>
    </div>
</body>
</html>