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
    <title><?php echo t("question_analytics"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
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
            <a href="/colesterol_game/pages/admin_reports.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_reports_center"); ?>
            </a>
        </div>
    </div>

    <h1>🧠 <?php echo t("question_analytics"); ?></h1>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <h3><?php echo t("total_questions"); ?></h3>
            <p id="total-questions">...</p>
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
    </section>

    <section class="admin-section">
        <h2><?php echo t("most_failed_questions"); ?></h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("incorrect_answers"); ?></th>
                    <th><?php echo t("failure_rate"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                </tr>
            </thead>
            <tbody id="failed-body">
                <tr><td colspan="6"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("hardest_questions"); ?></h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                </tr>
            </thead>
            <tbody id="hardest-body">
                <tr><td colspan="5"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("slowest_questions"); ?></h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                </tr>
            </thead>
            <tbody id="slowest-body">
                <tr><td colspan="5"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("best_precision_questions"); ?></h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("total_answered_questions"); ?></th>
                </tr>
            </thead>
            <tbody id="best-body">
                <tr><td colspan="5"><?php echo t("loading"); ?></td></tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2>📚 <?php echo t("all_questions"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("question"); ?></th>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("difficulty"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("total_answered_questions"); ?></th>
                </tr>
            </thead>

            <tbody id="all-questions-body">
                <tr>
                    <td colspan="7">
                        <?php echo t("loading"); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

</div>

<script>
const QA_I18N = {
    noData: "<?php echo t('no_data_available'); ?>"
};

fetch("/colesterol_game/backend/reports/question_analytics_report.php")
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;

        document.getElementById("total-questions").textContent = data.summary.total_questions;
        document.getElementById("total-answers").textContent = data.summary.total_answers;
        document.getElementById("precision").textContent = data.summary.precision + "%";
        document.getElementById("avg-response-time").textContent = data.summary.avg_response_time + "s";
        document.getElementById("avg-difficulty").textContent = data.summary.avg_difficulty + " / 5";

        renderFailed(data.most_failed_questions);
        renderHardest(data.hardest_questions);
        renderSlowest(data.slowest_questions);
        renderBest(data.best_precision_questions);
        renderAllQuestions(data.all_questions);
    })
    .catch(console.error);

function emptyRow(colspan) {
    return `<tr><td colspan="${colspan}">${QA_I18N.noData}</td></tr>`;
}

function renderFailed(items) {
    const tbody = document.getElementById("failed-body");
    tbody.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
        tbody.innerHTML = emptyRow(6);
        return;
    }

    items.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td>${item.question_id}</td>
                <td>${item.question}</td>
                <td>${item.category}</td>
                <td>${item.incorrect_answers} / ${item.total_answers}</td>
                <td>${item.failure_rate}%</td>
                <td>${item.avg_response_time}s</td>
            </tr>
        `;
    });
}

function renderHardest(items) {
    const tbody = document.getElementById("hardest-body");
    tbody.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
        tbody.innerHTML = emptyRow(5);
        return;
    }

    items.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td>${item.question_id}</td>
                <td>${item.question}</td>
                <td>${item.category}</td>
                <td>${item.avg_adaptive_difficulty} / 5</td>
                <td>${item.precision}%</td>
            </tr>
        `;
    });
}

function renderSlowest(items) {
    const tbody = document.getElementById("slowest-body");
    tbody.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
        tbody.innerHTML = emptyRow(5);
        return;
    }

    items.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td>${item.question_id}</td>
                <td>${item.question}</td>
                <td>${item.category}</td>
                <td>${item.avg_response_time}s</td>
                <td>${item.precision}%</td>
            </tr>
        `;
    });
}

function renderBest(items) {
    const tbody = document.getElementById("best-body");
    tbody.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {
        tbody.innerHTML = emptyRow(5);
        return;
    }

    items.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td>${item.question_id}</td>
                <td>${item.question}</td>
                <td>${item.category}</td>
                <td>${item.precision}%</td>
                <td>${item.total_answers}</td>
            </tr>
        `;
    });
}

function renderAllQuestions(items) {

    const tbody =
        document.getElementById("all-questions-body");

    tbody.innerHTML = "";

    if (!Array.isArray(items) || items.length === 0) {

        tbody.innerHTML = emptyRow(7);
        return;
    }

    items.forEach(item => {

        tbody.innerHTML += `
            <tr>
                <td>${item.question_id}</td>

                <td>${item.question}</td>

                <td>${item.category}</td>

                <td>
                    ${item.base_difficulty} / 5
                </td>

                <td>
                    ${item.precision}%
                </td>

                <td>
                    ${item.avg_response_time}s
                </td>

                <td>
                    ${item.total_answers}
                </td>
            </tr>
        `;
    });
}

</script>

</body>
</html>
