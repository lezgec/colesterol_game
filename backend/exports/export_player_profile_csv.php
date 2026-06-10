<?php
session_start();

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    die("No autorizado");
}

$userId = (int)$_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "player";

$filename = "player_profile_report_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ["PLAYER PROFILE REPORT"]);
fputcsv($output, ["Player", $userName]);
fputcsv($output, ["Generated at", date("Y-m-d H:i:s")]);
fputcsv($output, []);

fputcsv($output, ["SUMMARY"]);

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

fputcsv($output, ["Total answers", $totalAnswers]);
fputcsv($output, ["Correct answers", $correctAnswers]);
fputcsv($output, ["Precision", $precision . "%"]);
fputcsv($output, ["Average response time", round((float)$summary["avg_response_time"], 2) . "s"]);
fputcsv($output, ["Average difficulty", round((float)$summary["avg_difficulty"], 1) . " / 5"]);
fputcsv($output, ["Max difficulty", round((float)$summary["max_difficulty"], 1) . " / 5"]);
fputcsv($output, ["Total points", (int)$summary["total_points"]]);

fputcsv($output, []);
fputcsv($output, ["PERFORMANCE BY CATEGORY"]);
fputcsv($output, ["Category", "Total answers", "Correct answers", "Precision", "Average response time", "Average difficulty"]);

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

    fputcsv($output, [
        $row["category"],
        $total,
        $correct,
        $catPrecision . "%",
        round((float)$row["avg_response_time"], 2) . "s",
        round((float)$row["avg_difficulty"], 1) . " / 5"
    ]);
}

$stmt->close();

fputcsv($output, []);
fputcsv($output, ["MISTAKES"]);
fputcsv($output, [
    "Date",
    "Question",
    "Category",
    "Option A",
    "Option B",
    "Option C",
    "Option D",
    "Selected option",
    "Selected answer",
    "Correct option",
    "Correct answer",
    "Response time",
    "Difficulty",
    "Explanation"
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

    fputcsv($output, [
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
        round((float)$row["difficulty_level"], 1) . " / 5",
        $row["explanation"]
    ]);
}

$stmt->close();
$conn->close();

fclose($output);
exit;
?>
