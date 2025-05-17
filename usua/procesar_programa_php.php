<?php
require_once 'config.php';
require_once 'session.php';

// Verificar inicio de sesión
verificarSesion();

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Obtener datos del formulario
        $materia_id = $_POST['materia'] ?? null;
        $fecha_evaluacion = $_POST['fecha_evaluacion'] ?? null;
        
        // Validar datos básicos
        if (!$materia_id) {
            echo json_encode(['error' => 'Debe seleccionar una materia']);
            exit;
        }
        
        if (!$fecha_evaluacion) {
            echo json_encode(['error' => 'Debe seleccionar una fecha de evaluación']);
            exit;
        }
        
        // Validar que existan temas
        if (!isset($_POST['tema_unidad_id']) || !is_array($_POST['tema_unidad_id']) || count($_POST['tema_unidad_id']) == 0) {
            echo json_encode(['error' => 'Debe agregar al menos un tema']);
            exit;
        }
        
        // Verificar los días y horas
        if (!isset($_POST['dia']) || !is_array($_POST['dia']) || count($_POST['dia']) == 0) {
            echo json_encode(['error' => 'Debe especificar al menos un horario']);
            exit;
        }
        
        // Comenzar transacción
        $conn->beginTransaction();
        
        // 1. Registrar los temas
        $tema_unidad_ids = $_POST['tema_unidad_id'];
        $tema_nombres = $_POST['tema_nombre'];
        $tema_horas = $_POST['tema_horas'];
        
        $tema_ids = []; // Almacenar los IDs de temas creados
        
        for ($i = 0; $i < count($tema_unidad_ids); $i++) {
            $unidad_id = $tema_unidad_ids[$i];
            $nombre_tema = $tema_nombres[$i];
            $horas_estimadas = $tema_horas[$i];
            
            $stmt = $conn->prepare("INSERT INTO temas (id_unidad, nombre_tema, horas_estimadas) VALUES (?, ?, ?)");
            $stmt->execute([$unidad_id, $nombre_tema, $horas_estimadas]);
            
            $tema_ids[] = $conn->lastInsertId();
        }
        
        // 2. Registrar evaluación para cada unidad única
        $unidades_procesadas = [];
        
        foreach ($tema_unidad_ids as $unidad_id) {
            if (!in_array($unidad_id, $unidades_procesadas)) {
                $stmt = $conn->prepare("INSERT INTO evaluaciones (id_unidad, id_usuario, fecha_evaluacion) VALUES (?, ?, ?)");
                $stmt->execute([$unidad_id, $usuario_id, $fecha_evaluacion]);
                $unidades_procesadas[] = $unidad_id;
            }
        }
        
        // 3. Registrar disponibilidad (horarios)
        $dias = $_POST['dia'];
        $horas_inicio = $_POST['hora_inicio'];
        $horas_fin = $_POST['hora_fin'];
        
        for ($i = 0; $i < count($dias); $i++) {
            $dia = $dias[$i];
            $hora_inicio = $horas_inicio[$i];
            $hora_fin = $horas_fin[$i];
            
            // Convertir nombre de día a fecha (siguiente ocurrencia del día)
            $fecha = obtenerProximaFecha($dia);
            
            $stmt = $conn->prepare("INSERT INTO disponibilidad (id_usuario, fecha, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $fecha, $hora_inicio, $hora_fin]);
        }
        
        // 4. Crear dosificación de temas (asignación de fechas)
        foreach ($tema_ids as $tema_id) {
            // Obtener el primer día disponible
            $stmt = $conn->prepare("SELECT fecha, hora_inicio, hora_fin FROM disponibilidad WHERE id_usuario = ? ORDER BY fecha, hora_inicio LIMIT 1");
            $stmt->execute([$usuario_id]);
            $disponibilidad = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($disponibilidad) {
                $stmt = $conn->prepare("INSERT INTO dosificacion (id_tema, fecha, horas_asignadas) VALUES (?, ?, ?)");
                $stmt->execute([$tema_id, $disponibilidad['fecha'], calcularHoras($disponibilidad['hora_inicio'], $disponibilidad['hora_fin'])]);
            }
        }
        
        // Confirmar transacción
        $conn->commit();
        
        echo json_encode(['mensaje' => 'Programa registrado correctamente']);
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        $conn->rollBack();
        echo json_encode(['error' => 'Error al procesar los datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Método de solicitud no válido']);
}

// Función para obtener la próxima fecha para un día de la semana
function obtenerProximaFecha($nombreDia) {
    $dias = [
        'Lunes' => 1,
        'Martes' => 2,
        'Miércoles' => 3,
        'Jueves' => 4,
        'Viernes' => 5,
        'Sábado' => 6,
        'Domingo' => 0
    ];
    
    $numeroDia = $dias[$nombreDia];
    $hoy = date('N'); // 1 (lunes) a 7 (domingo)
    
    if ($hoy <= $numeroDia) {
        $diasFaltantes = $numeroDia - $hoy;
    } else {
        $diasFaltantes = 7 - ($hoy - $numeroDia);
    }
    
    return date('Y-m-d', strtotime("+{$diasFaltantes} days"));
}

// Función para calcular diferencia de horas
function calcularHoras($inicio, $fin) {
    $hora_inicio = strtotime($inicio);
    $hora_fin = strtotime($fin);
    $diferencia = $hora_fin - $hora_inicio;
    
    // Convertir segundos a horas (con decimales)
    return round($diferencia / 3600, 2);
}
?>