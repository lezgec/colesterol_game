<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../assets/includes/auth.php';
require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("admin_dashboard"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container admin-container">

    <div class="top-actions">
        <div class="language-switch">
            <a href="?lang=es">ES</a> |
            <a href="?lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/logout.php" class="logout-btn">
            <?php echo t("logout"); ?>
        </a>
    </div>

    <h1><?php echo t("admin_dashboard"); ?></h1>
    <p><?php echo t("admin_dashboard_description"); ?></p>

    <div class="admin-dashboard-layout">

        <section class="admin-section dashboard-summary-section">
            <h2><?php echo t("system_summary"); ?></h2>

            <div id="admin-summary" class="dashboard-grid">
                <div class="dashboard-card">
                    <h3><?php echo t("total_users"); ?></h3>
                    <p id="total-users">...</p>
                </div>

                <div class="dashboard-card">
                    <h3><?php echo t("total_questions"); ?></h3>
                    <p id="total-questions">...</p>
                </div>

                <div class="dashboard-card">
                    <h3><?php echo t("total_games"); ?></h3>
                    <p id="total-games">...</p>
                </div>

                <div class="dashboard-card">
                    <h3><?php echo t("total_rooms"); ?></h3>
                    <p id="total-rooms">...</p>
                </div>

                <div class="dashboard-card">
                    <h3><?php echo t("active_rooms"); ?></h3>
                    <p id="active-rooms">...</p>
                </div>

                <div class="dashboard-card">
                    <h3><?php echo t("average_score"); ?></h3>
                    <p id="avg-score">...</p>
                </div>
            </div>
        </section>

        <aside class="admin-tools-panel">
            <h2><?php echo t("admin_tools"); ?></h2>

            <div class="admin-tools-list">

                <a href="/colesterol_game/pages/admin_questions.php"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo t("admin_questions"); ?></h3>
                    <p><?php echo t("admin_questions_description"); ?></p>
                </a>

                <a href="/colesterol_game/pages/rooms/create.php"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo t("create_room"); ?></h3>
                    <p><?php echo t("create_room_description"); ?></p>
                </a>

                <a href="/colesterol_game/pages/ranking.php"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo t("ranking"); ?></h3>
                    <p><?php echo t("ranking_description"); ?></p>
                </a>

                <a href="/colesterol_game/pages/dashboard.php"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo t("dashboard"); ?></h3>
                    <p><?php echo t("dashboard_description"); ?></p>
                </a>

                <a href="/colesterol_game/index.php"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo t("public_view"); ?></h3>
                    <p><?php echo t("public_view_description"); ?></p>
                </a>

            </div>
        </aside>

    </div>

    <section class="admin-section">
        <h2><?php echo t("educational_analytics"); ?></h2>

        <div class="dashboard-grid">

            <div class="dashboard-card">
                <h3><?php echo t("overall_accuracy"); ?></h3>
                <p id="overall-accuracy">...</p>
            </div>

            <div class="dashboard-card">
                <h3><?php echo t("total_correct_answers"); ?></h3>
                <p id="total-correct">...</p>
            </div>

            <div class="dashboard-card">
                <h3><?php echo t("total_answered_questions"); ?></h3>
                <p id="total-answered">...</p>
            </div>

        </div>

        <div class="analytics-layout">

            <div class="admin-section">
                <h3><?php echo t("performance_by_difficulty"); ?></h3>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?php echo t("difficulty"); ?></th>
                            <th>%</th>
                        </tr>
                    </thead>

                    <tbody id="difficulty-analytics-body">
                        <tr>
                            <td colspan="2"><?php echo t("loading"); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h3><?php echo t("top_players"); ?></h3>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo t("player_name"); ?></th>
                            <th><?php echo t("score"); ?></th>
                        </tr>
                    </thead>

                    <tbody id="top-players-body">
                        <tr>
                            <td colspan="3"><?php echo t("loading"); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </section>

</div>

<script>
fetch("/colesterol_game/backend/dashboard/get_admin_dashboard.php")
    .then(res => res.json())
    .then(result => {
        if (!result.success) return;

        const data = result.data;

        document.getElementById("total-users").textContent = data.total_users;
        document.getElementById("total-questions").textContent = data.total_questions;
        document.getElementById("total-games").textContent = data.total_games;
        document.getElementById("total-rooms").textContent = data.total_rooms;
        document.getElementById("active-rooms").textContent = data.active_rooms;
        document.getElementById("avg-score").textContent = data.avg_score;
    })
    .catch(error => {
        console.error(error);
    });

fetch("/colesterol_game/backend/dashboard/get_admin_analytics.php")
    .then(res => res.json())
    .then(result => {
        if (!result.success) return;

        const data = result.data;

        document.getElementById("overall-accuracy").textContent =
            `${data.accuracy.percentage}%`;

        document.getElementById("total-correct").textContent =
            data.accuracy.total_correct;

        document.getElementById("total-answered").textContent =
            data.accuracy.total_answered;

        const difficultyBody = document.getElementById("difficulty-analytics-body");
        difficultyBody.innerHTML = "";

        if (!Array.isArray(data.difficulty_stats) || data.difficulty_stats.length === 0) {
            difficultyBody.innerHTML = `<tr><td colspan="2">-</td></tr>`;
        } else {
            data.difficulty_stats.forEach(item => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${item.difficulty}</td>
                    <td>${item.percentage}%</td>
                `;

                difficultyBody.appendChild(row);
            });
        }

        const playersBody = document.getElementById("top-players-body");
        playersBody.innerHTML = "";

        if (!Array.isArray(data.top_players) || data.top_players.length === 0) {
            playersBody.innerHTML = `<tr><td colspan="3">-</td></tr>`;
        } else {
            data.top_players.forEach((player, index) => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${player.player_name ?? "-"}</td>
                    <td>${player.best_score}</td>
                `;

                playersBody.appendChild(row);
            });
        }
    })
    .catch(error => {
        console.error(error);
    });
</script>

</body>
</html>