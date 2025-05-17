<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Por favor, complete todos los campos.";
        header('Location: index.php');
        exit;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id_usuario, nombre, contraseña, rol FROM Usuarios WHERE nombre = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Primero intentamos con password_verify (por si ya está hasheada)
            if (password_verify($password, $user['contraseña'])) {
                iniciarSesion($user);

            // Si falla, intentamos comparación directa (por si está en texto plano)
            } elseif ($password === $user['contraseña']) {
                // Migrar a hash
                $newHash = password_hash($password, PASSWORD_DEFAULT);

                $updateStmt = $conn->prepare("UPDATE Usuarios SET contraseña = :newHash WHERE id_usuario = :id");
                $updateStmt->bindParam(':newHash', $newHash);
                $updateStmt->bindParam(':id', $user['id_usuario']);
                $updateStmt->execute();

                iniciarSesion($user);
            } else {
                $_SESSION['error'] = "Contraseña incorrecta.";
            }

        } else {
            $_SESSION['error'] = "Usuario no encontrado.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en el sistema: " . $e->getMessage();
    }
    
    header('Location: index.php');
    exit;
}

function iniciarSesion($user) {
    $_SESSION['user_id'] = $user['id_usuario'];
    $_SESSION['username'] = $user['nombre'];
    $_SESSION['user_role'] = $user['rol'];

    if ($user['rol'] === 'admin') {
        header('Location: admin/p_admin.html');
    } else {
        header('Location: usua/p_usuario.html');
    }
    exit;
}
?>
