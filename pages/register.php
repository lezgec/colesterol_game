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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo t("register"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin">

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

    <h1><?php echo t("register"); ?></h1>

    <p>
        <?php echo current_lang() === "en"
            ? "Register to start the educational cholesterol game."
            : "Regístrate para comenzar el juego educativo sobre colesterol."; ?>
    </p>

    <?php if (isset($_GET["logout"])): ?>

        <p style="color:#4caf50; text-align:center; font-weight:bold;">

            <?php echo current_lang() === "en"
                ? "Session closed successfully"
                : "Sesión cerrada correctamente"; ?>

        </p>

    <?php endif; ?>

    <form id="register-form">

        <div class="form-group">
            <label for="name"><?php echo t("name"); ?></label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="email"><?php echo t("email"); ?></label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password"><?php echo t("password"); ?></label>
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="6"
            >
        </div>

        <button type="submit" class="primary-btn">
            <?php echo t("register"); ?>
        </button>

    </form>

    <p id="register-message"></p>

    <p style="margin-top:20px; text-align:center;">

        <?php echo current_lang() === "en"
            ? "Already have an account?"
            : "¿Ya tienes cuenta?"; ?>

        <a href="/colesterol_game/pages/login.php">

            <?php echo current_lang() === "en"
                ? "login_title"
                : "Inicia sesión"; ?>

        </a>

    </p>

</div>

<script src="/colesterol_game/assets/js/register.js"></script>

</body>
</html>