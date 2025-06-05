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

// Obtener los semestres activos
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

// Unidades y temas del programa seleccionado
$unidades = [];
$temas = [];
$horas_totales_programa = 0;
$id_programa_seleccionado = 0;

// Procesar el formulario para seleccionar un programa
if (isset($_POST['programa_seleccionado'])) {
    $id_programa = $_POST['programa_seleccionado'];
    $id_programa_seleccionado = $id_programa;
    
    // Obtener horas totales del programa seleccionado
    foreach ($programas as $programa) {
        if ($programa['id_programa'] == $id_programa) {
            $horas_totales_programa = $programa['horas_teoricas'] + $programa['horas_practicas'];
            break;
        }
    }
    
    // Obtener unidades del programa seleccionado
    $stmt = $conn->prepare("SELECT id_unidad, nombre_unidad, numero_unidad FROM unidades WHERE id_programa = ? ORDER BY numero_unidad");
    $stmt->execute([$id_programa]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener temas para todas las unidades del programa
    if (!empty($unidades)) {
        $unidad_ids = array_column($unidades, 'id_unidad');
        $placeholders = implode(',', array_fill(0, count($unidad_ids), '?'));
        
        $stmt = $conn->prepare("SELECT id_tema, id_unidad, nombre_tema FROM temas WHERE id_unidad IN ($placeholders)");
        $stmt->execute($unidad_ids);
        $todos_temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar temas por unidad
        foreach ($todos_temas as $tema) {
            $temas[$tema['id_unidad']][] = $tema;
        }
    }
}

// Procesar el registro de horas estimadas para los temas
if (isset($_POST['registrar_horas'])) {
    try {
        $conn->beginTransaction();
        
        // Calcular la suma total de horas planeadas
        $suma_horas_planeadas = 0;
        foreach ($_POST['tema'] as $id_tema => $horas) {
            if (!empty($horas) && is_numeric($horas)) {
                $suma_horas_planeadas += floatval($horas);
            }
        }
        
        // Validar que no exceda las horas totales del programa
        if ($suma_horas_planeadas > $horas_totales_programa) {
            throw new Exception("Las horas totales planeadas ($suma_horas_planeadas) exceden el límite permitido para este programa ($horas_totales_programa horas). Por favor, ajuste su planificación.");
        }
        
        foreach ($_POST['tema'] as $id_tema => $horas) {
            if (!empty($horas) && is_numeric($horas)) {
                // Verificar si ya existe una planificación para este tema y usuario
                $stmt = $conn->prepare("SELECT id_planificacion FROM planificacionusuario WHERE id_usuario = ? AND id_tema = ?");
                $stmt->execute([$id_usuario, $id_tema]);
                $planificacion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $fecha_actual = date('Y-m-d');
                
                if ($planificacion) {
                    // Actualizar la planificación existente
                    $stmt = $conn->prepare("UPDATE planificacionusuario SET horas_planeadas = ?, fecha = ? WHERE id_planificacion = ?");
                    $stmt->execute([$horas, $fecha_actual, $planificacion['id_planificacion']]);
                } else {
                    // Crear nueva planificación
                    $stmt = $conn->prepare("INSERT INTO planificacionusuario (id_usuario, id_tema, fecha, horas_planeadas) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id_usuario, $id_tema, $fecha_actual, $horas]);
                }
            }
        }
        
        $conn->commit();
        $mensaje = "Horas estimadas registradas correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener las planificaciones del alumno para cargar las horas ya registradas
$planificaciones = [];
if (!empty($temas)) {
    $tema_ids = [];
    foreach ($temas as $unidad_temas) {
        foreach ($unidad_temas as $tema) {
            $tema_ids[] = $tema['id_tema'];
        }
    }
    
    if (!empty($tema_ids)) {
        $placeholders = implode(',', array_fill(0, count($tema_ids), '?'));
        $params = array_merge([$id_usuario], $tema_ids);
        
        $stmt = $conn->prepare("
            SELECT id_tema, horas_planeadas 
            FROM planificacionusuario 
            WHERE id_usuario = ? AND id_tema IN ($placeholders)
        ");
        $stmt->execute($params);
        $planificacion_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($planificacion_results as $plan) {
            $planificaciones[$plan['id_tema']] = $plan['horas_planeadas'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planificación de Estudios</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="header">
                <h2>Planificación de Estudios</h2>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>
            
            <!-- Contenedor para selección de programa -->
            <div class="programa-container">
                <h3>Seleccionar Programa</h3>
                <form method="POST">
                    <select name="programa_seleccionado" required>
                        <option value="">Seleccione un programa</option>
                        <?php foreach ($programas as $programa): ?>
                            <option value="<?php echo $programa['id_programa']; ?>"
                                <?php echo (isset($_POST['programa_seleccionado']) && $_POST['programa_seleccionado'] == $programa['id_programa']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($programa['nombre_materia']); ?>
                                (<?php echo $programa['horas_teoricas'] + $programa['horas_practicas']; ?> horas)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="ver_programa">Ver Programa</button>
                </form>
            </div>
            
            <?php if (!empty($unidades)): ?>
                <!-- Contenedor para definir horas estimadas -->
                <div class="horas-container">
                    <h3>Definir Horas Estimadas</h3>
                    <?php if ($horas_totales_programa > 0): ?>
                        <div class="info-horas">
                            <p>Horas totales para este programa: <strong><?php echo $horas_totales_programa; ?> horas</strong></p>
                            <p>Las horas planeadas para todos los temas no deben exceder este límite.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="form-planificacion">
                        <input type="hidden" name="programa_seleccionado" value="<?php echo $id_programa_seleccionado; ?>">
                        
                        <?php 
                        // Variable para calcular la suma actual de horas planeadas
                        $suma_actual = 0;
                        foreach ($planificaciones as $horas) {
                            $suma_actual += floatval($horas);
                        }
                        ?>
                        
                        <div class="contador-horas">
                            <p>Horas planeadas: <span id="horas-actuales"><?php echo $suma_actual; ?></span> de <?php echo $horas_totales_programa; ?></p>
                        </div>
                        
                        <?php foreach ($unidades as $unidad): ?>
                            <div class="unidad-header">Unidad <?php echo $unidad['numero_unidad']; ?>: <?php echo htmlspecialchars($unidad['nombre_unidad']); ?></div>
                            <?php if (isset($temas[$unidad['id_unidad']])): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Tema</th>
                                                <th>Horas Estimadas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($temas[$unidad['id_unidad']] as $tema): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($tema['nombre_tema']); ?></td>
                                                    <td>
                                                        <input type="number" class="horas-input" name="tema[<?php echo $tema['id_tema']; ?>]" min="0" step="0.5" 
                                                            value="<?php echo isset($planificaciones[$tema['id_tema']]) ? $planificaciones[$tema['id_tema']] : ''; ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No hay temas registrados para esta unidad.</p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <button type="submit" name="registrar_horas">Guardar Horas Estimadas</button>
                    </form>
                </div>
                
                <script>
                    // JavaScript para actualizar dinámicamente el contador de horas
                    document.addEventListener('DOMContentLoaded', function() {
                        const horasInputs = document.querySelectorAll('.horas-input');
                        const horasActualesSpan = document.getElementById('horas-actuales');
                        const horasTotales = <?php echo $horas_totales_programa; ?>;
                        const formPlanificacion = document.getElementById('form-planificacion');
                        
                        horasInputs.forEach(input => {
                            input.addEventListener('input', actualizarContadorHoras);
                        });
                        
                        formPlanificacion.addEventListener('submit', function(e) {
                            const totalHoras = calcularTotalHoras();
                            if (totalHoras > horasTotales) {
                                e.preventDefault();
                                alert('Error: Las horas totales planeadas (' + totalHoras + ') exceden el límite permitido para este programa (' + horasTotales + ' horas). Por favor, ajuste su planificación.');
                            }
                        });
                        
                        function actualizarContadorHoras() {
                            const totalHoras = calcularTotalHoras();
                            horasActualesSpan.textContent = totalHoras;
                            
                            // Cambiar color si excede el límite
                            if (totalHoras > horasTotales) {
                                horasActualesSpan.style.color = 'red';
                            } else {
                                horasActualesSpan.style.color = 'inherit';
                            }
                        }
                        
                        function calcularTotalHoras() {
                            let total = 0;
                            horasInputs.forEach(input => {
                                if (input.value && !isNaN(input.value)) {
                                    total += parseFloat(input.value);
                                }
                            });
                            return total;
                        }
                    });
                </script>
            <?php elseif (isset($_POST['ver_programa'])): ?>
                <div class="planificacion-container">
                    <p class="no-data">No hay unidades disponibles para este programa.</p>
                </div>
            <?php endif; ?>
            
            <div class="nav-buttons">
                <a href="r_disponibilidad.php">Ir a Disponibilidad</a>
                <a href="ver_disponibilidad.php" class="btn-secondary">Ver Disponibilidad Programada</a>
                <a href="r_evaluaciones.php">Ir a Evaluaciones</a>
                <a href="gestion_dosificacion.php">Ir a Reportes</a>
                <a href="p_usuario.php">Ir a Inicio</a>
            </div>
        </div>
    </div>
</body>
</html>