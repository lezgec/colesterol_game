<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/export_helpers.php';

if (!has_role(["teacher", "super_admin"])) {
    die("No autorizado");
}

$filename = "global_analytics_report_" . date("Y-m-d_H-i-s") . ".csv";

$output = export_csv_open($filename);

export_csv_title($output, export_label("analytics_report"));
fputcsv($output, []);

export_csv_section($output, export_label("summary"));

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

fputcsv($output, [export_label("total_users"), (int)$summary["total_users"]]);
fputcsv($output, [export_label("total_games"), (int)$summary["total_games"]]);
fputcsv($output, [export_label("total_rooms"), (int)$summary["total_rooms"]]);
fputcsv($output, [export_label("total_questions"), (int)$summary["total_questions"]]);
fputcsv($output, [export_label("total_answers"), $totalAnswers]);
fputcsv($output, [export_label("correct_answers"), $correctAnswers]);
fputcsv($output, [export_label("global_precision"), $precision . "%"]);
fputcsv($output, [export_label("average_response_time"), round((float)$summary["avg_response_time"], 2) . "s"]);
fputcsv($output, [export_label("average_difficulty"), round((float)$summary["avg_difficulty"], 1) . " / 5"]);

export_csv_section($output, export_label("top_players"));
fputcsv($output, [
    export_label("player"),
    export_label("total_answers"),
    export_label("correct_answers"),
    export_label("precision"),
    export_label("score"),
    export_label("average_response_time"),
    export_label("average_difficulty"),
    export_label("max_difficulty")
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

export_csv_section($output, export_label("top_rooms"));
fputcsv($output, [
    export_label("room_code"),
    export_label("room_name"),
    export_label("status"),
    export_label("total_players"),
    export_label("total_answers"),
    export_label("correct_answers"),
    export_label("precision"),
    export_label("average_response_time"),
    export_label("average_difficulty")
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

export_csv_section($output, export_label("performance_by_category"));
fputcsv($output, [
    export_label("category"),
    export_label("total_answers"),
    export_label("correct_answers"),
    export_label("precision"),
    export_label("average_response_time"),
    export_label("average_difficulty")
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

export_csv_section($output, export_label("most_failed_questions"));
fputcsv($output, [
    export_label("question_id"),
    export_label("question"),
    export_label("category"),
    export_label("total_answers"),
    export_label("correct_answers"),
    export_label("incorrect_answers"),
    export_label("failure_rate"),
    export_label("average_response_time"),
    export_label("average_difficulty")
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
