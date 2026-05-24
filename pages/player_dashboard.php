<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["player"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$userName = $_SESSION["user_name"] ?? "Player";
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("player_dashboard"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a> |
            <a href="?lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/logout.php" class="logout-btn">
            <?php echo t("logout"); ?>
        </a>
    </div>

    <h1>🎮 <?php echo t("player_dashboard"); ?></h1>

    <p class="player-welcome">
        <?php echo current_lang() === "en" ? "Welcome," : "Bienvenido,"; ?>
        <strong><?php echo htmlspecialchars($userName); ?></strong>
    </p>

    <section class="dashboard-grid" id="player-summary">
        <div class="dashboard-card">
            <h3><?php echo t("total_games"); ?></h3>
            <p id="total-games">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("best_score"); ?></h3>
            <p id="best-score">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("precision"); ?></h3>
            <p id="accuracy">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_difficulty"); ?></h3>
            <p id="avg-difficulty">...</p>
        </div>
    </section>

    <h2 class="player-options-title"><?php echo t("player_options"); ?></h2>
    <section class="dashboard-grid">

        <a href="/colesterol_game/pages/game.php" class="dashboard-card dashboard-link">
            <h3>🎮 <?php echo t("start_solo_game"); ?></h3>
            <p><?php echo t("start_solo_game_description"); ?></p>
        </a>

        <a href="/colesterol_game/pages/rooms/join.php" class="dashboard-card dashboard-link">
            <h3>👥 <?php echo t("join_room"); ?></h3>
            <p><?php echo t("join_room_description"); ?></p>
        </a>

        <a href="/colesterol_game/pages/history.php" class="dashboard-card dashboard-link">
            <h3>📊 <?php echo t("history"); ?></h3>
            <p><?php echo t("history_description"); ?></p>
        </a>

        <a href="/colesterol_game/pages/ranking.php" class="dashboard-card dashboard-link">
            <h3>🏆 <?php echo t("ranking"); ?></h3>
            <p><?php echo t("ranking_description"); ?></p>
        </a>

        <a href="/colesterol_game/index.php" class="dashboard-card dashboard-link">
            <h3>🏠 <?php echo t("public_view"); ?></h3>
            <p><?php echo t("public_view_description"); ?></p>
        </a>

    </section>

</div>

<script>
fetch("/colesterol_game/backend/dashboard/get_dashboard.php")
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

</body>
</html>