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
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="header">
                <h1>Gestión de Dosificación</h1>
                <p class="subtitle">Calcula y visualiza la dosificación de tus programas académicos</p>
            </div>
            
            <!-- Sección de Cálculo de Dosificación -->
            <div class="form-section">
                <h3 class="section-title">Calcular Dosificación</h3>
                
                <form id="formDosificacion">
                    <div class="form-group">
                        <label for="programa_id">Seleccionar Programa:</label>
                        <select name="programa_id" id="programa_id" required>
                            <option value="">-- Seleccione un programa --</option>
                            <?php foreach ($programas as $programa): ?>
                                <option value="<?= $programa['id_programa'] ?>">
                                    <?= htmlspecialchars($programa['nombre_materia']) ?> - <?= htmlspecialchars($programa['nombre_semestre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Calcular Dosificación</button>
                </form>
                
                <div id="mensajeResultado" style="display:none;"></div>
            </div>
            
            <!-- Sección de Visualización de Dosificación -->
            <div class="form-section">
                <h3 class="section-title">Ver Dosificación</h3>
                
                <form action="ver_dosificacion.php" method="get">
                    <div class="form-group">
                        <label for="programa_id_ver">Seleccionar Programa:</label>
                        <select name="programa_id" id="programa_id_ver" required>
                            <option value="">-- Seleccione un programa --</option>
                            <?php foreach ($programas as $programa): ?>
                                <option value="<?= $programa['id_programa'] ?>">
                                    <?= htmlspecialchars($programa['nombre_materia']) ?> - <?= htmlspecialchars($programa['nombre_semestre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Ver Dosificación</button>
                </form>
            </div>

            <!-- Navegación -->
            <div class="nav-buttons">
                <a href="p_usuario.php">Volver al Inicio</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('formDosificacion').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const programaId = document.getElementById('programa_id').value;
            if (!programaId) {
                const mensajeDiv = document.getElementById('mensajeResultado');
                mensajeDiv.style.display = 'block';
                mensajeDiv.innerHTML = '<div class="mensaje danger">Por favor seleccione un programa</div>';
                return;
            }
            
            const mensaje = document.getElementById('mensajeResultado');
            mensaje.style.display = 'block';
            mensaje.innerHTML = '<div class="mensaje warning">Calculando dosificación, por favor espere...</div>';
            
            fetch('calcular_dosificacion.php?programa_id=' + programaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mensaje.innerHTML = `
                            <div class="mensaje success">
                                ${data.message}
                                <br><br>
                                <a href="ver_dosificacion.php?programa_id=${data.programa_id}" class="btn" style="display: inline-block; margin-top: 10px;">Ver Dosificación</a>
                            </div>
                        `;
                    } else {
                        mensaje.innerHTML = `<div class="mensaje danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    mensaje.innerHTML = `<div class="mensaje danger">Error al calcular la dosificación: ${error}</div>`;
                });
        });
    </script>
</body>
</html>