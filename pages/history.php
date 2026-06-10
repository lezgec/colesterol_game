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
    <title><?php echo t("history"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <h1>📊 <?php echo t("history"); ?></h1>

        <div class="top-links">
            <a href="/colesterol_game/pages/player_dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_player_dashboard"); ?>
            </a>
        </div>
    </div>

    <table class="admin-table" width="100%" id="historyTable">

        <thead>
            <tr>
                <th><?php echo t("date"); ?></th>
                <th><?php echo t("score"); ?></th>
                <th><?php echo t("correct_answers"); ?></th>
                <th><?php echo t("precision"); ?></th>
                <th><?php echo t("lives"); ?></th>
                <th><?php echo t("average_difficulty"); ?></th>
                <th><?php echo t("mode"); ?></th>
            </tr>
        </thead>

        <tbody></tbody>
    </table>

</div>

<script>

const HISTORY_I18N = {
    noGames: "<?php echo t('no_games_registered'); ?>",

    error: "<?php echo t('error'); ?>",

    room: "<?php echo t('room'); ?>",

    solo: "<?php echo t('solo'); ?>"
};

fetch("/colesterol_game/backend/game/get_user_results.php")
.then(res => res.json())
.then(response => {

    const tbody = document.querySelector("#historyTable tbody");

    if (!response.success || !Array.isArray(response.results)) {

        tbody.innerHTML =
            `<tr><td colspan="7">${HISTORY_I18N.error}</td></tr>`;

        return;
    }

    const data = response.results;

    if (data.length === 0) {

        tbody.innerHTML =
            `<tr><td colspan="7">${HISTORY_I18N.noGames}</td></tr>`;

        return;
    }

    tbody.innerHTML = "";

    data.forEach(item => {

        const mode = item.room_id
            ? `${HISTORY_I18N.room} #${item.room_id}`
            : HISTORY_I18N.solo;

        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.played_at}</td>
            <td>${item.score}</td>
            <td>${item.correct_answers} / ${item.total_questions}</td>
            <td>${item.precision}%</td>
            <td>${item.lives_remaining}</td>
            <td>${item.final_difficulty} / 5</td>
            <td>${mode}</td>
        `;

        tbody.appendChild(row);

    });

})
.catch(error => {

    console.error(error);

    const tbody = document.querySelector("#historyTable tbody");

    tbody.innerHTML =
        `<tr><td colspan="7">${HISTORY_I18N.error}</td></tr>`;

});
</script>

</body>
</html>
