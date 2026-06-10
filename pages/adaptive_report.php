<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$roomCode = strtoupper(trim($_GET["code"] ?? ""));
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("adaptive_report"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?<?php echo $roomCode ? 'code=' . urlencode($roomCode) . '&' : ''; ?>lang=es">ES</a>
            <span>|</span>
            <a href="?<?php echo $roomCode ? 'code=' . urlencode($roomCode) . '&' : ''; ?>lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="/colesterol_game/pages/admin_reports.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_reports_center"); ?>
            </a>
        </div>
    </div>

    <h1>📈 <?php echo t("adaptive_report"); ?></h1>

    <?php if ($roomCode !== ""): ?>
        <p>
            <strong><?php echo t("room_code"); ?>:</strong>
            <?php echo htmlspecialchars($roomCode); ?>
        </p>
    <?php endif; ?>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <h3><?php echo t("total_players"); ?></h3>
            <p id="total-players">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_answered_questions"); ?></h3>
            <p id="total-answers">...</p>
        </div>
    </section>
    <section class="admin-section">
        <h2>📈 <?php echo t("adaptive_progression_chart"); ?></h2>

        <div class="chart-card">
            <canvas id="adaptiveChart"></canvas>
        </div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("adaptive_progression"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("player_name"); ?></th>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("correct"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("difficulty_level"); ?></th>
                    <th><?php echo t("score"); ?></th>
                </tr>
            </thead>
            <tbody id="timeline-body">
                <tr>
                    <td colspan="7"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("performance_by_player"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("player_name"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="players-body">
                <tr>
                    <td colspan="5"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";

const ADAPTIVE_I18N = {
    noData: "<?php echo t('no_data_available'); ?>",
    yes: "<?php echo t('yes'); ?>",
    no: "<?php echo t('no'); ?>"
};

const endpoint = ROOM_CODE
    ? `/colesterol_game/backend/reports/adaptive_report.php?code=${encodeURIComponent(ROOM_CODE)}`
    : "/colesterol_game/backend/reports/adaptive_report.php";

fetch(endpoint)
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            console.error(data);
            return;
        }

        document.getElementById("total-players").textContent =
            data.summary.total_players;

        document.getElementById("total-answers").textContent =
            data.summary.total_answers;

        renderTimeline(data.timeline);
        renderPlayers(data.players);
        renderAdaptiveChart(data.players);
    })
    .catch(console.error);

function renderTimeline(items) {
    const tbody = document.getElementById("timeline-body");
    tbody.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7">${ADAPTIVE_I18N.noData}</td></tr>`;
        return;
    }

    items.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.player_name}</td>
            <td>${item.question}</td>
            <td>${item.category}</td>
            <td>${item.is_correct ? ADAPTIVE_I18N.yes : ADAPTIVE_I18N.no}</td>
            <td>${item.response_time}s</td>
            <td>${item.difficulty_level} / 5</td>
            <td>${item.score_earned}</td>
        `;

        tbody.appendChild(row);
    });
}

function renderPlayers(players) {
    const tbody = document.getElementById("players-body");
    tbody.innerHTML = "";

    if (!Array.isArray(players) || players.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">${ADAPTIVE_I18N.noData}</td></tr>`;
        return;
    }

    players.forEach(player => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${player.player_name}</td>
            <td>${player.correct_answers} / ${player.total_answers}</td>
            <td>${player.precision}%</td>
            <td>${player.avg_response_time}s</td>
            <td>${player.avg_difficulty} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

function renderAdaptiveChart(players) {
    const ctx = document.getElementById("adaptiveChart");

    if (!ctx || !Array.isArray(players) || players.length === 0) {
        return;
    }

    const datasets = players.slice(0, 6).map(player => {
        return {
            label: player.player_name,
            data: player.points.map((point, index) => ({
                x: index + 1,
                y: point.difficulty_level
            })),
            tension: 0.3
        };
    });

    new Chart(ctx, {
        type: "line",
        data: {
            datasets
        },
        options: {
            responsive: true,
            parsing: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                x: {
                    type: "linear",
                    title: {
                        display: true,
                        text: "<?php echo current_lang() === 'en' ? 'Question number' : 'Número de pregunta'; ?>"
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                y: {
                    beginAtZero: false,
                    min: 1,
                    max: 5,
                    title: {
                        display: true,
                        text: "<?php echo current_lang() === 'en' ? 'Difficulty level' : 'Nivel de dificultad'; ?>"
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>
