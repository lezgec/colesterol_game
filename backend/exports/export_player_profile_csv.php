<?php
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/export_helpers.php';

if (!is_logged_in()) {
    die("No autorizado");
}

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$userId = is_super_admin() && $requestedUserId > 0
    ? $requestedUserId
    : (int)$_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "player";

$stmtUser = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$userRow = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if ($userRow) {
    $userName = $userRow["name"];
}

$filename = "player_profile_report_" . date("Y-m-d_H-i-s") . ".csv";

$output = export_csv_open($filename);

export_csv_title($output, export_label("player_report"));
export_csv_write($output, [export_label("player"), $userName]);
export_csv_write($output, []);

export_csv_section($output, export_label("summary"));

$summarySql = "
    SELECT
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
$precision = $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0;

export_csv_write($output, [export_label("total_answers"), $totalAnswers]);
export_csv_write($output, [export_label("correct_answers"), $correctAnswers]);
export_csv_write($output, [export_label("precision"), $precision . "%"]);
export_csv_write($output, [export_label("average_response_time"), round((float)$summary["avg_response_time"], 2) . "s"]);
export_csv_write($output, [export_label("average_difficulty"), round((float)$summary["avg_difficulty"], 1) . " / 5"]);
export_csv_write($output, [export_label("max_difficulty"), round((float)$summary["max_difficulty"], 1) . " / 5"]);
export_csv_write($output, [export_label("total_points"), (int)$summary["total_points"]]);

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
    WHERE ga.user_id = ?
    GROUP BY q.category
    ORDER BY q.category ASC
";

$stmt = $conn->prepare($categorySql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $catPrecision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    export_csv_write($output, [
        $row["category"],
        $total,
        $correct,
        $catPrecision . "%",
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5"
    ]);
}

$stmt->close();

export_csv_section($output, export_label("mistakes"));
export_csv_write($output, [
    export_label("date"),
    export_label("question"),
    export_label("category"),
    "A",
    "B",
    "C",
    "D",
    export_label("selected"),
    export_label("selected_answer"),
    export_label("correct"),
    export_label("correct_answer"),
    export_label("average_response_time"),
    export_label("difficulty"),
    export_label("explanation")
]);

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
";

$stmt = $conn->prepare($mistakesSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $options = [
        "A" => $row["option_a"],
        "B" => $row["option_b"],
        "C" => $row["option_c"],
        "D" => $row["option_d"]
    ];
    $selectedOption = strtoupper(trim($row["selected_option"] ?? ""));
    $correctOption = strtoupper(trim($row["correct_option"] ?? ""));

    export_csv_write($output, [
        $row["answered_at"],
        $row["question"],
        $row["category"],
        $row["option_a"],
        $row["option_b"],
        $row["option_c"],
        $row["option_d"],
        $row["selected_option"],
        $options[$selectedOption] ?? "",
        $row["correct_option"],
        $options[$correctOption] ?? "",
        round((float)$row["response_time"], 2) . "s",
        (int)round((float)$row["difficulty_level"]) . " / 5",
        $row["explanation"]
    ]);
}

$stmt->close();
$conn->close();

fclose($output);
exit;
?>
