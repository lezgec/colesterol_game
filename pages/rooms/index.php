<?php
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../../assets/includes/auth.php';

$isLogged = isset($_SESSION["user_id"]);
$role = $_SESSION["user_role"] ?? null;
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("rooms_title"); ?></title>

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

        <?php if ($isLogged): ?>
            <a href="/colesterol_game/logout.php" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        <?php endif; ?>

    </div>

    <h1><?php echo t("rooms_title"); ?></h1>

    <p class="subtitle-text">
        <?php
        echo current_lang() === "en"
            ? "Play educational multiplayer matches in real time."
            : "Juega partidas educativas multijugador en tiempo real.";
        ?>
    </p>

    <?php if ($isLogged && in_array($role, ["teacher", "super_admin"])): ?>

        <a href="/colesterol_game/pages/rooms/create.php"
           class="primary-btn"
           style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">

            <?php echo t("create_room"); ?>
        </a>

    <?php endif; ?>

    <a href="/colesterol_game/pages/rooms/join.php"
       class="primary-btn secondary-dark-btn"
       style="display:block; text-align:center; text-decoration:none;">

        <?php echo t("join_room"); ?>
    </a>

    <br>

    <?php if ($isLogged && in_array($role, ["teacher", "super_admin"])): ?>

        <a href="/colesterol_game/pages/admin_dashboard.php"
           class="secondary-link">
            <?php
            echo current_lang() === "en"
                ? "Back to dashboard"
                : "Volver al panel";
            ?>
        </a>

    <?php else: ?>

        <a href="/colesterol_game/index.php"
           class="secondary-link">
            <?php echo t("back"); ?>
        </a>

    <?php endif; ?>

</div>

</body>
</html>