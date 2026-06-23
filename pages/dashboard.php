<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';

require_login();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("dashboard"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">

</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="/colesterol_game/pages/player_dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_player_dashboard"); ?>
            </a>

            <a href="/colesterol_game/pages/logout.php" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        </div>
    </div>

    <h1><?php echo ui_icon("analytics"); ?> <?php echo t("dashboard"); ?></h1>
    <p><?php echo t("dashboard_description"); ?></p>

    <div id="dashboard-cards" class="dashboard-grid">
        <div class="dashboard-card">
            <h3><?php echo t("total_games"); ?></h3>
            <p id="total-games">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_score"); ?></h3>
            <p id="avg-score">...</p>
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
    </div>

    <div class="recent-games-section">
        <h2><?php echo t("recent_games"); ?></h2>

        <table id="recentGamesTable" class="admin-table" width="100%">
            <thead>
                <tr>
                    <th><?php echo t("date"); ?></th>
                    <th><?php echo t("score"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("final_difficulty"); ?></th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>
</div>

<script>
const DASHBOARD_I18N = {
    lang: "<?php echo current_lang(); ?>",
    loadError: "<?php echo t('loading_error'); ?>",
    noGames: "<?php echo t('no_games_registered'); ?>",
    error: "<?php echo t('error'); ?>"
};

fetch(`/colesterol_game/backend/dashboard/get_dashboard.php?lang=${encodeURIComponent(DASHBOARD_I18N.lang)}`)
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            document.getElementById("dashboard-cards").innerHTML = `<p>${DASHBOARD_I18N.loadError}</p>`;
            return;
        }

        document.getElementById("total-games").textContent = data.total_games;
        document.getElementById("avg-score").textContent = data.avg_score;
        document.getElementById("best-score").textContent = data.best_score;
        document.getElementById("accuracy").textContent = data.accuracy + "%";
        document.getElementById("avg-difficulty").textContent = data.average_difficulty + " / 5";

        const tbody = document.querySelector("#recentGamesTable tbody");

        if (!Array.isArray(data.recent_games) || data.recent_games.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4">${DASHBOARD_I18N.noGames}</td></tr>`;
            return;
        }

        tbody.innerHTML = "";

        data.recent_games.forEach(game => {
            const row = document.createElement("tr");

            row.innerHTML = `
                <td>${game.played_at}</td>
                <td>${game.score}</td>
                <td>${game.correct_answers} / ${game.total_questions}</td>
                <td>${game.final_difficulty} / 5</td>
            `;

            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error(error);
        document.getElementById("dashboard-cards").innerHTML = `<p>${DASHBOARD_I18N.error}</p>`;
    });
</script>


<script src="/colesterol_game/assets/js/responsive_tables.js"></script>
<script src="/colesterol_game/assets/js/theme.js"></script>
</body>
</html>
