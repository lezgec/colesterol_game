<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../users/profile_helpers.php';
require_once __DIR__ . '/streak_helpers.php';

ensure_user_profile_columns($conn);
ensure_user_streak_columns($conn);

$rankingLang = in_array(($_GET["lang"] ?? "es"), ["es", "en"], true)
    ? $_GET["lang"]
    : "es";

$sql = "
    SELECT
        g.user_id,
        u.name,
        u.country,
        u.city,
        u.best_correct_streak,
        u.current_daily_streak,
        MAX(g.score) AS best_score,
        COUNT(g.id) AS total_games,
        COALESCE(SUM(g.correct_answers), 0) AS total_correct,
        COALESCE(SUM(g.total_questions), 0) AS total_questions,
        COALESCE(AVG(g.final_difficulty), 1.0) AS avg_difficulty
    FROM game_results g
    INNER JOIN users u ON g.user_id = u.id
    WHERE g.user_id IS NOT NULL
    GROUP BY g.user_id, u.name, u.country, u.city, u.best_correct_streak, u.current_daily_streak
    ORDER BY best_score DESC, total_correct DESC, total_games DESC, u.name ASC
    LIMIT 10
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener el ranking",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $totalQuestions = (int)$row["total_questions"];
    $totalCorrect = (int)$row["total_correct"];
    $streaks = get_player_streak_summary($conn, (int)$row["user_id"]);

    $precision = 0;

    if ($totalQuestions > 0) {
        $precision = round(($totalCorrect / $totalQuestions) * 100, 2);
    }

    $data[] = [
        "name" => $row["name"],
        "country" => country_display($row["country"] ?? "", $rankingLang),
        "city" => $row["city"] ?? "",
        "best_score" => (int)$row["best_score"],
        "total_games" => (int)$row["total_games"],
        "best_correct_streak" => $streaks["best_correct_streak"],
        "current_daily_streak" => $streaks["current_daily_streak"],
        "precision" => $precision,
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
