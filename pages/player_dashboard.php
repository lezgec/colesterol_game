<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';
require_once __DIR__ . '/../includes/user_menu.php';

require_role(["player"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$userName = $_SESSION["user_name"] ?? "Player";
$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("player_dashboard"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?v=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">

</head>
<body>

<div class="game-container player-dashboard-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <?php render_user_menu(); ?>
    </div>

    <header class="player-dashboard-hero">
        <div>
            <p class="player-welcome">
                <?php echo current_lang() === "en" ? "Welcome back," : "Bienvenido de nuevo,"; ?>
                <strong><?php echo htmlspecialchars($userName); ?></strong>
            </p>

            <h1><?php echo ui_icon("gamepad"); ?> <?php echo t("player_dashboard"); ?></h1>
        </div>

        <a href="/colesterol_game/pages/game.php" class="player-hero-action">
            <?php echo ui_icon("zap"); ?>
            <span><?php echo t("start_solo_game"); ?></span>
        </a>
    </header>

    <section class="dashboard-grid player-summary-grid" id="player-summary">
        <div class="dashboard-card player-stat-card stat-games">
            <span class="player-stat-icon"><?php echo ui_icon("gamepad"); ?></span>
            <h3><?php echo t("total_games"); ?></h3>
            <p id="total-games">...</p>
        </div>

        <div class="dashboard-card player-stat-card stat-score">
            <span class="player-stat-icon"><?php echo ui_icon("trophy"); ?></span>
            <h3><?php echo t("best_score"); ?></h3>
            <p id="best-score">...</p>
        </div>

        <div class="dashboard-card player-stat-card stat-accuracy">
            <span class="player-stat-icon"><?php echo ui_icon("target"); ?></span>
            <h3><?php echo t("precision"); ?></h3>
            <p id="accuracy">...</p>
        </div>

        <div class="dashboard-card player-stat-card stat-difficulty">
            <span class="player-stat-icon"><?php echo ui_icon("analytics"); ?></span>
            <h3><?php echo t("average_difficulty"); ?></h3>
            <p id="avg-difficulty">...</p>
        </div>
    </section>

    <section class="player-actions-section">
        <div class="player-section-heading">
            <h2 class="player-options-title"><?php echo t("player_options"); ?></h2>
            <span><?php echo current_lang() === "en" ? "Choose your next move" : "Elige tu siguiente paso"; ?></span>
        </div>

        <div class="dashboard-grid player-actions-grid">

        <a href="/colesterol_game/pages/game.php" class="dashboard-card dashboard-link player-action-card action-play">
            <h3><?php echo ui_icon("gamepad"); ?> <?php echo t("start_solo_game"); ?></h3>
            <p><?php echo t("start_solo_game_description"); ?></p>
        </a>

        <a href="/colesterol_game/pages/rooms/join.php" class="dashboard-card dashboard-link player-action-card action-room">
            <h3><?php echo ui_icon("users"); ?> <?php echo t("join_room"); ?></h3>
            <p><?php echo t("join_room_description"); ?></p>
        </a>

        <a href="/colesterol_game/pages/history.php" class="dashboard-card dashboard-link player-action-card action-history">
            <h3><?php echo ui_icon("analytics"); ?> <?php echo t("history"); ?></h3>
            <p><?php echo t("history_description"); ?></p>
        </a>

        <a href="/colesterol_game/pages/ranking.php" class="dashboard-card dashboard-link player-action-card action-ranking">
            <h3><?php echo ui_icon("trophy"); ?> <?php echo t("ranking"); ?></h3>
            <p><?php echo t("ranking_description"); ?></p>
        </a>

        <a href="/colesterol_game/index.php" class="dashboard-card dashboard-link player-action-card action-public">
            <h3><?php echo ui_icon("home"); ?> <?php echo t("public_view"); ?></h3>
            <p><?php echo t("public_view_description"); ?></p>
        </a>
        <a href="/colesterol_game/pages/player_profile.php" class="dashboard-card dashboard-link player-action-card action-profile">
            <h3><?php echo ui_icon("users"); ?> <?php echo t("player_profile"); ?></h3>
            <p><?php echo t("player_profile_description"); ?></p>
        </a>

        </div>
    </section>

</div>

<script>
const PLAYER_DASHBOARD_LANG = "<?php echo current_lang(); ?>";

fetch(`/colesterol_game/backend/dashboard/get_dashboard.php?lang=${encodeURIComponent(PLAYER_DASHBOARD_LANG)}`)
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;

        document.getElementById("total-games").textContent = data.total_games;
        document.getElementById("best-score").textContent = data.best_score;
        document.getElementById("accuracy").textContent = data.accuracy + "%";
        document.getElementById("avg-difficulty").textContent = data.average_difficulty + " / 5";
    })
    .catch(console.error);
</script>

<script src="/colesterol_game/assets/js/theme.js?v=<?php echo $themeVersion; ?>"></script>
</body>
</html>
