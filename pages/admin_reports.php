<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["teacher", "super_admin"]);

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

    <title>
        <?php echo t("reports_center"); ?>
    </title>

    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

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
            <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>"
               class="logout-btn secondary-btn">

                <?php echo t("back_dashboard"); ?>
            </a>
        </div>

    </div>

    <h1>
        <?php echo t("reports_center"); ?>
    </h1>
    <section class="dashboard-grid">

        <a href="<?php echo app_path('pages/global_analytics.php'); ?>" class="dashboard-card dashboard-link">
            <h3><?php echo t("global_analytics"); ?></h3>
            <p><?php echo t("global_analytics_description"); ?></p>
        </a>

        <a href="<?php echo app_path('pages/question_analytics.php'); ?>" class="dashboard-card dashboard-link">
            <h3><?php echo t("question_analytics"); ?></h3>
            <p><?php echo t("question_analytics_description"); ?></p>
        </a>

    </section>

    <section class="admin-section">

        <h2>
            <?php echo t("room_reports"); ?>
        </h2>

        <table class="admin-table">

            <thead>
                <tr>
                    <th><?php echo t("room_code"); ?></th>
                    <th><?php echo t("room_name"); ?></th>
                    <th><?php echo t("status"); ?></th>
                    <th><?php echo t("total_players"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                    <th><?php echo t("actions"); ?></th>
                </tr>
            </thead>

            <tbody id="rooms-body">

                <tr>
                    <td colspan="8">
                        <?php echo t("loading"); ?>
                    </td>
                </tr>

            </tbody>

        </table>

    </section>

</div>

<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;

const REPORTS_I18N = {
    noData: "<?php echo t('no_reports_available'); ?>",
    viewReport: "<?php echo t('view_report'); ?>",
    unknownStatus: "<?php echo t('room_status_unknown'); ?>",
    statuses: {
        waiting: "<?php echo t('room_status_waiting'); ?>",
        started: "<?php echo t('room_status_started'); ?>",
        paused: "<?php echo t('room_status_paused'); ?>",
        finished: "<?php echo t('room_status_finished'); ?>"
    }
};

function formatNumber(value, digits = 2) {
    const number = Number(value || 0);
    return Number(number.toFixed(digits)).toString();
}

function formatRoomStatus(status) {
    return REPORTS_I18N.statuses[status] || REPORTS_I18N.unknownStatus;
}

fetch(appUrl("backend/reports/list_room_reports.php"))
    .then(res => res.json())
    .then(data => {

        const tbody =
            document.getElementById("rooms-body");

        tbody.innerHTML = "";

        if (!data.success || !Array.isArray(data.rooms)) {

            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        ${REPORTS_I18N.noData}
                    </td>
                </tr>
            `;

            return;
        }

        if (data.rooms.length === 0) {

            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        ${REPORTS_I18N.noData}
                    </td>
                </tr>
            `;

            return;
        }

        data.rooms.forEach(room => {

            const row =
                document.createElement("tr");

            row.innerHTML = `
                <td>${room.room_code}</td>

                <td>${room.name}</td>

                <td>${formatRoomStatus(room.status)}</td>

                <td>${room.total_players}</td>

                <td>${formatNumber(room.precision)}%</td>

                <td>${formatNumber(room.avg_response_time)}s</td>

                <td>${formatNumber(room.avg_difficulty, 1)} / 5</td>

                <td>
                    <div class="report-row-actions">
                        <a
                            href="${appUrl(`pages/rooms/room_report.php?code=${encodeURIComponent(room.room_code)}`)}"
                            class="table-btn edit-btn"
                        >
                            ${REPORTS_I18N.viewReport}
                        </a>
                    </div>
                </td>
            `;

            tbody.appendChild(row);
        });

    })
    .catch(error => {
        console.error(error);
    });

</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
