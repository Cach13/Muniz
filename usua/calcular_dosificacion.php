<?php
require_once '../config.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'alumno') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para acceder a esta página']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verificar si se proporcionó un ID de programa
if (!isset($_GET['programa_id']) || empty($_GET['programa_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Se requiere un ID de programa']);
    exit();
}

$programa_id = intval($_GET['programa_id']);

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Verificar si el usuario tiene acceso al programa
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id_programa, p.nombre_materia, s.id_semestre, s.fecha_inicio, s.fecha_fin
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
        throw new Exception('No tienes acceso a este programa o el programa no existe');
    }
    
    $semestre_id = $programa['id_semestre'];
    
    // Limpiar dosificación anterior para este programa
    $stmt = $conn->prepare("
        DELETE d FROM dosificacion d
        JOIN temas t ON d.id_tema = t.id_tema
        JOIN unidades u ON t.id_unidad = u.id_unidad
        WHERE u.id_programa = ?
    ");
    $stmt->execute([$programa_id]);
    
    // Obtener todos los temas del programa
    $stmt = $conn->prepare("
        SELECT t.id_tema, t.nombre_tema, u.nombre_unidad, u.numero_unidad
        FROM temas t
        JOIN unidades u ON t.id_unidad = u.id_unidad
        WHERE u.id_programa = ?
        ORDER BY u.numero_unidad, t.id_tema
    ");
    $stmt->execute([$programa_id]);
    $temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener días no hábiles
    $stmt = $conn->prepare("
        SELECT fecha FROM diasnohabiles WHERE id_semestre = ?
    ");
    $stmt->execute([$semestre_id]);
    $dias_no_habiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener periodos de vacaciones
    $stmt = $conn->prepare("
        SELECT fecha_inicio, fecha_fin FROM vacaciones WHERE id_semestre = ?
    ");
    $stmt->execute([$semestre_id]);
    $vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener evaluaciones del usuario
    $stmt = $conn->prepare("
        SELECT e.fecha_evaluacion 
        FROM evaluaciones e
        JOIN unidades u ON e.id_unidad = u.id_unidad
        WHERE u.id_programa = ? AND e.id_usuario = ?
    ");
    $stmt->execute([$programa_id, $user_id]);
    $evaluaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Procesar cada tema
    foreach ($temas as $tema) {
        $id_tema = $tema['id_tema'];
        
        // Obtener disponibilidad del usuario para este tema
        $stmt = $conn->prepare("
            SELECT fecha, hora_inicio, hora_fin 
            FROM disponibilidad 
            WHERE id_usuario = ? AND id_tema = ?
            ORDER BY fecha
        ");
        $stmt->execute([$user_id, $id_tema]);
        $disponibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($disponibilidades as $disponibilidad) {
            $fecha = $disponibilidad['fecha'];
            $hora_inicio = $disponibilidad['hora_inicio'];
            $hora_fin = $disponibilidad['hora_fin'];
            
            // Calcular horas planeadas basándose en la diferencia de tiempo
            $inicio = new DateTime($hora_inicio);
            $fin = new DateTime($hora_fin);
            $diferencia = $fin->diff($inicio);
            $horas_planeadas = $diferencia->h + ($diferencia->i / 60); // Horas + minutos convertidos a decimal
            
            $horas_asignadas = $horas_planeadas; // Inicialmente asumimos todas las horas
            $motivo_reduccion = null;
        
            // Para depuración
            $es_dia_inhabil = in_array($fecha, $dias_no_habiles);
            $es_evaluacion = in_array($fecha, $evaluaciones);
            $es_vacacion = false;
            foreach ($vacaciones as $vacacion) {
                if ($fecha >= $vacacion['fecha_inicio'] && $fecha <= $vacacion['fecha_fin']) {
                    $es_vacacion = true;
                    break;
                }
            }
            
            // Verificar si la fecha coincide con algún día no hábil
            if (in_array($fecha, $dias_no_habiles)) {
                $horas_asignadas = 0;
                $motivo_reduccion = 'dia_no_habil';
            }
            
            // Verificar si la fecha coincide con alguna evaluación
            else if (in_array($fecha, $evaluaciones)) {
                $horas_asignadas = 0;
                $motivo_reduccion = 'evaluacion';
            }
            
            // Verificar si la fecha cae dentro de algún periodo de vacaciones
            else {
                foreach ($vacaciones as $vacacion) {
                    $inicio = $vacacion['fecha_inicio'];
                    $fin = $vacacion['fecha_fin'];
                    if ($fecha >= $inicio && $fecha <= $fin) {
                        $horas_asignadas = 0;
                        $motivo_reduccion = 'vacaciones';
                        break;
                    }
                }
            }
            
            // Guardar la dosificación (tanto si hay horas efectivas como si no)
            $stmt = $conn->prepare("
                INSERT INTO dosificacion (id_tema, fecha, horas_planeadas, horas_asignadas, motivo_reduccion)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            // Registrar información de depuración
            error_log("Dosificación para tema ID: $id_tema, Fecha: $fecha");
            error_log("Horas planeadas: $horas_planeadas, Horas asignadas: $horas_asignadas");
            error_log("Es día inhábil: " . ($es_dia_inhabil ? 'SÍ' : 'NO'));
            error_log("Es evaluación: " . ($es_evaluacion ? 'SÍ' : 'NO'));
            error_log("Es vacación: " . ($es_vacacion ? 'SÍ' : 'NO'));
            error_log("Motivo reducción: " . ($motivo_reduccion ?: 'ninguno'));
            
            $stmt->execute([$id_tema, $fecha, $horas_planeadas, $horas_asignadas, $motivo_reduccion]);
        }
    }
    
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Dosificación recalculada correctamente para el programa: ' . $programa['nombre_materia'],
        'programa_id' => $programa_id,
        'registros_creados' => count($temas)
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al recalcular la dosificación: ' . $e->getMessage()
    ]);
}
?>