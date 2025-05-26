<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema de Cronograma</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/login.js" defer></script>
</head>
<body>

<div class="login-container">
    <h2>Iniciar Sesión</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message" id="error-box">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']); 
            ?>
        </div>
    <?php endif; ?>
    
    <form id="login-form" action="login.php" method="post">
        <div class="form-group">
            <input type="text" name="username" id="username" placeholder="Nombre de usuario" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" id="password" placeholder="Contraseña" required>
        </div>
        <div class="form-group">
            <button type="submit" id="login-button">Entrar</button>
        </div>
    </form>

    <div class="loader" id="loader" style="display:none;">
        <div class="spinner"></div>
        <p>Verificando credenciales...</p>
    </div>
</div>

</body>
</html>
