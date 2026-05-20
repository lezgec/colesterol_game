<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: /colesterol_game/pages/game.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
    <h1>Iniciar sesión</h1>

    <form id="login-form">
        <div class="form-group">
            <label>Correo</label>
            <input type="email" id="email" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" id="password" required>
        </div>

        <button type="submit" class="primary-btn">Ingresar</button>
    </form>

    <p id="login-message"></p>

    <p style="margin-top:20px;">
        ¿No tienes cuenta?
        <a href="/colesterol_game/pages/register.php">Regístrate</a>
    </p>
</div>

<script src="/colesterol_game/assets/js/login.js"></script>

</body>
</html>