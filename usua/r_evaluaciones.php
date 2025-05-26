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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Evaluaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .section {
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        select, input[type="date"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s ease;
            background: white;
        }

        select:focus, input[type="date"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .semestre-info {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-top: 1rem;
            font-weight: 500;
            color: #374151;
            border-left: 4px solid #667eea;
        }

        .unidades-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid #e5e7eb;
        }

        .unidad-checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .unidad-checkbox:hover {
            background: #f1f5f9;
            border-color: #667eea;
        }

        input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px -4px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .btn-danger:hover {
            box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.4);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #4b5563;
        }

        tr:hover {
            background: #fafbfc;
        }

        .mensaje {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 4px solid;
        }

        .mensaje.success {
            background: #dcfce7;
            color: #166534;
            border-color: #22c55e;
        }

        .mensaje.danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: #ef4444;
        }

        .nav-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .nav-buttons a {
            background: rgba(255, 255, 255, 0.9);
            color: #374151;
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .nav-buttons a:hover {
            background: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
            font-style: italic;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }
            
            .main-card {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
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
                        
                        <div id="semestre-info" class="semestre-info" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Seleccionar Unidades:</label>
                        <?php foreach ($programas as $programa): ?>
                            <div id="unidades-programa-<?php echo $programa['id_programa']; ?>" class="unidades-container" style="display: none;">
                                <?php if (isset($unidades_por_programa[$programa['id_programa']])): ?>
                                    <?php foreach ($unidades_por_programa[$programa['id_programa']] as $unidad): ?>
                                        <label class="unidad-checkbox">
                                            <input type="checkbox" name="unidades_evaluacion[]" value="<?php echo $unidad['id_unidad']; ?>">
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
                <?php if (!empty($evaluaciones)): ?>
                    <div class="table-container">
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
                                            <a href="?eliminar_evaluacion=<?php echo $e['id_evaluacion']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar esta evaluación?');" 
                                               style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                               Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">No hay evaluaciones registradas.</div>
                <?php endif; ?>
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