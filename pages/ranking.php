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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("ranking"); ?></title>
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
        <h1><?php echo ui_icon("trophy"); ?> <?php echo t("global_ranking"); ?></h1>
        <p><?php echo t("top_10_global_description"); ?></p>
    </header>

    <table id="rankingTable" class="admin-table ranking-table" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo t("player_name"); ?></th>
                <th><?php echo current_lang() === "en" ? "Location" : "Ubicación"; ?></th>
                <th><?php echo t("best_score"); ?></th>
                <th><?php echo t("total_games"); ?></th>
                <th><?php echo current_lang() === "en" ? "Best streak" : "Mejor racha"; ?></th>
                <th><?php echo current_lang() === "en" ? "Daily" : "Diaria"; ?></th>
                <th><?php echo t("precision"); ?></th>
                <th><?php echo t("average_difficulty"); ?></th>
            </tr>
        </thead>

        <tbody></tbody>
    </table>
</div>

<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;
const escapeHtml = value => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
const RANKING_I18N = {
    noData: "<?php echo t('no_ranking_data'); ?>",
    error: "<?php echo t('error'); ?>"
};

fetch(appUrl("backend/game/get_ranking.php?lang=<?php echo current_lang(); ?>"))
    .then(res => res.json())
    .then(data => {
        const tbody = document.querySelector("#rankingTable tbody");

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">${RANKING_I18N.noData}</td></tr>`;
            return;
        }

        tbody.innerHTML = "";

        data.forEach((player, index) => {
            const row = document.createElement("tr");

            const country = player.country || {};
            const location = [
                country.name ? `${country.flag} ${country.name}` : "",
                player.city || ""
            ].filter(Boolean).join(" · ") || "-";

            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${escapeHtml(player.name)}</td>
                <td>${escapeHtml(location)}</td>
                <td>${escapeHtml(player.best_score)}</td>
                <td>${escapeHtml(player.total_games)}</td>
                <td>${escapeHtml(player.best_correct_streak || 0)}</td>
                <td>${escapeHtml(player.current_daily_streak || 0)}</td>
                <td>${escapeHtml(player.precision)}%</td>
                <td>${escapeHtml(player.avg_difficulty)} / 5</td>
            `;

            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error(error);

        const tbody = document.querySelector("#rankingTable tbody");

        tbody.innerHTML = `<tr><td colspan="9">${RANKING_I18N.error}</td></tr>`;
    });
</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
