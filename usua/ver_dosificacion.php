<?php
require_once '../config.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'alumno') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Verificar si se proporcionó un ID de programa
if (!isset($_GET['programa_id']) || empty($_GET['programa_id'])) {
    echo "Se requiere un ID de programa";
    exit();
}

$programa_id = intval($_GET['programa_id']);

// Verificar si el usuario tiene acceso al programa
$stmt = $conn->prepare("
    SELECT DISTINCT p.id_programa, p.nombre_materia, s.nombre as nombre_semestre, 
                    s.fecha_inicio, s.fecha_fin
    FROM programas p
    JOIN semestres s ON p.id_semestre = s.id_semestre
    JOIN unidades u ON p.id_programa = u.id_programa
    JOIN temas t ON u.id_unidad = t.id_unidad
    JOIN planificacionusuario pu ON t.id_tema = pu.id_tema
    WHERE pu.id_usuario = ? AND p.id_programa = ?
");
$stmt->execute([$user_id, $programa_id]);
$programa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$programa) {
    echo "No tienes acceso a este programa o el programa no existe";
    exit();
}

// Obtener las unidades del programa
$stmt = $conn->prepare("
    SELECT id_unidad, nombre_unidad, numero_unidad 
    FROM unidades 
    WHERE id_programa = ? 
    ORDER BY numero_unidad
");
$stmt->execute([$programa_id]);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información sobre días no hábiles
$stmt = $conn->prepare("
    SELECT d.fecha, d.descripcion
    FROM diasnohabiles d
    JOIN semestres s ON d.id_semestre = s.id_semestre
    JOIN programas p ON s.id_semestre = p.id_semestre
    WHERE p.id_programa = ?
    ORDER BY d.fecha
");
$stmt->execute([$programa_id]);
$dias_no_habiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información sobre vacaciones
$stmt = $conn->prepare("
    SELECT v.fecha_inicio, v.fecha_fin, v.descripcion
    FROM vacaciones v
    JOIN semestres s ON v.id_semestre = s.id_semestre
    JOIN programas p ON s.id_semestre = p.id_semestre
    WHERE p.id_programa = ?
    ORDER BY v.fecha_inicio
");
$stmt->execute([$programa_id]);
$vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información sobre evaluaciones
$stmt = $conn->prepare("
    SELECT e.fecha_evaluacion, u.nombre_unidad
    FROM evaluaciones e
    JOIN unidades u ON e.id_unidad = u.id_unidad
    WHERE u.id_programa = ? AND e.id_usuario = ?
    ORDER BY e.fecha_evaluacion
");
$stmt->execute([$programa_id, $user_id]);
$evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Dosificación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Reporte de Dosificación</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2><?= htmlspecialchars($programa['nombre_materia']) ?></h2>
                <p class="mb-0"><?= htmlspecialchars($programa['nombre_semestre']) ?> 
                   (<?= htmlspecialchars($programa['fecha_inicio']) ?> - <?= htmlspecialchars($programa['fecha_fin']) ?>)</p>
            </div>
            
            <!-- Información de días especiales -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h4>Días No Hábiles</h4>
                        <?php if (count($dias_no_habiles) > 0): ?>
                            <ul class="list-group">
                                <?php foreach ($dias_no_habiles as $dia): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= date('d/m/Y', strtotime($dia['fecha'])) ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($dia['descripcion']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay días no hábiles registrados.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <h4>Vacaciones</h4>
                        <?php if (count($vacaciones) > 0): ?>
                            <ul class="list-group">
                                <?php foreach ($vacaciones as $vacacion): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= date('d/m/Y', strtotime($vacacion['fecha_inicio'])) ?> - 
                                        <?= date('d/m/Y', strtotime($vacacion['fecha_fin'])) ?>
                                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($vacacion['descripcion']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay periodos de vacaciones registrados.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <h4>Evaluaciones</h4>
                        <?php if (count($evaluaciones) > 0): ?>
                            <ul class="list-group">
                                <?php foreach ($evaluaciones as $evaluacion): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= date('d/m/Y', strtotime($evaluacion['fecha_evaluacion'])) ?>
                                        <span class="badge bg-danger"><?= htmlspecialchars($evaluacion['nombre_unidad']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay evaluaciones registradas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumen general del programa -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h3>Resumen general del programa</h3>
            </div>
            <div class="card-body">
                <?php
                // Obtener totales generales
                $stmt = $conn->prepare("
                    SELECT 
                        SUM(d.horas_planeadas) as total_horas_planeadas,
                        SUM(d.horas_asignadas) as total_horas_asignadas
                    FROM dosificacion d
                    JOIN temas t ON d.id_tema = t.id_tema
                    JOIN unidades u ON t.id_unidad = u.id_unidad
                    WHERE u.id_programa = ?
                ");
                $stmt->execute([$programa_id]);
                $totales_programa = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $total_planeadas = $totales_programa['total_horas_planeadas'] ?? 0;
                $total_asignadas = $totales_programa['total_horas_asignadas'] ?? 0;
                $total_perdidas = $total_planeadas - $total_asignadas;
                $porcentaje_perdida = $total_planeadas > 0 ? ($total_perdidas / $total_planeadas) * 100 : 0;
                ?>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total horas planeadas</h5>
                                <p class="card-text display-6"><?= number_format($total_planeadas, 2) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total horas efectivas</h5>
                                <p class="card-text display-6"><?= number_format($total_asignadas, 2) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total horas perdidas</h5>
                                <p class="card-text display-6 text-danger"><?= number_format($total_perdidas, 2) ?></p>
                                <p class="card-text">(<?= number_format($porcentaje_perdida, 1) ?>% del total)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dosificación por unidades y temas -->
        <?php foreach ($unidades as $unidad): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h3>Unidad <?= $unidad['numero_unidad'] ?>: <?= htmlspecialchars($unidad['nombre_unidad']) ?></h3>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener temas de la unidad
                    $stmt = $conn->prepare("
                        SELECT id_tema, nombre_tema 
                        FROM temas 
                        WHERE id_unidad = ? 
                        ORDER BY id_tema
                    ");
                    $stmt->execute([$unidad['id_unidad']]);
                    $temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($temas) > 0): 
                        foreach ($temas as $tema):
                            // Obtener dosificación calculada para este tema
                            $stmt = $conn->prepare("
                                SELECT fecha, horas_planeadas, horas_asignadas, motivo_reduccion 
                                FROM dosificacion 
                                WHERE id_tema = ? 
                                ORDER BY fecha
                            ");
                            $stmt->execute([$tema['id_tema']]);
                            $dosificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Calcular totales del tema
                            $total_horas_planeadas = 0;
                            $total_horas_asignadas = 0;
                            foreach ($dosificacion as $d) {
                                $total_horas_planeadas += floatval($d['horas_planeadas']);
                                $total_horas_asignadas += floatval($d['horas_asignadas']);
                            }
                            $total_horas_perdidas = $total_horas_planeadas - $total_horas_asignadas;
                    ?>
                            <div class="mb-4">
                                <h4><?= htmlspecialchars($tema['nombre_tema']) ?></h4>
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Horas planificadas originalmente:</strong> <?= number_format($total_horas_planeadas, 2) ?> horas
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Horas dosificadas efectivas:</strong> <?= number_format($total_horas_asignadas, 2) ?> horas
                                        </div>
                                        <div class="col-md-4">
                                            <?php if ($total_horas_perdidas > 0): ?>
                                                <strong class="text-danger">Horas perdidas:</strong> <?= number_format($total_horas_perdidas, 2) ?> horas
                                                (<?= number_format(($total_horas_perdidas / $total_horas_planeadas) * 100, 1) ?>%)
                                            <?php else: ?>
                                                <strong class="text-success">Sin horas perdidas</strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (count($dosificacion) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Día</th>
                                                    <th>Horas Planeadas</th>
                                                    <th>Horas Efectivas</th>
                                                    <th>Horas Perdidas</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($dosificacion as $d): 
                                                    $fecha = new DateTime($d['fecha']);
                                                    $dias_semana = [
                                                        'Monday' => 'Lunes',
                                                        'Tuesday' => 'Martes',
                                                        'Wednesday' => 'Miércoles',
                                                        'Thursday' => 'Jueves',
                                                        'Friday' => 'Viernes',
                                                        'Saturday' => 'Sábado',
                                                        'Sunday' => 'Domingo'
                                                    ];
                                                    $dia_semana = $dias_semana[$fecha->format('l')] ?? $fecha->format('l');
                                                    
                                                    $horas_planeadas = floatval($d['horas_planeadas']);
                                                    $horas_asignadas = floatval($d['horas_asignadas']);
                                                    $horas_perdidas = $horas_planeadas - $horas_asignadas;
                                                    
                                                    $estado = "Normal";
                                                    $clase_estado = "success";
                                                    
                                                    if ($d['motivo_reduccion'] == 'evaluacion') {
                                                        $estado = "Evaluación";
                                                        $clase_estado = "danger";
                                                    }
                                                ?>
                                                    <tr>
                                                        <td><?= $fecha->format('d/m/Y') ?></td>
                                                        <td><?= $dia_semana ?></td>
                                                        <td><?= number_format($horas_planeadas, 2) ?></td>
                                                        <td><?= number_format($horas_asignadas, 2) ?></td>
                                                        <td class="<?= $horas_perdidas > 0 ? 'text-danger' : '' ?>">
                                                            <?= number_format($horas_perdidas, 2) ?>
                                                        </td>
                                                        <td><span class="badge bg-<?= $clase_estado ?>"><?= $estado ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-dark">
                                                <tr>
                                                    <th colspan="2">Totales</th>
                                                    <th><?= number_format($total_horas_planeadas, 2) ?></th>
                                                    <th><?= number_format($total_horas_asignadas, 2) ?></th>
                                                    <th class="<?= $total_horas_perdidas > 0 ? 'text-danger' : '' ?>">
                                                        <?= number_format($total_horas_perdidas, 2) ?>
                                                    </th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">No hay dosificación calculada para este tema.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">No hay temas registrados para esta unidad.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="mb-4">
            <a href="gestion_dosificacion.php" class="btn btn-primary">Volver a Gestión de Dosificación</a>
            <a href="p_usuario.php" class="btn btn-primary">Volver al inicio</a>
            <button onclick="window.print()" class="btn btn-secondary">Imprimir Reporte</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>