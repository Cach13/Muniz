<?php
require_once '..\config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}


// Obtener lista de programas para el select
$stmt = $conn->query("SELECT id_programa, nombre_materia, horas_teoricas, horas_practicas FROM programas");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar datos del programa vía AJAX
if (isset($_GET['programa_id'])) {
    $id = $_GET['programa_id'];
    $stmt = $conn->prepare("SELECT nombre_materia, horas_teoricas, horas_practicas FROM programas WHERE id_programa = ?");
    $stmt->execute([$id]);
    $programa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($programa) {
        echo json_encode($programa);
    }
    exit;
}

// Cargar unidades de un programa vía AJAX
if (isset($_GET['unidades_programa_id'])) {
    $id = $_GET['unidades_programa_id'];
    $stmt = $conn->prepare("SELECT id_unidad, nombre_unidad, numero_unidad FROM unidades WHERE id_programa = ? ORDER BY numero_unidad");
    $stmt->execute([$id]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($unidades);
    exit;
}

// Cargar temas de una unidad vía AJAX
if (isset($_GET['temas_unidad_id'])) {
    $id = $_GET['temas_unidad_id'];
    $stmt = $conn->prepare("SELECT id_tema, nombre_tema FROM temas WHERE id_unidad = ?");
    $stmt->execute([$id]);
    $temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($temas);
    exit;
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Variables para seguimiento de cambios
    $cambios_realizados = 0;
    $errores_encontrados = false;
    $mensajes_error = [];
    
    // Modificar programa
    if (isset($_POST['programa'])) {
        $id = $_POST['programa'];
        $nombre = $_POST['nuevo_nombre'];
        $horas_teoricas = $_POST['horas_teoricas'];
        $horas_practicas = $_POST['horas_practicas'];

        try {
            $stmt = $conn->prepare("UPDATE programas SET nombre_materia = ?, horas_teoricas = ?, horas_practicas = ? WHERE id_programa = ?");
            $stmt->execute([$nombre, $horas_teoricas, $horas_practicas, $id]);
            $cambios_realizados++;
        } catch (PDOException $e) {
            $errores_encontrados = true;
            $mensajes_error[] = "Error al modificar el programa: " . $e->getMessage();
        }
    }

    // Modificar unidad
    if (isset($_POST['unidad']) && $_POST['unidad'] != "") {
        $id_unidad = $_POST['unidad'];
        $nuevo_nombre_unidad = $_POST['nuevo_nombre_unidad'];
        $numero_unidad = $_POST['numero_unidad'];

        try {
            $stmt = $conn->prepare("UPDATE unidades SET nombre_unidad = ?, numero_unidad = ? WHERE id_unidad = ?");
            $stmt->execute([$nuevo_nombre_unidad, $numero_unidad, $id_unidad]);
            $cambios_realizados++;
        } catch (PDOException $e) {
            $errores_encontrados = true;
            $mensajes_error[] = "Error al modificar la unidad: " . $e->getMessage();
        }
    }

    // Modificar tema
    if (isset($_POST['tema']) && $_POST['tema'] != "") {
        $id_tema = $_POST['tema'];
        $nuevo_nombre_tema = $_POST['nuevo_nombre_tema'];

        try {
            $stmt = $conn->prepare("UPDATE temas SET nombre_tema = ? WHERE id_tema = ?");
            $stmt->execute([$nuevo_nombre_tema, $id_tema]);
            $cambios_realizados++;
        } catch (PDOException $e) {
            $errores_encontrados = true;
            $mensajes_error[] = "Error al modificar el tema: " . $e->getMessage();
        }
    }

    // Agregar unidad
    if (isset($_POST['agregar_unidad']) && isset($_POST['programa_unidad'])) {
        $id_programa = $_POST['programa_unidad'];
        $nombre_unidad = $_POST['nombre_nueva_unidad'];
        $numero_unidad = $_POST['numero_nueva_unidad'];

        try {
            $stmt = $conn->prepare("INSERT INTO unidades (nombre_unidad, id_programa, numero_unidad) VALUES (?, ?, ?)");
            $stmt->execute([$nombre_unidad, $id_programa, $numero_unidad]);
            $cambios_realizados++;
        } catch (PDOException $e) {
            $errores_encontrados = true;
            $mensajes_error[] = "Error al agregar la unidad: " . $e->getMessage();
        }
    }

    // Agregar tema
    if (isset($_POST['agregar_tema']) && isset($_POST['unidad_tema'])) {
        $id_unidad = $_POST['unidad_tema'];
        $nombre_tema = $_POST['nombre_nuevo_tema'];

        try {
            $stmt = $conn->prepare("INSERT INTO temas (nombre_tema, id_unidad) VALUES (?, ?)");
            $stmt->execute([$nombre_tema, $id_unidad]);
            $cambios_realizados++;
        } catch (PDOException $e) {
            $errores_encontrados = true;
            $mensajes_error[] = "Error al agregar el tema: " . $e->getMessage();
        }
    }
    
    // Establecer mensaje de retroalimentación
    if ($cambios_realizados > 0) {
        if ($errores_encontrados) {
            $_SESSION['mensaje'] = "Se completaron algunos cambios, pero ocurrieron errores: " . implode(". ", $mensajes_error);
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            if ($cambios_realizados == 1) {
                $_SESSION['mensaje'] = "El cambio se realizó correctamente.";
            } else {
                $_SESSION['mensaje'] = "Todos los cambios se hicieron correctamente.";
            }
            $_SESSION['tipo_mensaje'] = "exito";
        }
    } elseif ($errores_encontrados) {
        $_SESSION['mensaje'] = "Ocurrieron errores: " . implode(". ", $mensajes_error);
        $_SESSION['tipo_mensaje'] = "error";
    }

    header("Location: m_programa.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Programa</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
    function cargarDatosPrograma(select) {
        var id = select.value;
        if (id) {
            fetch('m_programa.php?programa_id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('nuevo_nombre').value = data.nombre_materia;
                document.getElementById('horas_teoricas').value = data.horas_teoricas;
                document.getElementById('horas_practicas').value = data.horas_practicas;
            });

            // Cargar unidades
            fetch('m_programa.php?unidades_programa_id=' + id)
            .then(response => response.json())
            .then(data => {
                var unidadSelect = document.getElementById('unidad');
                document.getElementById('unidad').innerHTML = '<option value="">Seleccione una unidad</option>';
                data.forEach(function(unidad) {
                    var opt = document.createElement('option');
                    opt.value = unidad.id_unidad;
                    opt.innerHTML = "Unidad " + unidad.numero_unidad + ": " + unidad.nombre_unidad;
                    unidadSelect.appendChild(opt);
                });
                // Limpiar temas
                document.getElementById('tema').innerHTML = '<option value="">Seleccione una unidad primero</option>';
                
                // Actualizar también el selector de unidades para agregar temas
                var unidadTemaSelect = document.getElementById('unidad_tema');
                unidadTemaSelect.innerHTML = '<option value="">Seleccione una unidad</option>';
                data.forEach(function(unidad) {
                    var opt = document.createElement('option');
                    opt.value = unidad.id_unidad;
                    opt.innerHTML = "Unidad " + unidad.numero_unidad + ": " + unidad.nombre_unidad;
                    unidadTemaSelect.appendChild(opt);
                });
                
                // Actualizar el selector de programas para agregar unidades
                document.getElementById('programa_unidad').value = id;
            });
        }
    }

    function cargarTemasUnidad(select) {
        var id = select.value;
        if (id) {
            // Obtener el número de unidad para mostrar en el formulario de edición
            var unidadText = select.options[select.selectedIndex].text;
            var numeroUnidad = unidadText.match(/Unidad (\d+):/);
            if (numeroUnidad && numeroUnidad[1]) {
                document.getElementById('numero_unidad').value = numeroUnidad[1];
            }
            
            fetch('m_programa.php?temas_unidad_id=' + id)
            .then(response => response.json())
            .then(data => {
                var temaSelect = document.getElementById('tema');
                temaSelect.innerHTML = '<option value="">Seleccione un tema</option>';
                data.forEach(function(tema) {
                    var opt = document.createElement('option');
                    opt.value = tema.id_tema;
                    opt.innerHTML = tema.nombre_tema;
                    temaSelect.appendChild(opt);
                });
            });
        }
    }
    </script>
</head>
<body>

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="mensaje <?php echo $_SESSION['tipo_mensaje']; ?>">
        <?php 
        echo $_SESSION['mensaje']; 
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
        ?>
    </div>
    <?php endif; ?>

    <h2>Modificar Programa, Unidad y Tema</h2>
    <form method="POST">

        <!-- Programa -->
        <label>Seleccionar Programa:</label>
        <select name="programa" required onchange="cargarDatosPrograma(this)">
            <option value="">Seleccione un programa</option>
            <?php foreach ($programas as $programa): ?>
                <option value="<?php echo $programa['id_programa']; ?>"><?php echo htmlspecialchars($programa['nombre_materia']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nuevo Nombre Programa:</label>
        <input type="text" name="nuevo_nombre" id="nuevo_nombre" placeholder="Nuevo nombre de programa" required>

        <label>Nuevas Horas Teóricas:</label>
        <input type="number" name="horas_teoricas" id="horas_teoricas" placeholder="Horas teóricas" required>

        <label>Nuevas Horas Prácticas:</label>
        <input type="number" name="horas_practicas" id="horas_practicas" placeholder="Horas prácticas" required>

        <!-- Unidad -->
        <label>Seleccionar Unidad:</label>
        <select name="unidad" id="unidad" onchange="cargarTemasUnidad(this)">
            <option value="">Seleccione un programa primero</option>
        </select>

        <label>Nuevo Nombre Unidad:</label>
        <input type="text" name="nuevo_nombre_unidad" placeholder="Nuevo nombre de unidad">

        <label>Número de Unidad:</label>
        <input type="number" name="numero_unidad" id="numero_unidad" placeholder="Número de unidad" min="1">

        <!-- Tema -->
        <label>Seleccionar Tema:</label>
        <select name="tema" id="tema">
            <option value="">Seleccione una unidad primero</option>
        </select>

        <label>Nuevo Nombre Tema:</label>
        <input type="text" name="nuevo_nombre_tema" placeholder="Nuevo nombre de tema">

        <button type="submit">Modificar</button>
    </form>

    <hr>

    <!-- Agregar Unidad -->
    <h2>Agregar Nueva Unidad</h2>
    <form method="POST">
        <input type="hidden" name="agregar_unidad" value="1">

        <label>Programa:</label>
        <select name="programa_unidad" id="programa_unidad" required>
            <option value="">Seleccione un programa</option>
            <?php foreach ($programas as $programa): ?>
                <option value="<?php echo $programa['id_programa']; ?>"><?php echo htmlspecialchars($programa['nombre_materia']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Nombre de la Nueva Unidad:</label>
        <input type="text" name="nombre_nueva_unidad" placeholder="Nombre de la unidad" required>
        
        <label>Número de Unidad:</label>
        <input type="number" name="numero_nueva_unidad" placeholder="Número de unidad" min="1" required>

        <button type="submit">Agregar Unidad</button>
    </form>

    <hr>

    <!-- Agregar Tema -->
    <h2>Agregar Nuevo Tema</h2>
    <form method="POST">
        <input type="hidden" name="agregar_tema" value="1">

        <label>Unidad:</label>
        <select name="unidad_tema" id="unidad_tema" required>
            <option value="">Seleccione una unidad</option>
        </select>

        <label>Nombre del Nuevo Tema:</label>
        <input type="text" name="nombre_nuevo_tema" placeholder="Nombre del tema" required>

        <button type="submit">Agregar Tema</button>
    </form>

    <a href="p_admin.html">Volver a Opciones de Admin</a>

    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            const confirmacion = confirm("¿Estás seguro de que deseas guardar los cambios?");
            if (!confirmacion) {
                e.preventDefault();
            }
        });
    </script>

</body>
</html>