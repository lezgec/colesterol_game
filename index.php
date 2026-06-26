<?php
require_once __DIR__ . '/lang/translate.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ui_icons.php';

$isLogged = current_session_is_active();
$role = $isLogged ? current_user_role() : "guest";
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("app_title"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>

<body>

<div class="game-container landing-container">

    <div class="landing-topbar">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <?php if ($isLogged): ?>
            <a href="<?php echo app_path('pages/logout.php'); ?>" class="admin-login-link">
                <?php echo t("logout"); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="hero-section">
        <div class="hero-title">
            <img
                src="<?php echo asset_path('icons/icon.svg'); ?>"
                alt="Game Logo"
                class="hero-logo"
            >

            <h1><?php echo t("app_title"); ?></h1>
        </div>

        <p>
            <?php echo t("landing_description"); ?>
        </p>
    </div>

    <div class="landing-actions">

        <a href="<?php echo app_path('pages/game.php'); ?>"
           class="primary-btn landing-btn">
            <?php echo ui_icon("gamepad", "ui-icon landing-btn-icon"); ?>
            <?php echo t("play_solo"); ?>
        </a>

        <a href="<?php echo app_path('pages/rooms/join.php'); ?>"
           class="primary-btn landing-btn secondary-landing">
            <?php echo ui_icon("users", "ui-icon landing-btn-icon"); ?>
            <?php echo t("join_room"); ?>
        </a>

        <?php if ($isLogged && in_array($role, ["teacher", "super_admin"], true)): ?>

            <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>"
               class="admin-login-link">
                <?php echo ui_icon("school", "ui-icon landing-btn-icon"); ?>
                <?php echo t("admin_dashboard"); ?>
            </a>

        <?php elseif ($isLogged): ?>

            <a href="<?php echo app_path('pages/player_dashboard.php'); ?>"
               class="admin-login-link">
                <?php echo ui_icon("gamepad", "ui-icon landing-btn-icon"); ?>
                <?php echo t("player_dashboard"); ?>
            </a>

        <?php else: ?>

            <a href="<?php echo app_path('pages/login.php'); ?>"
               class="admin-login-link">
                <?php echo ui_icon("users", "ui-icon landing-btn-icon"); ?>
                <?php echo t("login_title"); ?>
            </a>

        <?php endif; ?>

    </div>

</div>

<script src="<?php echo asset_path('js/theme.js'); ?>"></script>
</body>
</html>
