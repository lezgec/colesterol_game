<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';

if (current_session_is_active()) {
    header("Location: " . redirect_after_login_by_role($_SESSION["user_role"] ?? "player"));
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo current_lang() === "en" ? "Recover password" : "Recuperar contraseña"; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">

</head>
<body>

<div class="game-container auth-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>
    </div>

    <h1><?php echo current_lang() === "en" ? "Recover password" : "Recuperar contraseña"; ?></h1>

    <form id="forgot-password-form">
        <div class="form-group">
            <label><?php echo t("email"); ?></label>
            <input type="email" id="email" required>
        </div>

        <button type="submit" class="primary-btn">
            <?php echo current_lang() === "en" ? "Send recovery link" : "Enviar enlace"; ?>
        </button>
    </form>

    <p id="forgot-password-message"></p>

    <p style="margin-top:20px; text-align:center;">
        <a href="/colesterol_game/pages/login.php">
            <?php echo current_lang() === "en" ? "Back to login" : "Volver al inicio de sesión"; ?>
        </a>
    </p>

</div>

<script src="/colesterol_game/assets/js/forgot_password.js"></script>

<script src="/colesterol_game/assets/js/theme.js"></script>
</body>
</html>
