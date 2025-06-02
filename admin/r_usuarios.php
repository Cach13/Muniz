<?php
// funciona completamente

require_once '../config.php';
session_start();

// Verificar si hay sesión de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    // Validaciones adicionales
    if (strlen($password) < 6) {
        $mensaje = "🚨 Error: La contraseña debe tener al menos 6 caracteres para mayor seguridad.";
        $tipo_mensaje = "error";
    } elseif (strlen($nombre) < 3) {
        $mensaje = "🚨 Error: El nombre de usuario debe tener al menos 3 caracteres.";
        $tipo_mensaje = "error";
    } else {
        try {
            // Verificar si el usuario ya existe
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre = ?");
            $stmt_check->execute([$nombre]);
            
            if ($stmt_check->fetchColumn() > 0) {
                $mensaje = "⚠️ Atención: Ya existe un usuario con ese nombre. Por favor, elige otro nombre de usuario.";
                $tipo_mensaje = "warning";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, contraseña, rol) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $password_hash, $rol]);
                
                // Mensaje de éxito más descriptivo según el rol
                if ($rol == 'admin') {
                    $mensaje = "🎉 ¡Excelente! El administrador '<strong>" . htmlspecialchars($nombre) . "</strong>' ha sido registrado exitosamente con todos los privilegios del sistema.";
                } else {
                    $mensaje = "✅ ¡Perfecto! El usuario '<strong>" . htmlspecialchars($nombre) . "</strong>' ha sido registrado exitosamente y ya puede acceder al sistema.";
                }
                $tipo_mensaje = "success";
                
                // Limpiar el formulario después del registro exitoso
                $_POST = array();
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Error de duplicado
                $mensaje = "⚠️ Error: Ya existe un usuario con ese nombre. Por favor, elige otro nombre de usuario.";
                $tipo_mensaje = "warning";
            } else {
                $mensaje = "❌ Error inesperado: No se pudo registrar el usuario. Por favor, inténtalo nuevamente o contacta al administrador del sistema.";
                $tipo_mensaje = "error";
            }
        } catch (Exception $e) {
            $mensaje = "❌ Error del sistema: Ocurrió un problema al procesar la solicitud. Por favor, verifica los datos e inténtalo nuevamente.";
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuarios</title>
    <link rel="stylesheet" href="css/styles.css">

</head>
<body>
    <div class="form-container">
        <h2 class="form-title">👤 Registrar Nuevo Usuario</h2>

        <?php if (!empty($mensaje)) : ?>
            <div class="mensaje <?= $tipo_mensaje ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="userForm">
            <div class="form-group">
                <label for="nombre">🏷️ Nombre de Usuario:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ingresa el nombre de usuario" 
                       value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>" 
                       required minlength="3">
                <div class="password-requirements">Mínimo 3 caracteres</div>
            </div>

            <div class="form-group">
                <label for="password">🔒 Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="Crea una contraseña segura" 
                       required minlength="6">
                <div class="password-requirements">Mínimo 6 caracteres para mayor seguridad</div>
            </div>
            
            <div class="form-group">
                <label for="rol">👔 Rol de Usuario:</label>
                <select name="rol" id="rol" required>
                    <option value="">Seleccione un rol</option>
                    <option value="admin" <?= (isset($_POST['rol']) && $_POST['rol'] == 'admin') ? 'selected' : '' ?>>
                        🔧 Administrador - Acceso completo al sistema
                    </option>
                    <option value="alumno" <?= (isset($_POST['rol']) && $_POST['rol'] == 'alumno') ? 'selected' : '' ?>>
                        📚 Usuario - Acceso limitado para consultas
                    </option>
                </select>
            </div>
            
            <button type="submit" class="btn-register">✨ Registrar Usuario</button>
        </form>
        
        <div class="center-link">
    <a href="p_admin.php">← Volver al inicio</a>
</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación en tiempo real del formulario
            const form = document.getElementById('userForm');
            const nombreInput = document.getElementById('nombre');
            const passwordInput = document.getElementById('password');
            
            // Validar nombre de usuario
            nombreInput.addEventListener('input', function() {
                if (this.value.length < 3 && this.value.length > 0) {
                    this.style.borderColor = '#ffc107';
                } else if (this.value.length >= 3) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            // Validar contraseña
            passwordInput.addEventListener('input', function() {
                if (this.value.length < 6 && this.value.length > 0) {
                    this.style.borderColor = '#dc3545';
                } else if (this.value.length >= 6) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            // Validación antes de enviar
            form.addEventListener('submit', function(e) {
                const nombre = nombreInput.value.trim();
                const password = passwordInput.value;
                
                if (nombre.length < 3) {
                    e.preventDefault();
                    alert('⚠️ El nombre de usuario debe tener al menos 3 caracteres');
                    nombreInput.focus();
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('⚠️ La contraseña debe tener al menos 6 caracteres');
                    passwordInput.focus();
                    return false;
                }
            });
            
            // Auto-ocultar mensajes de éxito después de 5 segundos
            const mensajeSuccess = document.querySelector('.mensaje.success');
            if (mensajeSuccess) {
                setTimeout(function() {
                    mensajeSuccess.style.opacity = '0';
                    mensajeSuccess.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        mensajeSuccess.style.display = 'none';
                    }, 300);
                }, 5000);
            }
        });
    </script>
    
</body>
</html>