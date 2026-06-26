<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';

if (current_session_is_active()) {
    header("Location: " . redirect_after_login_by_role($_SESSION["user_role"] ?? "player"));
    exit;
}

$token = trim($_GET["token"] ?? "");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo current_lang() === "en" ? "New password" : "Nueva contraseña"; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container auth-container">

    <h1><?php echo current_lang() === "en" ? "New password" : "Nueva contraseña"; ?></h1>

    <?php if ($token === ""): ?>
        <p style="color:#ff6b6b; text-align:center;">
            <?php echo current_lang() === "en"
                ? "The recovery link is invalid."
                : "El enlace de recuperación no es válido."; ?>
        </p>
    <?php else: ?>
        <form id="reset-password-form">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, "UTF-8"); ?>">

            <div class="form-group">
                <label><?php echo current_lang() === "en" ? "New password" : "Nueva contraseña"; ?></label>
                <input type="password" id="password" minlength="10" autocomplete="new-password" required>
                <ul id="password-policy-hint" class="password-policy-hint"></ul>
            </div>

            <div class="form-group">
                <label><?php echo current_lang() === "en" ? "Confirm password" : "Confirmar contraseña"; ?></label>
                <input type="password" id="password-confirm" minlength="10" autocomplete="new-password" required>
            </div>

            <button type="submit" class="primary-btn">
                <?php echo current_lang() === "en" ? "Save password" : "Guardar contraseña"; ?>
            </button>
        </form>
    <?php endif; ?>

    <p id="reset-password-message"></p>

    <p style="margin-top:20px; text-align:center;">
        <a href="<?php echo app_path('pages/login.php'); ?>">
            <?php echo current_lang() === "en" ? "Back to login" : "Volver al inicio de sesión"; ?>
        </a>
    </p>

</div>

<script>
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="<?php echo asset_path('js/http.js'); ?>?v=<?php echo filemtime(__DIR__ . '/../assets/js/http.js'); ?>"></script>
<script src="<?php echo asset_path('js/password_policy.js'); ?>"></script>
<script src="<?php echo asset_path('js/reset_password.js'); ?>"></script>

<script src="<?php echo asset_path('js/theme.js'); ?>"></script>
</body>
</html>
