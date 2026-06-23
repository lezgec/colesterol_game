<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';

if (current_session_is_active()) {
    header("Location: " . redirect_after_login_by_role($_SESSION["user_role"] ?? "player"));
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$loginJsVersion = filemtime(__DIR__ . '/../assets/js/login.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("login_title"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?v=<?php echo $styleVersion; ?>">
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

        <a href="/colesterol_game/index.php" class="admin-login-link" style="margin:0;">
            <?php echo t("back"); ?>
        </a>

    </div>

    <h1><?php echo t("login_title"); ?></h1>
        <?php if (isset($_GET["logout"])): ?>
        <p style="color:#4caf50; text-align:center;">
            <?php echo t("session_closed_successfully"); ?>
        </p>
    <?php endif; ?>

    <?php if (($_GET["session"] ?? "") === "replaced"): ?>
        <p style="color:#ffc107; text-align:center;">
            <?php echo t("session_replaced_message"); ?>
        </p>
    <?php endif; ?>

    <?php if (($_GET["session"] ?? "") === "expired"): ?>
        <p style="color:#ffc107; text-align:center;">
            <?php echo t("session_expired_message"); ?>
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

    <p style="margin-top:16px; text-align:center;">
        <a href="/colesterol_game/pages/forgot_password.php">
            <?php echo t("forgot_password"); ?>
        </a>
    </p>

    <p style="margin-top:20px; text-align:center;">

        <?php echo t("no_account"); ?>

        <a href="/colesterol_game/pages/register.php">
            <?php echo t("register_link"); ?>
        </a>

    </p>

</div>

<script>
const LOGIN_I18N = {
    loading: "<?php echo t("login_loading"); ?>",
    success: "<?php echo t("login_success"); ?>",
    failed: "<?php echo t("login_failed"); ?>",
    connectionError: "<?php echo t("connection_error"); ?>"
};
</script>
<script src="/colesterol_game/assets/js/login.js?v=<?php echo $loginJsVersion; ?>"></script>

<script src="/colesterol_game/assets/js/theme.js?v=<?php echo $themeVersion; ?>"></script>
</body>
</html>
