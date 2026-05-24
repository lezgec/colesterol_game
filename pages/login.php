<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
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
    <title><?php echo t("login_title"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">

        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

    </div>

    <h1><?php echo t("login_title"); ?></h1>
        <?php if (isset($_GET["logout"])): ?>
        <p style="color:#4caf50; text-align:center;">
            <?php echo current_lang() === "en"
                ? "Session closed successfully"
                : "Sesión cerrada correctamente"; ?>
        </p>
    <?php endif; ?>

    <form id="login-form">

        <div class="form-group">
            <label><?php echo t("email"); ?></label>
            <input type="email" id="email" required>
        </div>

        <div class="form-group">
            <label><?php echo t("password"); ?></label>
            <input type="password" id="password" required>
        </div>

        <button type="submit" class="primary-btn">
            <?php echo t("login_title"); ?>
        </button>

    </form>

    <p id="login-message"></p>

    <p style="margin-top:20px; text-align:center;">

        <?php echo current_lang() === "en"
            ? "Don't have an account?"
            : "¿No tienes cuenta?"; ?>

        <a href="/colesterol_game/pages/register.php">
            <?php echo current_lang() === "en"
                ? "Register"
                : "Regístrate"; ?>
        </a>

    </p>

</div>

<script src="/colesterol_game/assets/js/login.js"></script>

</body>
</html>