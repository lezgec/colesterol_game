<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["player"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$userName = $_SESSION["user_name"] ?? "Player";
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("player_profile"); ?></title>
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

        <div class="top-links">
            <a href="/colesterol_game/pages/player_dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_player_dashboard"); ?>
            </a>

            <a href="/colesterol_game/pages/logout.php" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        </div>
    </div>

    <h1>👤 <?php echo t("player_profile"); ?></h1>

    <a href="/colesterol_game/backend/exports/export_player_profile_csv.php"
    class="primary-btn"
    style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        📥 <?php echo t("export_csv"); ?>
    </a>
    <a href="/colesterol_game/backend/exports/pdf/export_player_profile_pdf.php"
    class="primary-btn"
    style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        📄 <?php echo t("export_pdf"); ?>
    </a>

    <p class="player-welcome">
        <?php echo t("learning_report_for"); ?>
        <strong><?php echo htmlspecialchars($userName); ?></strong>
    </p>


    <section class="dashboard-grid" id="profile-summary">
        <div class="dashboard-card">
            <h3><?php echo t("total_answered_questions"); ?></h3>
            <p id="total-answers">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("precision"); ?></h3>
            <p id="precision">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_difficulty"); ?></h3>
            <p id="avg-difficulty">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("max_difficulty"); ?></h3>
            <p id="max-difficulty">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_response_time"); ?></h3>
            <p id="avg-response-time">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_points"); ?></h3>
            <p id="total-points">...</p>
        </div>
    </section>
    <section class="admin-section">
        <h2>📊 <?php echo t("visual_analytics"); ?></h2>

        <div class="analytics-layout">

            <div class="chart-card">
                <h3><?php echo t("difficulty_progression"); ?></h3>
                <canvas id="difficultyChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("category_precision"); ?></h3>
                <canvas id="categoryRadarChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("mistakes_distribution"); ?></h3>
                <canvas id="mistakesChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("response_time_analysis"); ?></h3>
                <canvas id="responseTimeChart"></canvas>
            </div>

        </div>
    </section>
    <section class="admin-section">
        <h2>🧠 <?php echo t("smart_insights"); ?></h2>

        <div id="insights-container" class="insights-grid"></div>
    </section>
    <section class="admin-section">
        <h2>🏅 <?php echo t("badges"); ?></h2>

        <div id="badges-container" class="badges-grid"></div>
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
        <h2><?php echo t("your_mistakes"); ?></h2>

        <div id="mistakes-list">
            <?php echo t("loading"); ?>
        </div>
    </section>

</div>

<script>
const PROFILE_I18N = {
    noData: "<?php echo t('no_data_available'); ?>",
    noMistakes: "<?php echo t('no_mistakes_recorded'); ?>",
    noInsights: "<?php echo t('no_insights_available'); ?>",
    noBadges: "<?php echo t('no_badges_earned'); ?>",
    selectedAnswer: "<?php echo t('selected_answer'); ?>",
    correctAnswer: "<?php echo t('correct_answer'); ?>",
    difficulty: "<?php echo t('difficulty'); ?>",
    precisionPercent: "<?php echo t('precision_percent'); ?>",
    avgResponseTimeShort: "<?php echo t('avg_response_time_short'); ?>"
};

fetch("/colesterol_game/backend/reports/player_profile_report.php")
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            return;
        }

        const summary = data.summary;

        document.getElementById("total-answers").textContent = summary.total_answers;
        document.getElementById("precision").textContent = summary.precision + "%";
        document.getElementById("avg-difficulty").textContent = summary.avg_difficulty + " / 5";
        document.getElementById("max-difficulty").textContent = summary.max_difficulty + " / 5";
        document.getElementById("avg-response-time").textContent = summary.avg_response_time + "s";
        document.getElementById("total-points").textContent = summary.total_points;

        renderCategories(data.categories);
        renderMistakes(data.mistakes);
        renderCharts(data);
        loadInsights();
        loadBadges();
    })
    .catch(console.error);

function renderCategories(categories) {
    const tbody = document.getElementById("category-body");
    tbody.innerHTML = "";

    if (!Array.isArray(categories) || categories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">${PROFILE_I18N.noData}</td></tr>`;
        return;
    }

    categories.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.category}</td>
            <td>${item.correct} / ${item.total}</td>
            <td>${item.precision}%</td>
            <td>${item.avg_time}s</td>
            <td>${item.avg_difficulty} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

function renderMistakes(mistakes) {
    const container = document.getElementById("mistakes-list");
    container.innerHTML = "";

    if (!Array.isArray(mistakes) || mistakes.length === 0) {
        container.innerHTML = `<p>${PROFILE_I18N.noMistakes}</p>`;
        return;
    }

    mistakes.forEach(item => {
        const card = document.createElement("div");
        card.classList.add("mistake-card");
        const options = item.options || {};
        const optionRows = ["A", "B", "C", "D"].map(letter => {
            const classes = ["mistake-option"];
            const badges = [];

            if (letter === item.selected_option) {
                classes.push("is-selected");
                badges.push(PROFILE_I18N.selectedAnswer);
            }

            if (letter === item.correct_option) {
                classes.push("is-correct");
                badges.push(PROFILE_I18N.correctAnswer);
            }

            return `
                <li class="${classes.join(" ")}">
                    <strong>${letter}</strong>
                    <span>${options[letter] || ""}</span>
                    ${badges.length > 0
                        ? `<em>${badges.join(" / ")}</em>`
                        : ""}
                </li>
            `;
        }).join("");

        card.innerHTML = `
            <h3>${item.question}</h3>

            <p>
                <strong>${item.category}</strong> • 
                ${PROFILE_I18N.difficulty} ${item.difficulty_level} / 5 • 
                ${item.response_time}s
            </p>

            <ul class="mistake-options-list">
                ${optionRows}
            </ul>

            <p>${item.explanation}</p>

            <small>${item.answered_at}</small>
        `;

        container.appendChild(card);
    });
}

function renderCharts(data) {
    renderDifficultyChart(data.answers || []);
    renderCategoryRadarChart(data.categories || []);
    renderMistakesChart(data.mistake_distribution || []);
    renderResponseTimeChart(data.categories || []);
}

function renderDifficultyChart(answers) {

    const ctx =
        document.getElementById("difficultyChart");

    if (!ctx || answers.length === 0) return;

    new Chart(ctx, {
        type: "line",
        data: {
            labels: answers.map((_, i) => i + 1),

            datasets: [{
                label: PROFILE_I18N.difficulty,
                data: answers.map(a =>
                    parseFloat(a.difficulty_level || 1)
                ),
                tension: 0.3
            }]
        },

        options: {
            responsive: true,

            scales: {
                y: {
                    min: 1,
                    max: 5
                }
            }
        }
    });
}

function renderCategoryRadarChart(categories) {

    const ctx =
        document.getElementById("categoryRadarChart");

    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "radar",

        data: {
            labels: categories.map(c => c.category),

            datasets: [{
                label: PROFILE_I18N.precisionPercent,
                data: categories.map(c =>
                    parseFloat(c.precision || 0)
                )
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

function renderMistakesChart(mistakes) {

    const ctx =
        document.getElementById("mistakesChart");

    if (!ctx || mistakes.length === 0) return;

    new Chart(ctx, {
        type: "doughnut",

        data: {
            labels: mistakes.map(m =>
                m.category || "Unknown"
            ),

            datasets: [{
                data: mistakes.map(m =>
                    parseInt(m.total_errors || 0)
                )
            }]
        },

        options: {
            responsive: true
        }
    });
}

function renderResponseTimeChart(categories) {

    const ctx =
        document.getElementById("responseTimeChart");

    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "bar",

        data: {
            labels: categories.map(c => c.category),

            datasets: [{
                label: PROFILE_I18N.avgResponseTimeShort,
                data: categories.map(c =>
                    parseFloat(c.avg_time || 0)
                )
            }]
        },

        options: {
            responsive: true
        }
    });
}

async function loadInsights() {

    try {

        const response = await fetch(
            "/colesterol_game/backend/exports/player_insights_report.php"
        );

        const data = await response.json();

        if (!data.success) {
            return;
        }

        renderInsights(data.insights || []);

    } catch (error) {

        console.error("Insights error:", error);
    }
}

function renderInsights(insights) {

    const container =
        document.getElementById("insights-container");

    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(insights) || insights.length === 0) {

        container.innerHTML = `
            <p>${PROFILE_I18N.noInsights}</p>
        `;

        return;
    }

    insights.forEach(insight => {

        const card = document.createElement("div");

        card.classList.add("insight-card");

        card.innerHTML = `
            <div class="insight-header">
                ${getInsightIcon(insight.type)}
                <h3>${insight.title}</h3>
            </div>

            <p>${insight.message}</p>
        `;

        container.appendChild(card);
    });
}

function getInsightIcon(type) {

    switch(type) {

        case "weak_category":
            return "⚠️";

        case "strong_category":
            return "🏆";

        case "fast_player":
            return "⚡";

        case "slow_player":
            return "🐢";

        case "advanced_player":
            return "📈";

        case "difficulty_master":
            return "🔥";

        case "excellent_precision":
            return "🎯";

        case "needs_practice":
            return "📚";

        default:
            return "🧠";
    }
}

async function loadBadges() {

    try {

        const response = await fetch(
            "/colesterol_game/backend/badges/get_user_badges.php"
        );

        const data = await response.json();

        if (!data.success) {
            return;
        }

        renderBadges(data.badges || []);

    } catch (error) {

        console.error("Badges error:", error);
    }
}

function renderBadges(badges) {

    const container =
        document.getElementById("badges-container");

    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(badges) || badges.length === 0) {

        container.innerHTML = `
            <p>${PROFILE_I18N.noBadges}</p>
        `;

        return;
    }

    badges.forEach(badge => {

        const card = document.createElement("div");

        card.classList.add("badge-card");

        card.innerHTML = `
            <div class="badge-icon">
                ${extractBadgeEmoji(badge.badge_name)}
            </div>

            <div class="badge-content">
                <h3>${badge.badge_name}</h3>

                <p>${badge.badge_description}</p>

                <small>
                    ${formatBadgeDate(badge.earned_at)}
                </small>
            </div>
        `;

        container.appendChild(card);
    });
}

function extractBadgeEmoji(text) {

    const match =
        text.match(
            /([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|\uD83E[\uDD00-\uDFFF])/
        );

    return match ? match[0] : "🏅";
}

function formatBadgeDate(date) {

    return new Date(date)
        .toLocaleDateString();
}
</script>

</body>
</html>
