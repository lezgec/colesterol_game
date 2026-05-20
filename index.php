<?php
require_once __DIR__ . '/lang/translate.php';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("app_title"); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" ref="/colesterol_game/assets/css/style.css">
<link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container landing-container">

    <div class="landing-topbar">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>
    </div>

<div class="hero-section">
    <div class="hero-title">
        <img
            src="/colesterol_game/assets/icons/icon.svg"
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
        <a href="/colesterol_game/pages/game.php"
           class="primary-btn landing-btn">
            🎮 <?php echo t("play_solo"); ?>
        </a>

        <a href="/colesterol_game/pages/rooms/join.php"
           class="primary-btn landing-btn secondary-landing">
            👥 <?php echo t("join_room"); ?>
        </a>

        <a href="/colesterol_game/pages/login.php"
        class="admin-login-link">
            🔐 <?php echo t("admin_login"); ?>
        </a>

    </div>

</div>

</body>
</html>