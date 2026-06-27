<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/export_helpers.php';
require_once __DIR__ . '/../rooms/room_auth_helpers.php';

if (!has_role(["teacher", "super_admin"])) {
    die("No autorizado");
}

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    die("Código de sala vacío");
}

$room = require_room_owner_or_super_admin($conn, $roomCode);
$roomId = (int)$room["id"];

$filename = "room_report_" . $roomCode . "_" . date("Y-m-d_H-i-s") . ".csv";

$output = export_csv_open($filename);

export_csv_title($output, export_label("room_report"));
export_csv_write($output, [export_label("room"), $room["name"]]);
export_csv_write($output, [export_label("code"), $room["room_code"]]);
export_csv_write($output, [export_label("status"), room_status_label($room["status"])]);
export_csv_write($output, []);

export_csv_section($output, export_label("ranking"));
export_csv_write($output, [
    export_label("player"),
    export_label("total_answers"),
    export_label("correct_answers"),
    export_label("precision"),
    export_label("score"),
    export_label("average_response_time"),
    export_label("average_difficulty"),
    export_label("max_difficulty")
]);

$rankingSql = "
    SELECT
        player_name,
        COUNT(*) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(SUM(score_earned), 0) AS total_score,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty,
        COALESCE(MAX(difficulty_level), 0) AS max_difficulty
    FROM game_answers
    WHERE room_id = ?
      AND game_mode = 'room'
    GROUP BY player_name
    ORDER BY total_score DESC, correct_answers DESC
";

$stmt = $conn->prepare($rankingSql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    export_csv_write($output, [
        $row["player_name"],
        $total,
        $correct,
        $precision . "%",
        (int)$row["total_score"],
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5",
        round((float)$row["max_difficulty"], 1) . " / 5"
    ]);
}

$stmt->close();

export_csv_section($output, export_label("performance_by_category"));
export_csv_write($output, [
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
    WHERE ga.room_id = ?
      AND ga.game_mode = 'room'
    GROUP BY q.category
    ORDER BY q.category ASC
";

$stmt = $conn->prepare($categorySql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    export_csv_write($output, [
        $row["category"],
        $total,
        $correct,
        $precision . "%",
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5"
    ]);
}

$stmt->close();

export_csv_section($output, export_label("most_failed_questions"));
export_csv_write($output, [
    export_label("question"),
    export_label("category"),
    export_label("total_answers"),
    export_label("correct_answers"),
    export_label("incorrect_answers"),
    export_label("failure_rate"),
    export_label("average_response_time")
]);

$failedSql = "
    SELECT
        q.question,
        q.category,
        COUNT(*) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    WHERE ga.room_id = ?
      AND ga.game_mode = 'room'
    GROUP BY q.id, q.question, q.category
    ORDER BY (1 - (COALESCE(SUM(ga.is_correct), 0) / COUNT(*))) DESC
";

$stmt = $conn->prepare($failedSql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $incorrect = $total - $correct;
    $failureRate = $total > 0 ? round(($incorrect / $total) * 100, 2) : 0;

    export_csv_write($output, [
        $row["question"],
        $row["category"],
        $total,
        $correct,
        $incorrect,
        $failureRate . "%",
        round((float)$row["avg_response_time"], 2) . "s"
    ]);
}

$stmt->close();
$conn->close();

fclose($output);
exit;
?>
