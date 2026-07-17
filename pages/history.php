<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';

require_login();

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("history"); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container">

    <div class="top-actions page-centered-top-actions">
        <div class="top-links">
            <a href="<?php echo app_path('pages/player_dashboard.php'); ?>" class="logout-btn secondary-btn">
                <?php echo t("back_to_player_dashboard"); ?>
            </a>
        </div>
    </div>

    <header class="page-title-block">
        <h1><?php echo ui_icon("calendar"); ?> <?php echo t("history"); ?></h1>
        <p><?php echo t("history_description"); ?></p>
    </header>

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
                <th><?php echo t("actions"); ?></th>
            </tr>
        </thead>

        <tbody></tbody>
    </table>

</div>

<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;

const HISTORY_I18N = {
    noGames: "<?php echo t('no_games_registered'); ?>",

    error: "<?php echo t('error'); ?>",

    room: "<?php echo t('room'); ?>",

    solo: "<?php echo t('solo'); ?>",

    viewGameStats: "<?php echo t('view_game_stats'); ?>"
};

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function formatApiMessage(data, fallback) {
    return [data?.message, data?.action]
        .filter(Boolean)
        .join(" ")
        || fallback;
}

fetch(appUrl("backend/game/get_user_results.php"))
.then(res => res.json())
.then(response => {

    const tbody = document.querySelector("#historyTable tbody");

    if (!response.success || !Array.isArray(response.results)) {

        tbody.innerHTML =
            `<tr><td colspan="8">${escapeHtml(formatApiMessage(response, HISTORY_I18N.error))}</td></tr>`;

        return;
    }

    const data = response.results;

    if (data.length === 0) {

        tbody.innerHTML =
            `<tr><td colspan="8">${HISTORY_I18N.noGames}</td></tr>`;

        return;
    }

    tbody.innerHTML = "";

    data.forEach(item => {

        const mode = item.room_id || item.game_mode === "room"
            ? `${HISTORY_I18N.room} #${item.room_id || "-"}`
            : HISTORY_I18N.solo;

        const row = document.createElement("tr");
        const statsUrl = appUrl(
            `pages/game_result_stats.php?result_id=${encodeURIComponent(item.id)}`
        );

        row.innerHTML = `
            <td>${escapeHtml(item.played_at)}</td>
            <td>${escapeHtml(item.score)}</td>
            <td>${escapeHtml(item.correct_answers)} / ${escapeHtml(item.total_questions)}</td>
            <td>${escapeHtml(item.precision)}%</td>
            <td>${escapeHtml(item.lives_remaining)}</td>
            <td>${escapeHtml(item.final_difficulty)} / 5</td>
            <td>${escapeHtml(mode)}</td>
            <td>
                <a class="table-btn edit-btn" href="${escapeHtml(statsUrl)}">
                    ${escapeHtml(HISTORY_I18N.viewGameStats)}
                </a>
            </td>
        `;

        tbody.appendChild(row);

    });

})
.catch(error => {

    console.error(error);

    const tbody = document.querySelector("#historyTable tbody");

    tbody.innerHTML =
        `<tr><td colspan="8">${HISTORY_I18N.error}</td></tr>`;

});
</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
