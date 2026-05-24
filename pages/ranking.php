<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_login();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("ranking"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <h1>🏆 <?php echo t("ranking"); ?></h1>

        <div class="top-links">
            <a href="/colesterol_game/pages/game.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_game"); ?>
            </a>
        </div>
    </div>

    <p>
        <?php echo current_lang() === "en"
            ? "Top 10 players based on their best registered score."
            : "Se muestran los 10 mejores jugadores según su mejor puntaje registrado."; ?>
    </p>

    <table id="rankingTable" class="admin-table" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo t("player_name"); ?></th>
                <th><?php echo t("best_score"); ?></th>
                <th><?php echo t("total_games"); ?></th>
                <th><?php echo t("precision"); ?></th>
                <th><?php echo t("average_difficulty"); ?></th>
            </tr>
        </thead>

        <tbody></tbody>
    </table>
</div>

<script>
const RANKING_I18N = {
    noData: "<?php echo current_lang() === 'en' ? 'No ranking data available' : 'No hay datos de ranking disponibles'; ?>",
    error: "<?php echo t('error'); ?>"
};

fetch("/colesterol_game/backend/game/get_ranking.php")
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector("#rankingTable tbody");

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">${RANKING_I18N.noData}</td></tr>`;
            return;
        }

        tbody.innerHTML = "";

        data.forEach((player, index) => {
            const row = document.createElement("tr");

            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${player.name}</td>
                <td>${player.best_score}</td>
                <td>${player.total_games}</td>
                <td>${player.precision}%</td>
                <td>${player.avg_difficulty} / 5</td>
            `;

            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error(error);

        const tbody = document.querySelector("#rankingTable tbody");

        tbody.innerHTML = `<tr><td colspan="6">${RANKING_I18N.error}</td></tr>`;
    });
</script>

</body>
</html>