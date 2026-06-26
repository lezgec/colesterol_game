<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../game/streak_helpers.php';

if (!is_logged_in()) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no autenticado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$userId = is_super_admin() && $requestedUserId > 0
    ? $requestedUserId
    : (int)$_SESSION["user_id"];

$summarySql = "
    SELECT
        COUNT(DISTINCT DATE(answered_at)) AS active_days,
        COUNT(DISTINCT CASE WHEN room_id IS NOT NULL THEN room_id END) AS rooms_played,
        COUNT(*) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty,
        COALESCE(MAX(difficulty_level), 0) AS max_difficulty,
        COALESCE(SUM(score_earned), 0) AS total_points
    FROM game_answers
    WHERE user_id = ?
";

$stmt = $conn->prepare($summarySql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalAnswers = (int)$summary["total_answers"];
$correctAnswers = (int)$summary["correct_answers"];

$precision = $totalAnswers > 0
    ? round(($correctAnswers / $totalAnswers) * 100, 2)
    : 0;

$streaks = get_player_streak_summary($conn, $userId);

$categorySql = "
    SELECT
        q.category,
        COUNT(*) AS total,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    WHERE ga.user_id = ?
    GROUP BY q.category
    ORDER BY (COALESCE(SUM(ga.is_correct), 0) / COUNT(*)) DESC, COUNT(*) DESC
";

$stmt = $conn->prepare($categorySql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$categoryResult = $stmt->get_result();

$categories = [];

while ($row = $categoryResult->fetch_assoc()) {
    $total = (int)$row["total"];
    $correct = (int)$row["correct_answers"];

    $categories[] = [
        "category" => $row["category"],
        "total" => $total,
        "correct" => $correct,
        "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
        "avg_time" => round((float)$row["avg_time"], 2),
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
    ];
}

$stmt->close();

$answersSql = "
    SELECT
        ga.answered_at,
        ga.question_id,
        q.category,
        ga.is_correct,
        ga.response_time,
        ga.difficulty_level,
        ga.score_earned
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    WHERE ga.user_id = ?
    ORDER BY ga.answered_at ASC, ga.id ASC
";

$stmt = $conn->prepare($answersSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$answersResult = $stmt->get_result();

$answers = [];

while ($row = $answersResult->fetch_assoc()) {
    $answers[] = [
        "answered_at" => $row["answered_at"],
        "question_id" => (int)$row["question_id"],
        "category" => $row["category"],
        "is_correct" => (int)$row["is_correct"],
        "response_time" => (int)$row["response_time"],
        "difficulty_level" => round((float)$row["difficulty_level"], 1),
        "score_earned" => (int)$row["score_earned"]
    ];
}

$stmt->close();

$mistakesSql = "
    SELECT
        ga.answered_at,
        q.question,
        q.category,
        q.explanation,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        ga.selected_option,
        ga.correct_option,
        ga.response_time,
        ga.difficulty_level
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    WHERE ga.user_id = ?
      AND ga.is_correct = 0
    ORDER BY ga.answered_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($mistakesSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$mistakesResult = $stmt->get_result();

$mistakes = [];

while ($row = $mistakesResult->fetch_assoc()) {
    $mistakes[] = [
        "answered_at" => $row["answered_at"],
        "question" => $row["question"],
        "category" => $row["category"],
        "explanation" => $row["explanation"],
        "options" => [
            "A" => $row["option_a"],
            "B" => $row["option_b"],
            "C" => $row["option_c"],
            "D" => $row["option_d"]
        ],
        "selected_option" => $row["selected_option"],
        "correct_option" => $row["correct_option"],
        "response_time" => (int)$row["response_time"],
        "difficulty_level" => round((float)$row["difficulty_level"], 1)
    ];
}

$stmt->close();

$mistakeDistributionSql = "
    SELECT
        q.category,
        COUNT(*) AS total_errors
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    WHERE ga.user_id = ?
      AND ga.is_correct = 0
    GROUP BY q.category
    ORDER BY total_errors DESC, q.category ASC
";

$stmt = $conn->prepare($mistakeDistributionSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$mistakeDistributionResult = $stmt->get_result();

$mistakeDistribution = [];

while ($row = $mistakeDistributionResult->fetch_assoc()) {
    $mistakeDistribution[] = [
        "category" => $row["category"],
        "total_errors" => (int)$row["total_errors"]
    ];
}

$stmt->close();

echo json_encode([
    "success" => true,
    "summary" => [
        "active_days" => (int)$summary["active_days"],
        "rooms_played" => (int)$summary["rooms_played"],
        "total_answers" => $totalAnswers,
        "correct_answers" => $correctAnswers,
        "precision" => $precision,
        "avg_response_time" => round((float)$summary["avg_response_time"], 2),
        "avg_difficulty" => round((float)$summary["avg_difficulty"], 1),
        "max_difficulty" => round((float)$summary["max_difficulty"], 1),
        "total_points" => (int)$summary["total_points"],
        "current_correct_streak" => $streaks["current_correct_streak"],
        "best_correct_streak" => $streaks["best_correct_streak"],
        "current_daily_streak" => $streaks["current_daily_streak"],
        "best_daily_streak" => $streaks["best_daily_streak"],
        "last_played_date" => $streaks["last_played_date"]
    ],
    "categories" => $categories,
    "answers" => $answers,
    "mistakes" => $mistakes,
    "mistake_distribution" => $mistakeDistribution
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
