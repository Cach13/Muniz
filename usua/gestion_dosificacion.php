<?php
require_once '../config.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'alumno') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener programas disponibles para el usuario
$stmt = $conn->prepare("
    SELECT DISTINCT
        p.id_programa, 
        p.nombre_materia,
        s.nombre as nombre_semestre
    FROM programas p
    JOIN semestres s ON p.id_semestre = s.id_semestre
    JOIN unidades u ON p.id_programa = u.id_programa
    JOIN temas t ON u.id_unidad = t.id_unidad
    JOIN planificacionusuario pu ON t.id_tema = pu.id_tema
    WHERE pu.id_usuario = ?
    ORDER BY s.fecha_inicio DESC, p.nombre_materia
");

$stmt->execute([$user_id]);
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Dosificación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Gestión de Dosificación</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Calcular Dosificación</h2>
            </div>
            <div class="card-body">
                <form id="formDosificacion">
                    <div class="mb-3">
                        <label for="programa_id" class="form-label">Seleccionar Programa:</label>
                        <select name="programa_id" id="programa_id" class="form-select" required>
                            <option value="">-- Seleccione un programa --</option>
                            <?php foreach ($programas as $programa): ?>
                                <option value="<?= $programa['id_programa'] ?>">
                                    <?= htmlspecialchars($programa['nombre_materia']) ?> - <?= htmlspecialchars($programa['nombre_semestre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Calcular Dosificación</button>
                </form>
                
                <div id="mensajeResultado" class="mt-3" style="display:none;"></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Ver Dosificación</h2>
            </div>
            <div class="card-body">
                <form action="ver_dosificacion.php" method="get">
                    <div class="mb-3">
                        <label for="programa_id_ver" class="form-label">Seleccionar Programa:</label>
                        <select name="programa_id" id="programa_id_ver" class="form-select" required>
                            <option value="">-- Seleccione un programa --</option>
                            <?php foreach ($programas as $programa): ?>
                                <option value="<?= $programa['id_programa'] ?>">
                                    <?= htmlspecialchars($programa['nombre_materia']) ?> - <?= htmlspecialchars($programa['nombre_semestre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Ver Dosificación</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formDosificacion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const programaId = document.getElementById('programa_id').value;
            if (!programaId) {
                alert('Por favor seleccione un programa');
                return;
            }
            
            const mensaje = document.getElementById('mensajeResultado');
            mensaje.style.display = 'block';
            mensaje.innerHTML = '<div class="alert alert-info">Calculando dosificación, por favor espere...</div>';
            
            fetch('calcular_dosificacion.php?programa_id=' + programaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mensaje.innerHTML = `
                            <div class="alert alert-success">
                                ${data.message}
                                <a href="ver_dosificacion.php?programa_id=${data.programa_id}" class="btn btn-sm btn-primary mt-2">Ver Dosificación</a>
                            </div>
                        `;
                    } else {
                        mensaje.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    mensaje.innerHTML = `<div class="alert alert-danger">Error al calcular la dosificación: ${error}</div>`;
                });
        });
    </script>

    <a href="p_usuario.php">Volver al Inicio</a>

</body>
</html>