<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';

require_login();

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

$resultId = (int)($_GET["result_id"] ?? 0);
$currentUserId = current_user_id();

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function option_label_text($answer, $letter) {
    $letter = strtoupper((string)$letter);
    $map = [
        "A" => "option_a",
        "B" => "option_b",
        "C" => "option_c",
        "D" => "option_d"
    ];

    if (!isset($map[$letter])) {
        return "-";
    }

    $text = $answer[$map[$letter]] ?? "";
    return trim($letter . ". " . $text);
}

$result = null;
$roomOwnerId = null;
$answers = [];
$categoryStats = [];
$difficultyStats = [];
$answerOutcomeStats = [
    "correct" => 0,
    "incorrect" => 0
];
$responseTimeStats = [];
$feedbackTitle = "";
$feedbackMessage = "";
$feedbackTips = [];

if ($resultId > 0) {
    $stmt = $conn->prepare("
        SELECT
            gr.*,
            r.name AS room_name,
            r.room_code,
            r.created_by AS room_created_by
        FROM game_results gr
        LEFT JOIN game_rooms r ON r.id = gr.room_id
        WHERE gr.id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $resultData = $stmt->get_result();
        $result = $resultData->fetch_assoc() ?: null;
        $stmt->close();
    }
}

if ($result) {
    $roomOwnerId = $result["room_created_by"] !== null
        ? (int)$result["room_created_by"]
        : null;

    $canView = ((int)($result["user_id"] ?? 0) === $currentUserId)
        || is_super_admin()
        || ($roomOwnerId !== null && $roomOwnerId === $currentUserId);

    if (!$canView) {
        http_response_code(403);
        $result = null;
    }
}

if ($result) {
    $limit = max(1, (int)$result["total_questions"]);
    $roomId = $result["room_id"] !== null ? (int)$result["room_id"] : null;

    if ($roomId !== null) {
        $playerName = (string)($result["player_name"] ?? "");
        $stmt = $conn->prepare("
            SELECT
                ga.*,
                q.question,
                q.option_a,
                q.option_b,
                q.option_c,
                q.option_d,
                q.category
            FROM game_answers ga
            INNER JOIN questions q ON q.id = ga.question_id
            WHERE ga.room_id = ?
              AND ga.player_name = ?
              AND ga.answered_at <= ?
            ORDER BY ga.answered_at DESC, ga.id DESC
            LIMIT ?
        ");

        if ($stmt) {
            $stmt->bind_param("issi", $roomId, $playerName, $result["played_at"], $limit);
            $stmt->execute();
            $answerResult = $stmt->get_result();
            while ($row = $answerResult->fetch_assoc()) {
                $answers[] = $row;
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("
            SELECT
                ga.*,
                q.question,
                q.option_a,
                q.option_b,
                q.option_c,
                q.option_d,
                q.category
            FROM game_answers ga
            INNER JOIN questions q ON q.id = ga.question_id
            WHERE ga.user_id = ?
              AND ga.room_id IS NULL
              AND ga.answered_at <= ?
            ORDER BY ga.answered_at DESC, ga.id DESC
            LIMIT ?
        ");

        if ($stmt) {
            $stmt->bind_param("isi", $currentUserId, $result["played_at"], $limit);
            $stmt->execute();
            $answerResult = $stmt->get_result();
            while ($row = $answerResult->fetch_assoc()) {
                $answers[] = $row;
            }
            $stmt->close();
        }
    }

    $answers = array_reverse($answers);

    foreach ($answers as $answer) {
        $category = trim((string)($answer["category"] ?? t("category")));
        if ($category === "") {
            $category = t("category");
        }

        $difficulty = (string)round((float)$answer["difficulty_level"], 1);
        $isCorrect = (int)$answer["is_correct"] === 1;
        $responseTime = (float)$answer["response_time"];

        if (!isset($categoryStats[$category])) {
            $categoryStats[$category] = [
                "category" => $category,
                "correct" => 0,
                "total" => 0,
                "avg_time_sum" => 0
            ];
        }

        $categoryStats[$category]["total"]++;
        $categoryStats[$category]["correct"] += $isCorrect ? 1 : 0;
        $categoryStats[$category]["avg_time_sum"] += $responseTime;

        if (!isset($difficultyStats[$difficulty])) {
            $difficultyStats[$difficulty] = [
                "difficulty" => $difficulty,
                "correct" => 0,
                "total" => 0
            ];
        }

        $difficultyStats[$difficulty]["total"]++;
        $difficultyStats[$difficulty]["correct"] += $isCorrect ? 1 : 0;

        $answerOutcomeStats[$isCorrect ? "correct" : "incorrect"]++;

        $responseTimeStats[] = [
            "question" => count($responseTimeStats) + 1,
            "time" => $responseTime
        ];
    }

    foreach ($categoryStats as &$categoryStat) {
        $categoryStat["precision"] = $categoryStat["total"] > 0
            ? round(($categoryStat["correct"] / $categoryStat["total"]) * 100, 2)
            : 0;
        $categoryStat["avg_time"] = $categoryStat["total"] > 0
            ? round($categoryStat["avg_time_sum"] / $categoryStat["total"], 2)
            : 0;
        unset($categoryStat["avg_time_sum"]);
    }
    unset($categoryStat);

    foreach ($difficultyStats as &$difficultyStat) {
        $difficultyStat["precision"] = $difficultyStat["total"] > 0
            ? round(($difficultyStat["correct"] / $difficultyStat["total"]) * 100, 2)
            : 0;
    }
    unset($difficultyStat);

    $categoryStats = array_values($categoryStats);
    $difficultyStats = array_values($difficultyStats);
    usort($difficultyStats, function ($a, $b) {
        return (float)$a["difficulty"] <=> (float)$b["difficulty"];
    });
}

$correct = $result ? (int)$result["correct_answers"] : 0;
$total = $result ? (int)$result["total_questions"] : 0;
$precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
$mode = $result && $result["room_id"] !== null
    ? t("room") . " " . h($result["room_code"] ?? ("#" . $result["room_id"]))
    : t("solo");

if ($result) {
    if ($precision >= 80) {
        $feedbackTitle = t("game_feedback_excellent_title");
        $feedbackMessage = t("game_feedback_excellent_message");
    } elseif ($precision >= 60) {
        $feedbackTitle = t("game_feedback_good_title");
        $feedbackMessage = t("game_feedback_good_message");
    } else {
        $feedbackTitle = t("game_feedback_practice_title");
        $feedbackMessage = t("game_feedback_practice_message");
    }

    if ($categoryStats) {
        $weakestCategory = $categoryStats[0];
        foreach ($categoryStats as $categoryStat) {
            if ($categoryStat["precision"] < $weakestCategory["precision"]) {
                $weakestCategory = $categoryStat;
            }
        }

        $feedbackTips[] = sprintf(
            t("game_feedback_category_tip"),
            $weakestCategory["category"],
            $weakestCategory["precision"]
        );
    }

    if ($responseTimeStats) {
        $timeTotal = array_sum(array_column($responseTimeStats, "time"));
        $avgTime = round($timeTotal / count($responseTimeStats), 2);
        $feedbackTips[] = sprintf(t("game_feedback_time_tip"), $avgTime);
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("game_stats"); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">
</head>
<body>
<div class="game-container game-result-stats-page">
    <div class="top-actions page-centered-top-actions">
        <div class="top-links">
            <a href="<?php echo app_path('pages/history.php'); ?>" class="logout-btn secondary-btn">
                <?php echo t("back_to_history"); ?>
            </a>
            <?php if ($result): ?>
                <a href="<?php echo app_path('backend/exports/pdf/export_game_result_pdf.php?result_id=' . (int)$resultId); ?>" class="logout-btn secondary-btn">
                    <?php echo ui_icon("download"); ?> <?php echo t("export_pdf"); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <header class="page-title-block">
        <h1><?php echo ui_icon("analytics"); ?> <?php echo t("game_stats"); ?></h1>
        <p><?php echo t("game_stats_description"); ?></p>
    </header>

    <?php if (!$result): ?>
        <section class="admin-section empty-state-panel">
            <h2><?php echo t("result_not_found"); ?></h2>
            <p><?php echo t("result_not_available_action"); ?></p>
        </section>
    <?php else: ?>
        <section class="dashboard-grid game-result-summary">
            <article class="dashboard-card">
                <h3><?php echo t("final_score"); ?></h3>
                <p><?php echo h((int)$result["score"]); ?></p>
            </article>
            <article class="dashboard-card">
                <h3><?php echo t("correct_answers"); ?></h3>
                <p><?php echo h($correct); ?> / <?php echo h($total); ?></p>
            </article>
            <article class="dashboard-card">
                <h3><?php echo t("precision"); ?></h3>
                <p><?php echo h($precision); ?>%</p>
            </article>
            <article class="dashboard-card">
                <h3><?php echo t("remaining_lives"); ?></h3>
                <p><?php echo h((int)$result["lives_remaining"]); ?></p>
            </article>
            <article class="dashboard-card">
                <h3><?php echo t("final_difficulty"); ?></h3>
                <p><?php echo h(round((float)$result["final_difficulty"], 1)); ?> / 5</p>
            </article>
            <article class="dashboard-card">
                <h3><?php echo t("mode"); ?></h3>
                <p><?php echo $mode; ?></p>
            </article>
        </section>

        <section class="admin-section game-result-feedback-section">
            <div class="section-heading-row">
                <div>
                    <h2><?php echo ui_icon("brain"); ?> <?php echo t("game_feedback"); ?></h2>
                    <p><?php echo t("game_feedback_description"); ?></p>
                </div>
            </div>

            <div class="game-result-feedback-card">
                <div>
                    <h3><?php echo h($feedbackTitle); ?></h3>
                    <p><?php echo h($feedbackMessage); ?></p>
                </div>
                <?php if ($feedbackTips): ?>
                    <ul>
                        <?php foreach ($feedbackTips as $tip): ?>
                            <li><?php echo h($tip); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="game-result-charts-grid">
            <div class="chart-card">
                <h3><?php echo t("category_performance"); ?></h3>
                <canvas id="resultCategoryChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><?php echo t("answer_distribution"); ?></h3>
                <canvas id="resultOutcomeChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><?php echo t("difficulty_performance"); ?></h3>
                <canvas id="resultDifficultyChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><?php echo t("response_time_progress"); ?></h3>
                <canvas id="resultTimeChart"></canvas>
            </div>
        </section>

        <section class="admin-section">
            <div class="section-heading-row">
                <div>
                    <h2><?php echo t("answer_detail"); ?></h2>
                    <p><?php echo t("answer_detail_description"); ?></p>
                </div>
                <span class="status-chip"><?php echo h($result["played_at"]); ?></span>
            </div>

            <table class="admin-table game-result-answer-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo t("question"); ?></th>
                        <th><?php echo t("category"); ?></th>
                        <th><?php echo t("selected_answer"); ?></th>
                        <th><?php echo t("correct_answer"); ?></th>
                        <th><?php echo t("time"); ?></th>
                        <th><?php echo t("difficulty"); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$answers): ?>
                        <tr>
                            <td colspan="7"><?php echo t("no_answer_detail"); ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($answers as $index => $answer): ?>
                        <?php
                        $selected = option_label_text($answer, $answer["selected_option"] ?? "");
                        $correctText = option_label_text($answer, $answer["correct_option"] ?? "");
                        $rowClass = ((int)$answer["is_correct"] === 1) ? "is-correct" : "is-incorrect";
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo h($index + 1); ?></td>
                            <td><?php echo h($answer["question"]); ?></td>
                            <td><?php echo h($answer["category"]); ?></td>
                            <td><?php echo h($selected); ?></td>
                            <td><?php echo h($correctText); ?></td>
                            <td><?php echo h((int)$answer["response_time"]); ?>s</td>
                            <td><?php echo h(round((float)$answer["difficulty_level"], 1)); ?> / 5</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</div>

<?php if ($result): ?>
<script>
const RESULT_CHART_DATA = {
    categories: <?php echo json_encode($categoryStats, JSON_UNESCAPED_UNICODE); ?>,
    difficulties: <?php echo json_encode($difficultyStats, JSON_UNESCAPED_UNICODE); ?>,
    outcomes: <?php echo json_encode($answerOutcomeStats, JSON_UNESCAPED_UNICODE); ?>,
    responseTimes: <?php echo json_encode($responseTimeStats, JSON_UNESCAPED_UNICODE); ?>
};

const RESULT_CHART_I18N = {
    correct: "<?php echo t('correct'); ?>",
    incorrect: "<?php echo t('incorrect'); ?>",
    precision: "<?php echo t('precision'); ?>",
    questions: "<?php echo t('questions'); ?>",
    seconds: "<?php echo t('seconds'); ?>",
    difficulty: "<?php echo t('difficulty'); ?>"
};

function renderResultCharts() {
    if (typeof Chart === "undefined") {
        return;
    }

    const textColor = getComputedStyle(document.documentElement)
        .getPropertyValue("--theme-section-text")
        .trim() || "#111827";
    const gridColor = "rgba(148, 163, 184, 0.24)";

    const categoryCtx = document.getElementById("resultCategoryChart");
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: "bar",
            data: {
                labels: RESULT_CHART_DATA.categories.map(item => item.category),
                datasets: [{
                    label: RESULT_CHART_I18N.precision,
                    data: RESULT_CHART_DATA.categories.map(item => item.precision),
                    backgroundColor: "rgba(91, 91, 232, 0.72)",
                    borderColor: "#5b5be8",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { labels: { color: textColor } }
                }
            }
        });
    }

    const outcomeCtx = document.getElementById("resultOutcomeChart");
    if (outcomeCtx) {
        new Chart(outcomeCtx, {
            type: "doughnut",
            data: {
                labels: [RESULT_CHART_I18N.correct, RESULT_CHART_I18N.incorrect],
                datasets: [{
                    data: [
                        RESULT_CHART_DATA.outcomes.correct || 0,
                        RESULT_CHART_DATA.outcomes.incorrect || 0
                    ],
                    backgroundColor: ["#22c55e", "#ef4444"],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: textColor } }
                }
            }
        });
    }

    const difficultyCtx = document.getElementById("resultDifficultyChart");
    if (difficultyCtx) {
        new Chart(difficultyCtx, {
            type: "line",
            data: {
                labels: RESULT_CHART_DATA.difficulties.map(item => `${RESULT_CHART_I18N.difficulty} ${item.difficulty}`),
                datasets: [{
                    label: RESULT_CHART_I18N.precision,
                    data: RESULT_CHART_DATA.difficulties.map(item => item.precision),
                    borderColor: "#14b8a6",
                    backgroundColor: "rgba(20, 184, 166, 0.18)",
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { labels: { color: textColor } }
                }
            }
        });
    }

    const timeCtx = document.getElementById("resultTimeChart");
    if (timeCtx) {
        new Chart(timeCtx, {
            type: "line",
            data: {
                labels: RESULT_CHART_DATA.responseTimes.map(item => `#${item.question}`),
                datasets: [{
                    label: RESULT_CHART_I18N.seconds,
                    data: RESULT_CHART_DATA.responseTimes.map(item => item.time),
                    borderColor: "#f59e0b",
                    backgroundColor: "rgba(245, 158, 11, 0.18)",
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { labels: { color: textColor } }
                }
            }
        });
    }
}

renderResultCharts();
</script>
<?php endif; ?>

<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
