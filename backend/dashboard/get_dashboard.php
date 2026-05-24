<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no autenticado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int) $_SESSION["user_id"];

$sqlSummary = "
    SELECT 
        COUNT(*) AS total_games,
        COALESCE(AVG(score), 0) AS avg_score,
        COALESCE(MAX(score), 0) AS best_score,
        COALESCE(SUM(correct_answers), 0) AS total_correct_answers,
        COALESCE(SUM(total_questions), 0) AS total_answered_questions,
        COALESCE(AVG(final_difficulty), 1.0) AS average_difficulty
    FROM game_results
    WHERE user_id = ?
";

$stmtSummary = $conn->prepare($sqlSummary);

if (!$stmtSummary) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta resumen",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtSummary->bind_param("i", $user_id);

if (!$stmtSummary->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Error al ejecutar la consulta resumen",
        "error" => $stmtSummary->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$summary = $stmtSummary->get_result()->fetch_assoc();

$accuracy = 0;

if ((int)$summary["total_answered_questions"] > 0) {
    $accuracy =
        ((int)$summary["total_correct_answers"] /
        (int)$summary["total_answered_questions"]) * 100;
}

$sqlRecentResults = "
    SELECT 
        score,
        correct_answers,
        total_questions,
        final_difficulty,
        played_at
    FROM game_results
    WHERE user_id = ?
    ORDER BY played_at DESC
    LIMIT 5
";

$stmtRecent = $conn->prepare($sqlRecentResults);

if (!$stmtRecent) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta de resultados recientes",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRecent->bind_param("i", $user_id);
$stmtRecent->execute();

$recentResult = $stmtRecent->get_result();

$recentGames = [];

while ($row = $recentResult->fetch_assoc()) {
    $recentGames[] = [
        "score" => (int)$row["score"],
        "correct_answers" => (int)$row["correct_answers"],
        "total_questions" => (int)$row["total_questions"],
        "final_difficulty" => round((float)($row["final_difficulty"] ?? 1.0), 1),
        "played_at" => $row["played_at"]
    ];
}

echo json_encode([
    "success" => true,
    "total_games" => (int)$summary["total_games"],
    "avg_score" => round((float)$summary["avg_score"], 2),
    "best_score" => (int)$summary["best_score"],
    "accuracy" => round($accuracy, 2),
    "average_difficulty" => round((float)$summary["average_difficulty"], 1),
    "recent_games" => $recentGames
], JSON_UNESCAPED_UNICODE);

$stmtSummary->close();
$stmtRecent->close();
$conn->close();
?>