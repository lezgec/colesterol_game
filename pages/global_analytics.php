<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("global_analytics"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/admin_reports.php" class="logout-btn secondary-btn">
            <?php echo t("back_to_reports_center"); ?>
        </a>
    </div>

    <h1>📈 <?php echo t("global_analytics"); ?></h1>
    <a href="/colesterol_game/backend/exports/export_global_analytics_csv.php"
    class="primary-btn"
    style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        📥 <?php echo t("export_csv"); ?>
    </a>
    <a href="/colesterol_game/backend/exports/pdf/export_global_analytics_pdf.php"
    class="primary-btn"
    style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        📄 <?php echo t("export_pdf"); ?>
    </a>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <h3><?php echo t("total_users"); ?></h3>
            <p id="total-users">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_games"); ?></h3>
            <p id="total-games">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_answered_questions"); ?></h3>
            <p id="total-answers">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("precision"); ?></h3>
            <p id="global-precision">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_response_time"); ?></h3>
            <p id="avg-response-time">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_difficulty"); ?></h3>
            <p id="avg-difficulty">...</p>
        </div>
    </section>
    <section class="admin-section">
        <h2>📊 <?php echo t("visual_charts"); ?></h2>

        <div class="analytics-layout">
            <div class="chart-card">
                <h3><?php echo t("performance_by_category"); ?></h3>
                <canvas id="categoryChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("top_players"); ?></h3>
                <canvas id="playersChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("room_reports"); ?></h3>
                <canvas id="roomsChart"></canvas>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("performance_by_category"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("total_answered_questions"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                </tr>
            </thead>
            <tbody id="categories-body">
                <tr><td colspan="4"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("top_players"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo t("player_name"); ?></th>
                    <th><?php echo t("score"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                </tr>
            </thead>
            <tbody id="players-body">
                <tr><td colspan="5"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("room_reports"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("room_code"); ?></th>
                    <th><?php echo t("room_name"); ?></th>
                    <th><?php echo t("total_players"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                </tr>
            </thead>
            <tbody id="rooms-body">
                <tr><td colspan="5"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

</div>

<script>
const GA_I18N = {
    noData: "<?php echo t('no_data_available'); ?>",
    score: "<?php echo t('score'); ?>",
    precisionPercent: "<?php echo t('precision_percent'); ?>",
    players: "<?php echo t('total_players'); ?>"
};

fetch("/colesterol_game/backend/reports/global_analytics_report.php")
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            console.error(data);
            return;
        }

        renderSummary(data.summary);
        renderCategories(data.categories);
        renderTopPlayers(data.top_players);
        renderTopRooms(data.top_rooms);
        renderCharts(data);
    })
    .catch(console.error);

function renderSummary(summary) {
    document.getElementById("total-users").textContent = summary.total_users ?? 0;
    document.getElementById("total-games").textContent = summary.total_games ?? 0;
    document.getElementById("total-answers").textContent = summary.total_answers ?? 0;
    document.getElementById("global-precision").textContent = (summary.global_precision ?? 0) + "%";
    document.getElementById("avg-response-time").textContent = (summary.avg_response_time ?? 0) + "s";
    document.getElementById("avg-difficulty").textContent = (summary.avg_difficulty ?? 0) + " / 5";
}

function renderCategories(categories) {
    const tbody = document.getElementById("categories-body");
    tbody.innerHTML = "";

    if (!Array.isArray(categories) || categories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4">${GA_I18N.noData}</td></tr>`;
        return;
    }

    categories.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td>${item.category}</td>
                <td>${item.total_answers}</td>
                <td>${item.precision}%</td>
                <td>${item.avg_response_time}s</td>
            </tr>
        `;
    });
}

function renderTopPlayers(players) {
    const tbody = document.getElementById("players-body");
    tbody.innerHTML = "";

    if (!Array.isArray(players) || players.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">${GA_I18N.noData}</td></tr>`;
        return;
    }

    players.forEach((player, index) => {
        tbody.innerHTML += `
            <tr>
                <td>${index + 1}</td>
                <td>${player.player_name}</td>
                <td>${player.total_score}</td>
                <td>${player.correct_answers} / ${player.total_answers}</td>
                <td>${player.precision}%</td>
            </tr>
        `;
    });
}

function renderTopRooms(rooms) {
    const tbody = document.getElementById("rooms-body");
    tbody.innerHTML = "";

    if (!Array.isArray(rooms) || rooms.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">${GA_I18N.noData}</td></tr>`;
        return;
    }

    rooms.forEach(room => {
        tbody.innerHTML += `
            <tr>
                <td>${room.room_code}</td>
                <td>${room.name}</td>
                <td>${room.total_players}</td>
                <td>${room.precision}%</td>
                <td>${room.avg_response_time}s</td>
            </tr>
        `;
    });
}
function renderCharts(data) {
    renderCategoryChart(data.categories || []);
    renderPlayersChart(data.top_players || []);
    renderRoomsChart(data.top_rooms || []);
}

function renderCategoryChart(categories) {
    const ctx = document.getElementById("categoryChart");

    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                label: GA_I18N.precisionPercent,
                data: categories.map(c => c.precision)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function renderPlayersChart(players) {
    const ctx = document.getElementById("playersChart");

    if (!ctx || players.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: players.map(p => p.player_name),
            datasets: [{
                label: GA_I18N.score,
                data: players.map(p => p.total_score)
            }]
        },
        options: {
            responsive: true,
            indexAxis: "y"
        }
    });
}

function renderRoomsChart(rooms) {
    const ctx = document.getElementById("roomsChart");

    if (!ctx || rooms.length === 0) return;

    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: rooms.map(r => r.room_code),
            datasets: [{
                label: GA_I18N.players,
                data: rooms.map(r => r.total_players)
            }]
        },
        options: {
            responsive: true
        }
    });
}
</script>

</body>
</html>
