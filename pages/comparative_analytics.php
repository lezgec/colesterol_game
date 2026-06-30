<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("comparative_analytics"); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container admin-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <a href="<?php echo app_path('pages/admin_reports.php'); ?>" class="logout-btn secondary-btn">
            <?php echo t("back_to_reports_center"); ?>
        </a>
    </div>

    <h1><?php echo ui_icon("analytics"); ?> <?php echo t("comparative_analytics"); ?></h1>

    <section class="admin-section">
        <h2><?php echo t("comparative_charts"); ?></h2>

        <div class="analytics-layout">

            <div class="chart-card">
                <h3><?php echo t("players_comparison"); ?></h3>
                <canvas id="playersComparisonChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("rooms_comparison"); ?></h3>
                <canvas id="roomsComparisonChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("categories_comparison"); ?></h3>
                <canvas id="categoriesComparisonChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("difficulty_comparison"); ?></h3>
                <canvas id="difficultyComparisonChart"></canvas>
            </div>

        </div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("game_modes_comparison"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("game_mode"); ?></th>
                    <th><?php echo t("total_answered_questions"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="modes-body">
                <tr><td colspan="6"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("players_comparison"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("player_name"); ?></th>
                    <th><?php echo t("score"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                    <th><?php echo t("max_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="players-body">
                <tr><td colspan="7"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("rooms_comparison"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("room_code"); ?></th>
                    <th><?php echo t("room_name"); ?></th>
                    <th><?php echo t("total_players"); ?></th>
                    <th><?php echo t("total_answered_questions"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="rooms-body">
                <tr><td colspan="7"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("categories_comparison"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("total_answered_questions"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="categories-body">
                <tr><td colspan="6"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

</div>

<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;
const COMPARATIVE_I18N = {
    noData: "<?php echo t('no_data_available'); ?>",
    precisionPercent: "<?php echo t('precision_percent'); ?>",
    avgDifficultyShort: "<?php echo t('avg_difficulty_short'); ?>",
    avgResponseTimeShort: "<?php echo t('avg_response_time_short'); ?>"
};

fetch(appUrl("backend/reports/comparative_analytics_report.php"))
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            console.error(data);
            return;
        }

        renderCharts(data);
        renderModes(data.modes || []);
        renderPlayers(data.players || []);
        renderRooms(data.rooms || []);
        renderCategories(data.categories || []);
    })
    .catch(console.error);

function renderCharts(data) {
    renderPlayersComparisonChart(data.players || []);
    renderRoomsComparisonChart(data.rooms || []);
    renderCategoriesComparisonChart(data.categories || []);
    renderDifficultyComparisonChart(data.difficulty_levels || []);
}

function renderPlayersComparisonChart(players) {
    const ctx = document.getElementById("playersComparisonChart");
    if (!ctx || players.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: players.slice(0, 10).map(p => p.label),
            datasets: [
                {
                    label: COMPARATIVE_I18N.precisionPercent,
                    data: players.slice(0, 10).map(p => p.precision)
                },
                {
                    label: COMPARATIVE_I18N.avgDifficultyShort,
                    data: players.slice(0, 10).map(p => p.avg_difficulty * 20)
                }
            ]
        },
        options: {
            responsive: true,
            indexAxis: "y",
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function renderRoomsComparisonChart(rooms) {
    const ctx = document.getElementById("roomsComparisonChart");
    if (!ctx || rooms.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: rooms.slice(0, 10).map(r => r.room_code),
            datasets: [{
                label: COMPARATIVE_I18N.precisionPercent,
                data: rooms.slice(0, 10).map(r => r.precision)
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function renderCategoriesComparisonChart(categories) {
    const ctx = document.getElementById("categoriesComparisonChart");
    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "radar",
        data: {
            labels: categories.map(c => c.label),
            datasets: [{
                label: COMPARATIVE_I18N.precisionPercent,
                data: categories.map(c => c.precision)
            }]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    min: 0,
                    max: 100
                }
            }
        }
    });
}

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function renderDifficultyComparisonChart(levels) {
    const ctx = document.getElementById("difficultyComparisonChart");
    if (!ctx || levels.length === 0) return;

    new Chart(ctx, {
        type: "line",
        data: {
            labels: levels.map(d => d.label),
            datasets: [
                {
                    label: COMPARATIVE_I18N.precisionPercent,
                    data: levels.map(d => d.precision),
                    tension: 0.3
                },
                {
                    label: COMPARATIVE_I18N.avgResponseTimeShort,
                    data: levels.map(d => d.avg_response_time),
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true
        }
    });
}

function renderModes(modes) {
    const tbody = document.getElementById("modes-body");
    tbody.innerHTML = "";

    if (!Array.isArray(modes) || modes.length === 0) {
        tbody.innerHTML = emptyRow(6);
        return;
    }

    modes.forEach(mode => {
        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(mode.game_mode)}</td>
                <td>${escapeHtml(mode.total_answers)}</td>
                <td>${escapeHtml(mode.correct_answers)}</td>
                <td>${escapeHtml(mode.precision)}%</td>
                <td>${escapeHtml(mode.avg_response_time)}s</td>
                <td>${escapeHtml(mode.avg_difficulty)} / 5</td>
            </tr>
        `;
    });
}

function renderPlayers(players) {
    const tbody = document.getElementById("players-body");
    tbody.innerHTML = "";

    if (!Array.isArray(players) || players.length === 0) {
        tbody.innerHTML = emptyRow(7);
        return;
    }

    players.forEach(player => {
        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(player.label)}</td>
                <td>${escapeHtml(player.total_score)}</td>
                <td>${escapeHtml(player.correct_answers)} / ${escapeHtml(player.total_answers)}</td>
                <td>${escapeHtml(player.precision)}%</td>
                <td>${escapeHtml(player.avg_response_time)}s</td>
                <td>${escapeHtml(player.avg_difficulty)} / 5</td>
                <td>${escapeHtml(player.max_difficulty)} / 5</td>
            </tr>
        `;
    });
}

function renderRooms(rooms) {
    const tbody = document.getElementById("rooms-body");
    tbody.innerHTML = "";

    if (!Array.isArray(rooms) || rooms.length === 0) {
        tbody.innerHTML = emptyRow(7);
        return;
    }

    rooms.forEach(room => {
        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(room.room_code)}</td>
                <td>${escapeHtml(room.name)}</td>
                <td>${escapeHtml(room.total_players)}</td>
                <td>${escapeHtml(room.total_answers)}</td>
                <td>${escapeHtml(room.precision)}%</td>
                <td>${escapeHtml(room.avg_response_time)}s</td>
                <td>${escapeHtml(room.avg_difficulty)} / 5</td>
            </tr>
        `;
    });
}

function renderCategories(categories) {
    const tbody = document.getElementById("categories-body");
    tbody.innerHTML = "";

    if (!Array.isArray(categories) || categories.length === 0) {
        tbody.innerHTML = emptyRow(6);
        return;
    }

    categories.forEach(category => {
        tbody.innerHTML += `
            <tr>
                <td>${escapeHtml(category.category)}</td>
                <td>${escapeHtml(category.total_answers)}</td>
                <td>${escapeHtml(category.correct_answers)}</td>
                <td>${escapeHtml(category.precision)}%</td>
                <td>${escapeHtml(category.avg_response_time)}s</td>
                <td>${escapeHtml(category.avg_difficulty)} / 5</td>
            </tr>
        `;
    });
}

function emptyRow(colspan) {
    return `<tr><td colspan="${colspan}">${COMPARATIVE_I18N.noData}</td></tr>`;
}
</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>"></script>
</body>
</html>
