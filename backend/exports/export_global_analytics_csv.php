<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';

if (!has_role(["teacher", "super_admin"])) {
    die("No autorizado");
}

$filename = "global_analytics_report_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ["GLOBAL ANALYTICS REPORT"]);
fputcsv($output, ["Generated at", date("Y-m-d H:i:s")]);
fputcsv($output, []);

fputcsv($output, ["SUMMARY"]);

$summarySql = "
    SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM game_results) AS total_games,
        (SELECT COUNT(*) FROM game_rooms) AS total_rooms,
        (SELECT COUNT(*) FROM questions) AS total_questions,
        (SELECT COUNT(*) FROM game_answers) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty
    FROM game_answers
";

$result = $conn->query($summarySql);
$summary = $result->fetch_assoc();

$totalAnswers = (int)$summary["total_answers"];
$correctAnswers = (int)$summary["correct_answers"];
$precision = $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0;

fputcsv($output, ["Total users", (int)$summary["total_users"]]);
fputcsv($output, ["Total games", (int)$summary["total_games"]]);
fputcsv($output, ["Total rooms", (int)$summary["total_rooms"]]);
fputcsv($output, ["Total questions", (int)$summary["total_questions"]]);
fputcsv($output, ["Total answers", $totalAnswers]);
fputcsv($output, ["Correct answers", $correctAnswers]);
fputcsv($output, ["Global precision", $precision . "%"]);
fputcsv($output, ["Average response time", round((float)$summary["avg_response_time"], 2) . "s"]);
fputcsv($output, ["Average difficulty", round((float)$summary["avg_difficulty"], 1) . " / 5"]);

fputcsv($output, []);
fputcsv($output, ["TOP PLAYERS"]);
fputcsv($output, [
    "Player",
    "Total answers",
    "Correct answers",
    "Precision",
    "Total score",
    "Average response time",
    "Average difficulty",
    "Max difficulty"
]);

$playersSql = "
    SELECT
        COALESCE(u.name, ga.player_name, 'Guest') AS player_name,
        COUNT(*) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(SUM(ga.score_earned), 0) AS total_score,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty,
        COALESCE(MAX(ga.difficulty_level), 0) AS max_difficulty
    FROM game_answers ga
    LEFT JOIN users u ON ga.user_id = u.id
    GROUP BY COALESCE(u.name, ga.player_name, 'Guest')
    ORDER BY total_score DESC, correct_answers DESC
    LIMIT 20
";

$result = $conn->query($playersSql);

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $rowPrecision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    fputcsv($output, [
        $row["player_name"],
        $total,
        $correct,
        $rowPrecision . "%",
        (int)$row["total_score"],
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5",
        round((float)$row["max_difficulty"], 1) . " / 5"
    ]);
}

fputcsv($output, []);
fputcsv($output, ["TOP ROOMS"]);
fputcsv($output, [
    "Room code",
    "Room name",
    "Status",
    "Total players",
    "Total answers",
    "Correct answers",
    "Precision",
    "Average response time",
    "Average difficulty"
]);

$roomsSql = "
    SELECT
        gr.room_code,
        gr.name,
        gr.status,
        COUNT(DISTINCT ga.player_name) AS total_players,
        COUNT(ga.id) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty
    FROM game_rooms gr
    LEFT JOIN game_answers ga 
        ON ga.room_id = gr.id
        AND ga.game_mode = 'room'
    GROUP BY gr.id, gr.room_code, gr.name, gr.status
    ORDER BY total_players DESC, total_answers DESC
";

$result = $conn->query($roomsSql);

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $rowPrecision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    fputcsv($output, [
        $row["room_code"],
        $row["name"],
        room_status_label($row["status"]),
        (int)$row["total_players"],
        $total,
        $correct,
        $rowPrecision . "%",
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5"
    ]);
}

fputcsv($output, []);
fputcsv($output, ["PERFORMANCE BY CATEGORY"]);
fputcsv($output, [
    "Category",
    "Total answers",
    "Correct answers",
    "Precision",
    "Average response time",
    "Average difficulty"
]);

$categorySql = "
    SELECT
        q.category,
        COUNT(*) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    GROUP BY q.category
    ORDER BY q.category ASC
";

$result = $conn->query($categorySql);

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $rowPrecision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    fputcsv($output, [
        $row["category"],
        $total,
        $correct,
        $rowPrecision . "%",
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5"
    ]);
}

fputcsv($output, []);
fputcsv($output, ["MOST FAILED QUESTIONS"]);
fputcsv($output, [
    "Question ID",
    "Question",
    "Category",
    "Total answers",
    "Correct answers",
    "Incorrect answers",
    "Failure rate",
    "Average response time",
    "Average difficulty"
]);

$failedSql = "
    SELECT
        q.id,
        q.question,
        q.category,
        COUNT(*) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    GROUP BY q.id, q.question, q.category
    ORDER BY (1 - (COALESCE(SUM(ga.is_correct), 0) / COUNT(*))) DESC, COUNT(*) DESC
    LIMIT 20
";

$result = $conn->query($failedSql);

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $incorrect = $total - $correct;
    $failureRate = $total > 0 ? round(($incorrect / $total) * 100, 2) : 0;

    fputcsv($output, [
        (int)$row["id"],
        $row["question"],
        $row["category"],
        $total,
        $correct,
        $incorrect,
        $failureRate . "%",
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5"
    ]);
}

$conn->close();

fclose($output);
exit;
?>
