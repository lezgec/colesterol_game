<?php
session_start();


if (isset($_GET["logout"])) {
    $logoutMessage = "Sesión cerrada correctamente";
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    
}
?>
<?php if (isset($logoutMessage)): ?>
    <p style="color: green;"><?php echo $logoutMessage; ?></p>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Serious Game</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>
    <div class="game-container">
        <div style="text-align:right; margin-bottom:10px;">
    <a href="?lang=es">ES</a> |
    <a href="?lang=en">EN</a>
</div>
        <h1>Registro de Usuario</h1>
        <p>Regístrate para comenzar el juego educativo sobre colesterol.</p>

        <form id="register-form">
            <div class="form-group">
                <label for="name">Nombre</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <button type="submit" class="primary-btn">Registrarse</button>
        </form>

        <p id="register-message"></p>

        <p style="margin-top: 20px;">
            ¿Ya tienes cuenta?
            <a href="/colesterol_game/pages/login.php">Inicia sesión</a>
        </p>
    </div>

    <script src="/colesterol_game/assets/js/register.js"></script>
</body>
</html>