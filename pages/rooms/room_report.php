<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';

require_role(["teacher", "super_admin"]);

$styleVersion = filemtime(__DIR__ . '/../../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../../assets/js/responsive_tables.js');
$themeVersion = filemtime(__DIR__ . '/../../assets/js/theme.js');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    header("Location: " . app_path("pages/rooms/index.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("room_report"); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=es">ES</a>
            <span>|</span>
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="<?php echo app_path('pages/rooms/ranking.php?code=' . urlencode($roomCode)); ?>" class="logout-btn secondary-btn">
                <?php echo t("room_ranking"); ?>
            </a>

            <a href="<?php echo app_path('pages/rooms/index.php'); ?>" class="logout-btn secondary-btn">
                <?php echo t("back_to_rooms"); ?>
            </a>
        </div>
    </div>

    <h1><?php echo t("room_report"); ?></h1>

    <section class="report-export-actions" aria-label="<?php echo t("actions"); ?>">
        <a href="<?php echo app_path('backend/exports/export_room_report_csv.php?code=' . urlencode($roomCode)); ?>"
           class="primary-btn">
            <?php echo t("export_csv"); ?>
        </a>
        <a href="<?php echo app_path('backend/exports/pdf/export_room_report_pdf.php?code=' . urlencode($roomCode)); ?>"
           class="primary-btn">
            <?php echo t("export_pdf"); ?>
        </a>
    </section>

    <p class="room-meta">
        <strong><?php echo t("room_code"); ?>:</strong>
        <?php echo htmlspecialchars($roomCode); ?>
    </p>

    <section class="dashboard-grid" id="room-summary">
        <div class="dashboard-card">
            <h3><?php echo t("total_players"); ?></h3>
            <p id="total-players">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_answered_questions"); ?></h3>
            <p id="total-answers">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("precision"); ?></h3>
            <p id="precision">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_response_time"); ?></h3>
            <p id="avg-response-time">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_difficulty"); ?></h3>
            <p id="avg-difficulty">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_points"); ?></h3>
            <p id="total-points">...</p>
        </div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("visual_charts"); ?></h2>

        <div class="analytics-layout">
            <div class="chart-card">
                <h3><?php echo t("ranking"); ?></h3>
                <canvas id="roomRankingChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("performance_by_category"); ?></h3>
                <canvas id="roomCategoryChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("most_failed_questions"); ?></h3>
                <canvas id="roomFailedChart"></canvas>
            </div>
        </div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("room_information"); ?></h2>

        <table class="admin-table">
            <tbody>
                <tr>
                    <th><?php echo t("room_name"); ?></th>
                    <td id="room-name">...</td>
                </tr>
                <tr>
                    <th><?php echo t("status"); ?></th>
                    <td id="room-status">...</td>
                </tr>
                <tr>
                    <th><?php echo t("question_count"); ?></th>
                    <td id="question-count">...</td>
                </tr>
                <tr>
                    <th><?php echo t("time_limit"); ?></th>
                    <td id="time-limit">...</td>
                </tr>
                <tr>
                    <th><?php echo t("initial_difficulty"); ?></th>
                    <td id="initial-difficulty">...</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("ranking"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo t("player_name"); ?></th>
                    <th><?php echo t("score"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="ranking-body">
                <tr>
                    <td colspan="7"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("performance_by_category"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>
            <tbody id="category-body">
                <tr>
                    <td colspan="5"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("most_failed_questions"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("incorrect_answers"); ?></th>
                    <th><?php echo t("failure_rate"); ?></th>
                    <th><?php echo t("most_selected_wrong_option"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                </tr>
            </thead>
            <tbody id="failed-body">
                <tr>
                    <td colspan="6"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("question_statistics"); ?></h2>
        <div id="question-stats-list" class="question-stats-list">
            <?php echo t("loading"); ?>
        </div>
    </section>

</div>

<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";

const REPORT_I18N = {
    noData: "<?php echo t('no_data_available'); ?>",
    error: "<?php echo t('error'); ?>",
    score: "<?php echo t('score'); ?>",
    precisionPercent: "<?php echo t('precision_percent'); ?>",
    failurePercent: "<?php echo t('failure_percent'); ?>",
    answerDistribution: "<?php echo t('answer_distribution'); ?>",
    mostSelectedOption: "<?php echo t('most_selected_option'); ?>",
    mostSelectedWrongOption: "<?php echo t('most_selected_wrong_option'); ?>",
    noOptionSelected: "<?php echo t('no_option_selected'); ?>",
    correctOption: "<?php echo t('correct_option'); ?>",
    correctAnswers: "<?php echo t('correct_answers'); ?>",
    incorrectAnswers: "<?php echo t('incorrect_answers'); ?>",
    answeredCount: "<?php echo t('answered_count'); ?>",
    failureRate: "<?php echo t('failure_rate'); ?>",
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
    return REPORT_I18N.statuses[status] || REPORT_I18N.unknownStatus;
}

function escapeHtml(text) {
    return String(text ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function formatOptionLabel(item, option) {
    if (!option) {
        return REPORT_I18N.noOptionSelected;
    }

    const count = item.option_counts?.[option] ?? item[`${option.toLowerCase()}_count`] ?? 0;
    const text = item.options?.[option] || "";

    return `${option}: ${text} (${count})`;
}

fetch(appUrl(`backend/reports/room_report.php?code=${encodeURIComponent(ROOM_CODE)}`))
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            document.getElementById("ranking-body").innerHTML =
                `<tr><td colspan="7">${data.message || REPORT_I18N.error}</td></tr>`;
            return;
        }

        renderSummary(data.summary);
        renderRoomInfo(data.room);
        renderRanking(data.ranking);
        renderCategories(data.categories);
        renderFailedQuestions(data.most_failed_questions);
        renderQuestionStatistics(data.question_statistics);
        renderRoomCharts(data);
    })
    .catch(error => {
        console.error(error);
    });

function renderSummary(summary) {
    document.getElementById("total-players").textContent = summary.total_players;
    document.getElementById("total-answers").textContent = summary.total_answers;
    document.getElementById("precision").textContent = formatNumber(summary.precision) + "%";
    document.getElementById("avg-response-time").textContent = formatNumber(summary.avg_response_time) + "s";
    document.getElementById("avg-difficulty").textContent = formatNumber(summary.avg_difficulty, 1) + " / 5";
    document.getElementById("total-points").textContent = summary.total_points;
}

function renderRoomInfo(room) {
    document.getElementById("room-name").textContent = room.name;
    document.getElementById("room-status").textContent = formatRoomStatus(room.status);
    document.getElementById("question-count").textContent = room.question_count;
    document.getElementById("time-limit").textContent = room.time_limit + "s";
    document.getElementById("initial-difficulty").textContent = formatNumber(room.initial_difficulty, 1) + " / 5";
}

function renderRanking(ranking) {
    const tbody = document.getElementById("ranking-body");
    tbody.innerHTML = "";

    if (!Array.isArray(ranking) || ranking.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7">${REPORT_I18N.noData}</td></tr>`;
        return;
    }

    ranking.forEach((player, index) => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${escapeHtml(index + 1)}</td>
            <td>${escapeHtml(player.player_name)}</td>
            <td>${escapeHtml(player.total_score)}</td>
            <td>${escapeHtml(player.correct_answers)} / ${escapeHtml(player.total_answers)}</td>
            <td>${escapeHtml(formatNumber(player.precision))}%</td>
            <td>${escapeHtml(formatNumber(player.avg_response_time))}s</td>
            <td>${escapeHtml(formatNumber(player.avg_difficulty, 1))} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

function renderCategories(categories) {
    const tbody = document.getElementById("category-body");
    tbody.innerHTML = "";

    if (!Array.isArray(categories) || categories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">${REPORT_I18N.noData}</td></tr>`;
        return;
    }

    categories.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${escapeHtml(item.category)}</td>
            <td>${escapeHtml(item.correct_answers)} / ${escapeHtml(item.total_answers)}</td>
            <td>${escapeHtml(formatNumber(item.precision))}%</td>
            <td>${escapeHtml(formatNumber(item.avg_response_time))}s</td>
            <td>${escapeHtml(formatNumber(item.avg_difficulty, 1))} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

function renderFailedQuestions(questions) {
    const tbody = document.getElementById("failed-body");
    tbody.innerHTML = "";

    if (!Array.isArray(questions) || questions.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6">${REPORT_I18N.noData}</td></tr>`;
        return;
    }

    questions.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${escapeHtml(item.question)}</td>
            <td>${escapeHtml(item.category)}</td>
            <td>${escapeHtml(item.incorrect_answers)} / ${escapeHtml(item.total_answers)}</td>
            <td>${escapeHtml(formatNumber(item.failure_rate))}%</td>
            <td>${escapeHtml(formatOptionLabel(item, item.most_selected_wrong_option))}</td>
            <td>${escapeHtml(formatNumber(item.avg_response_time))}s</td>
        `;

        tbody.appendChild(row);
    });
}

function renderQuestionStatistics(items) {
    const container = document.getElementById("question-stats-list");

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = `<p>${REPORT_I18N.noData}</p>`;
        return;
    }

    container.innerHTML = items.map(item => {
        const total = Math.max(1, Number(item.total_answers || 0));
        const optionRows = ["A", "B", "C", "D"].map(option => {
            const count = Number(item.option_counts?.[option] || 0);
            const percent = Math.round((count / total) * 100);
            const classes = ["question-option-stat"];

            if (option === item.correct_option) {
                classes.push("is-correct");
            }

            if (option === item.most_selected_wrong_option) {
                classes.push("is-most-wrong");
            }

            return `
                <li class="${classes.join(" ")}">
                    <div class="question-option-stat-row">
                        <strong>${option}</strong>
                        <span>${escapeHtml(item.options?.[option] || "")}</span>
                        <em>${escapeHtml(count)}</em>
                    </div>
                    <div class="question-option-track">
                        <span style="width:${escapeHtml(percent)}%"></span>
                    </div>
                </li>
            `;
        }).join("");

        return `
            <details class="question-stat-card">
                <summary>
                    <span>#${escapeHtml(item.question_id)}</span>
                    <strong>${escapeHtml(item.question)}</strong>
                    <em>${escapeHtml(REPORT_I18N.failureRate)}: ${escapeHtml(formatNumber(item.failure_rate))}%</em>
                </summary>

                <div class="question-stat-body">
                    <div class="question-stat-metrics">
                        <span>${escapeHtml(REPORT_I18N.answeredCount)}: <strong>${escapeHtml(item.total_answers)}</strong></span>
                        <span>${escapeHtml(REPORT_I18N.correctAnswers)}: <strong>${escapeHtml(item.correct_answers)}</strong></span>
                        <span>${escapeHtml(REPORT_I18N.incorrectAnswers)}: <strong>${escapeHtml(item.incorrect_answers)}</strong></span>
                        <span>${escapeHtml(REPORT_I18N.correctOption)}: <strong>${escapeHtml(item.correct_option)}</strong></span>
                        <span>${escapeHtml(REPORT_I18N.mostSelectedWrongOption)}: <strong>${escapeHtml(formatOptionLabel(item, item.most_selected_wrong_option))}</strong></span>
                    </div>

                    <h3>${escapeHtml(REPORT_I18N.answerDistribution)}</h3>
                    <ul class="question-option-stats">
                        ${optionRows}
                    </ul>
                </div>
            </details>
        `;
    }).join("");
}

function renderRoomCharts(data) {
    renderRoomRankingChart(data.ranking || []);
    renderRoomCategoryChart(data.categories || []);
    renderRoomFailedChart(data.most_failed_questions || []);
}

function renderRoomRankingChart(ranking) {
    const ctx = document.getElementById("roomRankingChart");
    if (!ctx || ranking.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ranking.map(p => p.player_name),
            datasets: [{
                label: REPORT_I18N.score,
                data: ranking.map(p => p.total_score)
            }]
        },
        options: {
            responsive: true,
            indexAxis: "y"
        }
    });
}

function renderRoomCategoryChart(categories) {
    const ctx = document.getElementById("roomCategoryChart");
    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                label: REPORT_I18N.precisionPercent,
                data: categories.map(c => c.precision)
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

function renderRoomFailedChart(items) {
    const ctx = document.getElementById("roomFailedChart");
    if (!ctx || items.length === 0) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: items.map(q => `#${q.question_id}`),
            datasets: [{
                label: REPORT_I18N.failurePercent,
                data: items.map(q => q.failure_rate)
            }]
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
</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
