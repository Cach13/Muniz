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

// Obtener semestres activos
$stmt = $conn->query("SELECT id_semestre, nombre, fecha_inicio, fecha_fin FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener días no hábiles
$stmt = $conn->query("SELECT fecha, descripcion FROM diasnohabiles WHERE id_semestre IN (SELECT id_semestre FROM semestres)");
$dias_no_habiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener períodos de vacaciones
$stmt = $conn->query("SELECT fecha_inicio, fecha_fin, descripcion FROM vacaciones WHERE id_semestre IN (SELECT id_semestre FROM semestres)");
$periodos_vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Calcular horas usadas por programa para mostrar disponibilidad
$horas_usadas_por_programa = [];
foreach ($programas as $programa) {
    $stmt = $conn->prepare("
        SELECT SUM(
            TIMESTAMPDIFF(SECOND, 
                CONCAT(d.fecha, ' ', d.hora_inicio), 
                CONCAT(d.fecha, ' ', d.hora_fin)
            ) / 3600
        ) as total_horas
        FROM disponibilidad d
        JOIN temas t ON d.id_tema = t.id_tema
        JOIN unidades u ON t.id_unidad = u.id_unidad
        WHERE u.id_programa = ? AND d.id_usuario = ?
    ");
    $stmt->execute([$programa['id_programa'], $id_usuario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $horas_usadas_por_programa[$programa['id_programa']] = $resultado['total_horas'] ?? 0;
}

// Función para obtener horas planificadas y usadas por tema
function obtenerHorasTema($conn, $id_tema, $id_usuario) {
    // Obtener horas planificadas para el tema
    $stmt = $conn->prepare("SELECT SUM(horas_planeadas) as horas_planificadas FROM planificacionusuario WHERE id_tema = ? AND id_usuario = ?");
    $stmt->execute([$id_tema, $id_usuario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $horas_planificadas = $resultado['horas_planificadas'] ?? 0;
    
    // Obtener horas ya usadas en disponibilidad para el tema
    $stmt = $conn->prepare("
        SELECT SUM(
            TIMESTAMPDIFF(SECOND, 
                CONCAT(fecha, ' ', hora_inicio), 
                CONCAT(fecha, ' ', hora_fin)
            ) / 3600
        ) as horas_usadas
        FROM disponibilidad 
        WHERE id_tema = ? AND id_usuario = ?
    ");
    $stmt->execute([$id_tema, $id_usuario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $horas_usadas = $resultado['horas_usadas'] ?? 0;
    
    return [
        'planificadas' => $horas_planificadas,
        'usadas' => $horas_usadas,
        'disponibles' => $horas_planificadas - $horas_usadas
    ];
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
        // Validar que no sea fin de semana
        else if (date('w', strtotime($fecha)) == 0 || date('w', strtotime($fecha)) == 6) {
            $mensaje = "No se puede registrar disponibilidad en fines de semana (sábado o domingo).";
            $tipo_mensaje = "warning";
        }
        // Validar que no sea día no hábil
        else {
            $stmt = $conn->prepare("SELECT descripcion FROM diasnohabiles WHERE fecha = ? AND id_semestre = ?");
            $stmt->execute([$fecha, $programa_seleccionado['id_semestre']]);
            $dia_no_habil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dia_no_habil) {
                $mensaje = "No se puede registrar disponibilidad en días no hábiles (" . $dia_no_habil['descripcion'] . ").";
                $tipo_mensaje = "warning";
            }
            // Validar que no esté en período de vacaciones
            else {
                $stmt = $conn->prepare("SELECT descripcion FROM vacaciones WHERE ? BETWEEN fecha_inicio AND fecha_fin AND id_semestre = ?");
                $stmt->execute([$fecha, $programa_seleccionado['id_semestre']]);
                $vacacion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($vacacion) {
                    $mensaje = "No se puede registrar disponibilidad durante períodos de vacaciones (" . $vacacion['descripcion'] . ").";
                    $tipo_mensaje = "warning";
                }
                // Validar que la hora de inicio sea menor que la hora de fin
                else if ($hora_inicio >= $hora_fin) {
                    $mensaje = "La hora de inicio debe ser menor que la hora de fin.";
                    $tipo_mensaje = "warning";
                } 
                else {
                    // Calcular duración de la nueva disponibilidad
                    $inicio_timestamp = strtotime("$fecha $hora_inicio");
                    $fin_timestamp = strtotime("$fecha $hora_fin");
                    $nueva_duracion_horas = ($fin_timestamp - $inicio_timestamp) / 3600;
                    
                    // Obtener información del tema seleccionado
                    $info_tema = obtenerHorasTema($conn, $id_tema, $id_usuario);
                    
                    // Validar que el tema tenga horas planificadas
                    if ($info_tema['planificadas'] <= 0) {
                        $mensaje = "No hay horas planificadas para este tema. Debe primero planificar las horas en la sección de planificación.";
                        $tipo_mensaje = "warning";
                    }
                    // Validar que no exceda las horas planificadas para el tema
                    else if (($info_tema['usadas'] + $nueva_duracion_horas) > $info_tema['planificadas']) {
                        $mensaje = "El total de horas de disponibilidad para este tema (" . number_format($info_tema['usadas'] + $nueva_duracion_horas, 2) . ") supera las horas planificadas (" . number_format($info_tema['planificadas'], 2) . "). Horas disponibles: " . number_format($info_tema['disponibles'], 2);
                        $tipo_mensaje = "warning";
                    }
                    else {
                        // Calcular horas de disponibilidad actuales para el programa completo
                        $horas_disponibilidad_actual = $horas_usadas_por_programa[$id_programa];
                        
                        // Validar que no exceda el total de horas del programa
                        if (($horas_disponibilidad_actual + $nueva_duracion_horas) > $horas_totales_programa) {
                            $mensaje = "El total de horas de disponibilidad del programa (" . number_format($horas_disponibilidad_actual + $nueva_duracion_horas, 2) . ") supera el límite de horas del programa (" . $horas_totales_programa . ").";
                            $tipo_mensaje = "warning";
                        } else {
                            try {
                                $stmt = $conn->prepare("INSERT INTO disponibilidad (id_usuario, fecha, hora_inicio, hora_fin, id_tema) VALUES (?, ?, ?, ?, ?)");
                                $stmt->execute([$id_usuario, $fecha, $hora_inicio, $hora_fin, $id_tema]);
                                
                                $mensaje = "Disponibilidad registrada correctamente. Horas restantes para este tema: " . number_format($info_tema['disponibles'] - $nueva_duracion_horas, 2);
                                $tipo_mensaje = "success";
                                
                                // Actualizar horas usadas después de registrar
                                $horas_usadas_por_programa[$id_programa] += $nueva_duracion_horas;
                            } catch (PDOException $e) {
                                $mensaje = "Error al registrar la disponibilidad: " . $e->getMessage();
                                $tipo_mensaje = "danger";
                            }
                        }
                    }
                }
            }
        }
    }
}

// Función para formatear la fecha
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Obtener información de horas planificadas por tema para JavaScript
$horas_planificadas_por_tema = [];
foreach ($programas as $programa) {
    if (isset($unidades_por_programa[$programa['id_programa']])) {
        foreach ($unidades_por_programa[$programa['id_programa']] as $unidad) {
            if (isset($temas_por_unidad[$unidad['id_unidad']])) {
                foreach ($temas_por_unidad[$unidad['id_unidad']] as $tema) {
                    $info_tema = obtenerHorasTema($conn, $tema['id_tema'], $id_usuario);
                    $horas_planificadas_por_tema[$tema['id_tema']] = [
                        'planificadas' => $info_tema['planificadas'],
                        'usadas' => $info_tema['usadas'],
                        'disponibles' => $info_tema['disponibles']
                    ];
                }
            }
        }
    }
}

// Preparar datos de días no hábiles y vacaciones para JavaScript
$dias_no_habiles_js = array_column($dias_no_habiles, 'fecha');
$vacaciones_js = [];
foreach ($periodos_vacaciones as $vacacion) {
    $vacaciones_js[] = [
        'inicio' => $vacacion['fecha_inicio'],
        'fin' => $vacacion['fecha_fin'],
        'descripcion' => $vacacion['descripcion']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Disponibilidad</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <h2>Registrar Nueva Disponibilidad</h2>
    
    <?php if ($mensaje): ?>
    <div class="mensaje <?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <div class="form-section">
        <h3>Registrar Disponibilidad</h3>
        <form method="POST">
            <label>Programa:</label>
            <select name="id_programa" id="select-programa" required>
                <option value="">Seleccione un programa</option>
                <?php foreach ($programas as $programa): ?>
                    <?php
                    $total_horas = $programa['horas_teoricas'] + $programa['horas_practicas'];
                    $horas_usadas = $horas_usadas_por_programa[$programa['id_programa']] ?? 0;
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
            
            <div class="tema-info" id="tema-info" style="margin-top: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; display: none;">
                <strong>Información del tema:</strong>
                <div id="tema-horas-detalle"></div>
            </div>
            
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
    
    <div class="nav-buttons">
        <a href="ver_disponibilidad.php" class="btn-secondary">Ver Disponibilidad Programada</a>
        <a href="r_planificacion.php">Ir a Planificación</a>
        <a href="r_evaluaciones.php">Ir a Evaluaciones</a>
        <a href="gestion_dosificacion.php">Ir a Reportes</a>
        <a href="p_usuario.php">Ir a Inicio</a>
    </div>
    
    <script>
        // Datos de programas, unidades y temas
        const unidadesPorPrograma = <?php echo json_encode($unidades_por_programa); ?>;
        const temasPorUnidad = <?php echo json_encode($temas_por_unidad); ?>;
        const horasPlanificadasPorTema = <?php echo json_encode($horas_planificadas_por_tema); ?>;
        const diasNoHabiles = <?php echo json_encode($dias_no_habiles_js); ?>;
        const periodosVacaciones = <?php echo json_encode($vacaciones_js); ?>;
        
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
        const temaInfo = document.getElementById('tema-info');
        const temaHorasDetalle = document.getElementById('tema-horas-detalle');
        
        // Función para verificar si una fecha es fin de semana
        function esFinDeSemana(fecha) {
            const dia = fecha.getDay();
            return dia === 0 || dia === 6; // 0 es domingo, 6 es sábado
        }
        
        // Función para verificar si una fecha es día no hábil
        function esDiaNoHabil(fecha) {
            const fechaStr = fecha.toISOString().split('T')[0];
            return diasNoHabiles.includes(fechaStr);
        }
        
        // Función para verificar si una fecha está en período de vacaciones
        function estaEnVacaciones(fecha) {
            const fechaStr = fecha.toISOString().split('T')[0];
            for (const periodo of periodosVacaciones) {
                if (fechaStr >= periodo.inicio && fechaStr <= periodo.fin) {
                    return periodo;
                }
            }
            return null;
        }
        
        // Función para mostrar advertencias de días no hábiles
        function verificarDiaNoHabil(fechaInput) {
            const fecha = new Date(fechaInput.value);
            let mensaje = '';
            
            if (esFinDeSemana(fecha)) {
                mensaje = '⚠️ Advertencia: Has seleccionado un fin de semana (sábado o domingo).';
            } else if (esDiaNoHabil(fecha)) {
                mensaje = '⚠️ Advertencia: Has seleccionado un día feriado no hábil.';
            } else {
                const vacacion = estaEnVacaciones(fecha);
                if (vacacion) {
                    mensaje = `⚠️ Advertencia: Has seleccionado un día dentro del período de vacaciones (${vacacion.descripcion}).`;
                }
            }
            
            if (mensaje) {
                const advertencia = document.createElement('div');
                advertencia.className = 'mensaje-advertencia';
                advertencia.style.color = 'orange';
                advertencia.style.marginTop = '5px';
                advertencia.textContent = mensaje;
                
                // Eliminar advertencia anterior si existe
                const anterior = document.getElementById('advertencia-dia');
                if (anterior) anterior.remove();
                
                advertencia.id = 'advertencia-dia';
                fechaInput.parentNode.insertBefore(advertencia, fechaInput.nextSibling);
            } else {
                const anterior = document.getElementById('advertencia-dia');
                if (anterior) anterior.remove();
            }
        }
        
        // Evento: Cambio de programa
        selectPrograma.addEventListener('change', function() {
            const programaId = this.value;
            
            // Limpiar y actualizar unidades
            selectUnidad.innerHTML = '<option value="">Seleccione una unidad</option>';
            selectTema.innerHTML = '<option value="">Primero seleccione una unidad</option>';
            temaInfo.style.display = 'none';
            
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
            temaInfo.style.display = 'none';
            
            if (unidadId && temasPorUnidad[unidadId]) {
                temasPorUnidad[unidadId].forEach(tema => {
                    const option = document.createElement('option');
                    option.value = tema.id_tema;
                    option.textContent = tema.nombre_tema;
                    selectTema.appendChild(option);
                });
            }
        });
        
        // Evento: Cambio de tema
        selectTema.addEventListener('change', function() {
            const temaId = this.value;
            
            if (temaId && horasPlanificadasPorTema[temaId]) {
                const info = horasPlanificadasPorTema[temaId];
                temaHorasDetalle.innerHTML = `
                    <div>Horas planificadas: ${parseFloat(info.planificadas).toFixed(2)}</div>
                    <div>Horas ya usadas: ${parseFloat(info.usadas).toFixed(2)}</div>
                    <div>Horas disponibles: <strong>${parseFloat(info.disponibles).toFixed(2)}</strong></div>
                `;
                temaInfo.style.display = 'block';
                
                if (info.disponibles <= 0) {
                    temaHorasDetalle.innerHTML += '<div style="color: red; font-weight: bold;">⚠️ No hay horas disponibles para este tema</div>';
                }
            } else {
                temaInfo.style.display = 'none';
            }
            
            actualizarDuracion();
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
                
                // Verificar disponibilidad del tema
                const temaId = selectTema.value;
                if (temaId && horasPlanificadasPorTema[temaId]) {
                    const info = horasPlanificadasPorTema[temaId];
                    
                    if (info.planificadas <= 0) {
                        duracionInfo.innerHTML = `<span style="color: red;">Error: No hay horas planificadas para este tema.</span>`;
                        btnRegistrar.disabled = true;
                        return;
                    }
                    
                    if (duracionHoras > info.disponibles) {
                        duracionInfo.innerHTML = `
                            <span style="color: red;">Advertencia: La duración (${duracionHoras.toFixed(2)} horas) supera las horas disponibles del tema (${parseFloat(info.disponibles).toFixed(2)} horas).</span>
                        `;
                        btnRegistrar.disabled = true;
                        return;
                    }
                }
                
                // Verificar si hay suficientes horas disponibles en el programa
                const programaId = selectPrograma.value;
                if (programaId) {
                    const horasDisponibles = parseFloat(selectPrograma.options[selectPrograma.selectedIndex].getAttribute('data-horas-disponibles'));
                    
                    if (duracionHoras > horasDisponibles) {
                        duracionInfo.textContent = `Advertencia: La duración (${duracionHoras.toFixed(2)} horas) supera las horas disponibles del programa (${horasDisponibles.toFixed(2)} horas).`;
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
        
        // Eventos para actualizar duración
        horaInicio.addEventListener('change', actualizarDuracion);
        horaFin.addEventListener('change', actualizarDuracion);
        selectPrograma.addEventListener('change', actualizarDuracion);
        
        // Evento para verificar días no hábiles
        fechaDisponibilidad.addEventListener('change', function() {
            verificarDiaNoHabil(this);
            actualizarDuracion();
        });
        
        // Función para formatear fechas en JavaScript
        function formatDateJS(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-ES');
        }
    </script>
</body>
</html>