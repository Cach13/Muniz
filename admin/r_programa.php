<?php
require_once '../config.php';
session_start();

// Verificar si hay sesión de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Obtener semestres disponibles
$stmt = $conn->query("SELECT id_semestre, nombre FROM semestres");
$semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // VALIDACIÓN BACKEND - Verificar horas antes de procesar
        $horas_teoricas = (int)$_POST['horas_teoricas'];
        $horas_practicas = (int)$_POST['horas_practicas'];
        
        // Validar rango de horas
        if ($horas_teoricas < 1 || $horas_teoricas > 5) {
            throw new Exception("Las horas teóricas deben estar entre 1 y 5. Valor recibido: " . $horas_teoricas);
        }
        
        if ($horas_practicas < 1 || $horas_practicas > 5) {
            throw new Exception("Las horas prácticas deben estar entre 1 y 5. Valor recibido: " . $horas_practicas);
        }
        
        // Validar otros campos requeridos
        if (empty($_POST['materia']) || empty($_POST['semestre']) || empty($_POST['num_unidades'])) {
            throw new Exception("Todos los campos son obligatorios");
        }
        
        $num_unidades = (int)$_POST['num_unidades'];
        if ($num_unidades < 1 || $num_unidades > 10) {
            throw new Exception("El número de unidades debe estar entre 1 y 10");
        }
        
        $conn->beginTransaction();
        
        // Insertar programa (multiplicar horas por 16)
        $horas_teoricas_total = $horas_teoricas * 16;
        $horas_practicas_total = $horas_practicas * 16;
        
        $stmt = $conn->prepare("INSERT INTO programas (nombre_materia, horas_teoricas, horas_practicas, id_semestre, id_admin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['materia']),
            $horas_teoricas_total,
            $horas_practicas_total,
            $_POST['semestre'],
            $_SESSION['user_id']
        ]);
        
        $id_programa = $conn->lastInsertId();
        
        // Insertar unidades y temas
        for ($i = 1; $i <= $num_unidades; $i++) {
            // Validar que exista el nombre de la unidad
            if (empty($_POST['nombre_unidad_' . $i])) {
                throw new Exception("El nombre de la unidad $i es obligatorio");
            }
            
            $nombre_unidad = trim($_POST['nombre_unidad_' . $i]);
            
            $stmt = $conn->prepare("INSERT INTO unidades (id_programa, nombre_unidad, numero_unidad) VALUES (?, ?, ?)");
            $stmt->execute([$id_programa, $nombre_unidad, $i]);
            $id_unidad = $conn->lastInsertId();
            
            if (isset($_POST['temas_unidad_' . $i]) && !empty($_POST['temas_unidad_' . $i])) {
                $temas_array = explode(',', trim($_POST['temas_unidad_' . $i]));
                foreach ($temas_array as $tema) {
                    $tema_limpio = trim($tema);
                    if ($tema_limpio !== '') {
                        $stmt = $conn->prepare("INSERT INTO temas (id_unidad, nombre_tema) VALUES (?, ?)");
                        $stmt->execute([$id_unidad, $tema_limpio]);
                    }
                }
            }
        }
        
        $conn->commit();
        $mensaje = "Programa registrado exitosamente. Total de horas teóricas: {$horas_teoricas_total}, Total de horas prácticas: {$horas_practicas_total}";
        $tipo_mensaje = "success";
        
    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = $e->getMessage();
        $tipo_mensaje = "error";
    } catch (PDOException $e) {
        $conn->rollBack();
        $mensaje = "Error de base de datos: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Programa Académico</title>
    <link rel="stylesheet" href="css/styles.css">
    
</head>
<body class="general-page">
    <h2>Registrar Programa Académico</h2>

    

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validarFormulario()">
        <div class="form-group">
            <label for="materia">Materia</label>
            <input type="text" id="materia" name="materia" placeholder="Nombre de la materia" required>
        </div>
        
        <div class="form-group">
            <label for="horas_teoricas">Horas Teóricas (máximo 5 horas por semana)</label>
            <input type="number" id="horas_teoricas" name="horas_teoricas" placeholder="Horas Teóricas" 
                   required min="1" max="5" 
                   oninput="validarHorasEnTiempoReal(this)" onblur="validarHoras(this)">
            <small class="help-text">Se multiplicarán por 16 semanas automáticamente</small>
        </div>
        
        <div class="form-group">
            <label for="horas_practicas">Horas Prácticas (máximo 5 horas por semana)</label>
            <input type="number" id="horas_practicas" name="horas_practicas" placeholder="Horas Prácticas" 
                   required min="1" max="5"
                   oninput="validarHorasEnTiempoReal(this)" onblur="validarHoras(this)">
            <small class="help-text">Se multiplicarán por 16 semanas automáticamente</small>
        </div>
        
        <div class="form-group">
            <label for="semestre">Semestre</label>
            <select id="semestre" name="semestre" required>
                <option value="">Seleccione un semestre</option>
                <?php foreach ($semestres as $semestre): ?>
                    <option value="<?php echo $semestre['id_semestre']; ?>">
                        <?php echo htmlspecialchars($semestre['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="num_unidades">Número de Unidades</label>
            <input type="number" id="num_unidades" name="num_unidades" min="1" max="10" 
                   placeholder="Número de unidades (1-10)" required onchange="mostrarCamposTemas()">
        </div>
        
        <div id="temas_container">
            <!-- Aquí se generarán dinámicamente los campos para los nombres y temas de cada unidad -->
        </div>
        
        <div class="button-group">
            <button type="submit" class="success">Registrar Programa</button>
        </div>
    </form>
    
    <div class="center-link">
    <a href="p_admin.php">← Volver al inicio</a>
</div>

    <script>
    function mostrarCamposTemas() {
        var numUnidades = document.getElementById('num_unidades').value;
        var contenedorTemas = document.getElementById('temas_container');
        contenedorTemas.innerHTML = '';
        
        for (var i = 1; i <= numUnidades; i++) {
            var div = document.createElement('div');
            div.className = 'unidad-container';
            div.innerHTML = `
                <div class="unidad-header">Unidad ${i}</div>
                <div class="form-group">
                    <label for="nombre_unidad_${i}">Nombre de la Unidad ${i}</label>
                    <input type="text" id="nombre_unidad_${i}" name="nombre_unidad_${i}" 
                           placeholder="Ej: Introducción a la Programación" required>
                </div>
                <div class="form-group">
                    <label for="temas_unidad_${i}">Temas de la Unidad ${i}</label>
                    <textarea id="temas_unidad_${i}" name="temas_unidad_${i}" 
                              placeholder="Tema 1, Tema 2, Tema 3..." required rows="3"></textarea>
                    <small class="help-text">Separa los temas con comas</small>
                </div>
            `;
            contenedorTemas.appendChild(div);
        }
    }

    function validarHoras(input) {
        let valor = input.value;
        
        // Si está vacío, no hacer nada (permitir que el usuario siga escribiendo)
        if (valor === '') {
            ocultarMensajeValidacion(input);
            return;
        }
        
        let numeroValor = parseInt(valor);
        
        // Si no es un número válido, limpiar el campo
        if (isNaN(numeroValor)) {
            input.value = '';
            mostrarMensajeValidacion(input, 'Solo se permiten números');
            return;
        }
        
        // Solo validar y corregir si el número está fuera del rango
        if (numeroValor < 1) {
            input.value = 1;
            mostrarMensajeValidacion(input, 'El mínimo es 1 hora');
        } else if (numeroValor > 5) {
            input.value = 5;
            mostrarMensajeValidacion(input, 'El máximo es 5 horas');
        } else {
            ocultarMensajeValidacion(input);
        }
    }

    function validarHorasEnTiempoReal(input) {
        let valor = input.value;
        
        // Si está vacío, no mostrar mensaje de error
        if (valor === '') {
            ocultarMensajeValidacion(input);
            return;
        }
        
        let numeroValor = parseInt(valor);
        
        // Solo mostrar advertencia, no corregir automáticamente
        if (isNaN(numeroValor)) {
            mostrarMensajeValidacion(input, 'Solo se permiten números', 'warning');
        } else if (numeroValor < 1) {
            mostrarMensajeValidacion(input, 'El mínimo es 1 hora', 'warning');
        } else if (numeroValor > 5) {
            mostrarMensajeValidacion(input, 'El máximo es 5 horas', 'warning');
        } else {
            ocultarMensajeValidacion(input);
        }
    }

    function mostrarMensajeValidacion(input, mensaje, tipo = 'error') {
        // Remover mensaje anterior si existe
        let mensajeAnterior = input.parentNode.querySelector('.mensaje-validacion');
        if (mensajeAnterior) {
            mensajeAnterior.remove();
        }
        
        // Crear nuevo mensaje
        let mensajeDiv = document.createElement('div');
        mensajeDiv.className = 'mensaje-validacion';
        if (tipo === 'warning') {
            mensajeDiv.className += ' warning';
        }
        mensajeDiv.textContent = mensaje;
        
        input.parentNode.appendChild(mensajeDiv);
        
        // Remover mensaje después de 3 segundos solo si es warning
        if (tipo === 'warning') {
            setTimeout(() => {
                if (mensajeDiv.parentNode) {
                    mensajeDiv.remove();
                }
            }, 3000);
        }
    }

    function ocultarMensajeValidacion(input) {
        let mensaje = input.parentNode.querySelector('.mensaje-validacion');
        if (mensaje) {
            mensaje.remove();
        }
    }

    function validarFormulario() {
        let horasTeoricas = document.querySelector('input[name="horas_teoricas"]');
        let horasPracticas = document.querySelector('input[name="horas_practicas"]');
        
        let valorTeorico = parseInt(horasTeoricas.value);
        let valorPractico = parseInt(horasPracticas.value);
        
        // Validar horas teóricas
        if (isNaN(valorTeorico) || valorTeorico < 1 || valorTeorico > 5) {
            alert('Las horas teóricas deben estar entre 1 y 5');
            horasTeoricas.focus();
            return false;
        }
        
        // Validar horas prácticas
        if (isNaN(valorPractico) || valorPractico < 1 || valorPractico > 5) {
            alert('Las horas prácticas deben estar entre 1 y 5');
            horasPracticas.focus();
            return false;
        }
        
        // Validar que haya al menos una unidad
        let numUnidades = document.getElementById('num_unidades').value;
        if (!numUnidades || numUnidades < 1) {
            alert('Debe especificar al menos 1 unidad');
            document.getElementById('num_unidades').focus();
            return false;
        }
        
        return true;
    }

    // Inicializar la validación al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-focus en el primer campo
        document.getElementById('materia').focus();
    });
    </script>
</body>
</html>