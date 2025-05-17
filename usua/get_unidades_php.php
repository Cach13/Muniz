<?php
require_once 'config.php';
require_once 'session.php';

// Verificar sesión
verificarSesion();

header('Content-Type: application/json');

// Verificar parámetro
if (!isset($_GET['id_programa']) || empty($_GET['id_programa'])) {
    echo json_encode(['error' => 'Parámetro id_programa requerido']);
    exit;
}

$id_programa = $_GET['id_programa'];

try {
    // Obtener unidades de la materia seleccionada
    $stmt = $conn->prepare("
        SELECT id_unidad, nombre_unidad, numero_unidad 
        FROM unidades
        WHERE id_programa = ?
        ORDER BY numero_unidad
    ");
    $stmt->execute([$id_programa]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($unidades);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al cargar unidades: ' . $e->getMessage()]);
}
?>