<?php
session_start();

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../export_helpers.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!current_session_is_active()) {
    die("No autorizado");
}

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$userId = is_super_admin() && $requestedUserId > 0
    ? $requestedUserId
    : (int)$_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "Player";

$stmtUser = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$userRow = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if ($userRow) {
    $userName = $userRow["name"];
}

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
$categoriesResult = $stmt->get_result();

$categoriesRows = "";

while ($row = $categoriesResult->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $catPrecision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    $categoriesRows .= "
        <tr>
            <td>" . htmlspecialchars($row["category"]) . "</td>
            <td>{$total}</td>
            <td>{$correct}</td>
            <td>{$catPrecision}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
            <td>" . round((float)$row["avg_difficulty"], 1) . " / 5</td>
        </tr>
    ";
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
    LIMIT 20
";

$stmt = $conn->prepare($mistakesSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$mistakesResult = $stmt->get_result();

$mistakesRows = "";

while ($row = $mistakesResult->fetch_assoc()) {
    $options = [
        "A" => $row["option_a"],
        "B" => $row["option_b"],
        "C" => $row["option_c"],
        "D" => $row["option_d"]
    ];
    $selectedOption = strtoupper(trim($row["selected_option"] ?? ""));
    $correctOption = strtoupper(trim($row["correct_option"] ?? ""));
    $selectedAnswer = $options[$selectedOption] ?? "";
    $correctAnswer = $options[$correctOption] ?? "";
    $optionsText = "";

    foreach ($options as $letter => $text) {
        $labels = [];

        if ($letter === $selectedOption) {
            $labels[] = export_label("selected");
        }

        if ($letter === $correctOption) {
            $labels[] = export_label("correct");
        }

        $labelText = count($labels) > 0
            ? " (" . implode(" / ", $labels) . ")"
            : "";

        $optionsText .= "<strong>{$letter}</strong>: " . htmlspecialchars($text) . htmlspecialchars($labelText) . "<br>";
    }

    $mistakesRows .= "
        <tr>
            <td>" . htmlspecialchars($row["answered_at"]) . "</td>
            <td>" . htmlspecialchars($row["question"]) . "</td>
            <td>" . htmlspecialchars($row["category"]) . "</td>
            <td>{$optionsText}</td>
            <td>" . htmlspecialchars($selectedOption . " - " . $selectedAnswer) . "</td>
            <td>" . htmlspecialchars($correctOption . " - " . $correctAnswer) . "</td>
            <td>" . round((float)$row["response_time"], 2) . "s</td>
            <td>" . (int)round((float)$row["difficulty_level"]) . " / 5</td>
        </tr>
    ";
}

$stmt->close();
$conn->close();

$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "Helvetica");

$dompdf = new Dompdf($options);
$reportTitle = export_label("player_report");
$logoHtml = export_pdf_logo_html();
$traceHtml = export_pdf_trace_html($reportTitle, [
    export_label("player") => $userName
]);

$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #111827;
        font-size: 12px;
    }

    .report-header {
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 14px;
        padding-bottom: 10px;
    }

    .report-logo {
        width: 42px;
        height: 42px;
        vertical-align: middle;
        margin-right: 10px;
    }

    h1 {
        display: inline-block;
        vertical-align: middle;
        color: #1f2937;
        margin-bottom: 4px;
    }

    h2 {
        color: #374151;
        margin-top: 24px;
        border-bottom: 1px solid #d1d5db;
        padding-bottom: 6px;
    }

    .meta {
        color: #6b7280;
        margin-bottom: 20px;
    }

    .summary {
        display: table;
        width: 100%;
        margin-top: 16px;
    }

    .card {
        display: table-cell;
        width: 16%;
        border: 1px solid #d1d5db;
        padding: 10px;
        text-align: center;
    }

    .card strong {
        display: block;
        font-size: 16px;
        margin-top: 6px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    th {
        background: #1f2937;
        color: white;
        padding: 7px;
        border: 1px solid #d1d5db;
        font-size: 11px;
    }

    td {
        padding: 7px;
        border: 1px solid #d1d5db;
        vertical-align: top;
        font-size: 10px;
    }

    tr:nth-child(even) td {
        background: #f9fafb;
    }

    .footer {
        margin-top: 30px;
        font-size: 10px;
        color: #6b7280;
    }
</style>

</head>
<body>

<div class='report-header'>{$logoHtml}<h1>" . htmlspecialchars($reportTitle) . "</h1></div>

{$traceHtml}

<div class='summary'>
    <div class='card'>" . export_label("answers") . "<strong>{$totalAnswers}</strong></div>
    <div class='card'>" . export_label("correct") . "<strong>{$correctAnswers}</strong></div>
    <div class='card'>" . export_label("precision") . "<strong>{$precision}%</strong></div>
    <div class='card'>" . export_label("average_response_time") . "<strong>" . round((float)$summary["avg_response_time"], 2) . "s</strong></div>
    <div class='card'>" . export_label("average_difficulty") . "<strong>" . round((float)$summary["avg_difficulty"], 1) . "/5</strong></div>
    <div class='card'>" . export_label("points") . "<strong>" . (int)$summary["total_points"] . "</strong></div>
</div>

<h2>" . export_label("performance_by_category") . "</h2>

<table>
    <thead>
        <tr>
            <th>" . export_label("category") . "</th>
            <th>" . export_label("total") . "</th>
            <th>" . export_label("correct") . "</th>
            <th>" . export_label("precision") . "</th>
            <th>" . export_label("average_response_time") . "</th>
            <th>" . export_label("average_difficulty") . "</th>
        </tr>
    </thead>
    <tbody>
        {$categoriesRows}
    </tbody>
</table>

<h2>" . export_label("recent_mistakes") . "</h2>

<table>
    <thead>
        <tr>
            <th>" . export_label("date") . "</th>
            <th>" . export_label("question") . "</th>
            <th>" . export_label("category") . "</th>
            <th>" . export_label("options") . "</th>
            <th>" . export_label("selected") . "</th>
            <th>" . export_label("correct") . "</th>
            <th>" . export_label("time") . "</th>
            <th>" . export_label("difficulty") . "</th>
        </tr>
    </thead>
    <tbody>
        {$mistakesRows}
    </tbody>
</table>

<div class='footer'>
    " . export_label("report_footer") . "
</div>

</body>
</html>
";

$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

$filename = "player_profile_report_" . date("Y-m-d_H-i-s") . ".pdf";

$dompdf->stream($filename, [
    "Attachment" => true
]);

exit;
?>
