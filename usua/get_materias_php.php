<?php
require_once 'config.php';
require_once 'session.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

try {
    // Obtener materias disponibles para el usuario
    $stmt = $conn->prepare("
        SELECT p.id_programa, p.nombre_materia 
        FROM programas p
        JOIN semestres s ON p.id_semestre = s.id_semestre
        WHERE s.fecha_fin >= CURDATE() 
        ORDER BY p.nombre_materia
    ");
    $stmt->execute();
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($materias);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al cargar materias: ' . $e->getMessage()]);
}
?>