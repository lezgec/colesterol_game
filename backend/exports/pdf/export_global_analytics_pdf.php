<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../lang/translate.php';
require_once __DIR__ . '/../export_helpers.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!has_role(["teacher", "super_admin"])) {
    die("No autorizado");
}

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

$precision = $totalAnswers > 0
    ? round(($correctAnswers / $totalAnswers) * 100, 2)
    : 0;

$playersRows = "";

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
    LIMIT 10
";

$result = $conn->query($playersSql);

$position = 1;

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];

    $playerPrecision = $total > 0
        ? round(($correct / $total) * 100, 2)
        : 0;

    $playersRows .= "
        <tr>
            <td>{$position}</td>
            <td>" . htmlspecialchars($row["player_name"]) . "</td>
            <td>" . (int)$row["total_score"] . "</td>
            <td>{$correct} / {$total}</td>
            <td>{$playerPrecision}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
            <td>" . round((float)$row["avg_difficulty"], 1) . " / 5</td>
        </tr>
    ";

    $position++;
}

$roomsRows = "";

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
    LIMIT 10
";

$result = $conn->query($roomsSql);

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];

    $roomPrecision = $total > 0
        ? round(($correct / $total) * 100, 2)
        : 0;

    $roomsRows .= "
        <tr>
            <td>" . htmlspecialchars($row["room_code"]) . "</td>
            <td>" . htmlspecialchars($row["name"]) . "</td>
            <td>" . htmlspecialchars(room_status_label($row["status"])) . "</td>
            <td>" . (int)$row["total_players"] . "</td>
            <td>{$roomPrecision}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
        </tr>
    ";
}

$categoryRows = "";

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

    $categoryPrecision = $total > 0
        ? round(($correct / $total) * 100, 2)
        : 0;

    $categoryRows .= "
        <tr>
            <td>" . htmlspecialchars($row["category"]) . "</td>
            <td>{$total}</td>
            <td>{$correct}</td>
            <td>{$categoryPrecision}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
            <td>" . round((float)$row["avg_difficulty"], 1) . " / 5</td>
        </tr>
    ";
}

$failedRows = "";

$failedSql = "
    SELECT
        q.id,
        q.question,
        q.category,
        COUNT(*) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    GROUP BY q.id, q.question, q.category
    ORDER BY (1 - (COALESCE(SUM(ga.is_correct), 0) / COUNT(*))) DESC, COUNT(*) DESC
    LIMIT 10
";

$result = $conn->query($failedSql);

while ($row = $result->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $incorrect = $total - $correct;

    $failureRate = $total > 0
        ? round(($incorrect / $total) * 100, 2)
        : 0;

    $failedRows .= "
        <tr>
            <td>" . (int)$row["id"] . "</td>
            <td>" . htmlspecialchars($row["question"]) . "</td>
            <td>" . htmlspecialchars($row["category"]) . "</td>
            <td>{$incorrect} / {$total}</td>
            <td>{$failureRate}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
        </tr>
    ";
}

$conn->close();

$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "Helvetica");

$dompdf = new Dompdf($options);
$reportTitle = export_label("analytics_report");
$logoHtml = export_pdf_logo_html();

$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #111827;
        font-size: 11px;
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
        margin-bottom: 16px;
    }

    .summary {
        display: table;
        width: 100%;
        margin-top: 14px;
    }

    .card {
        display: table-cell;
        width: 16%;
        border: 1px solid #d1d5db;
        padding: 8px;
        text-align: center;
    }

    .card strong {
        display: block;
        font-size: 15px;
        margin-top: 5px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th {
        background: #1f2937;
        color: white;
        padding: 6px;
        border: 1px solid #d1d5db;
        font-size: 10px;
    }

    td {
        padding: 6px;
        border: 1px solid #d1d5db;
        vertical-align: top;
        font-size: 9px;
    }

    tr:nth-child(even) td {
        background: #f9fafb;
    }

    .footer {
        margin-top: 24px;
        font-size: 9px;
        color: #6b7280;
    }
</style>

</head>
<body>

<div class='report-header'>{$logoHtml}<h1>" . htmlspecialchars($reportTitle) . "</h1></div>

<div class='meta'>
    " . export_label("generated_at") . ": " . date("Y-m-d H:i:s") . "
</div>

<div class='summary'>
    <div class='card'>" . export_label("users") . "<strong>" . (int)$summary["total_users"] . "</strong></div>
    <div class='card'>" . export_label("total_games") . "<strong>" . (int)$summary["total_games"] . "</strong></div>
    <div class='card'>" . export_label("rooms") . "<strong>" . (int)$summary["total_rooms"] . "</strong></div>
    <div class='card'>" . export_label("questions") . "<strong>" . (int)$summary["total_questions"] . "</strong></div>
    <div class='card'>" . export_label("answers") . "<strong>{$totalAnswers}</strong></div>
    <div class='card'>" . export_label("precision") . "<strong>{$precision}%</strong></div>
</div>

<h2>" . export_label("top_players") . "</h2>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>" . export_label("player") . "</th>
            <th>" . export_label("score") . "</th>
            <th>" . export_label("correct") . "</th>
            <th>" . export_label("precision") . "</th>
            <th>" . export_label("average_response_time") . "</th>
            <th>" . export_label("average_difficulty") . "</th>
        </tr>
    </thead>
    <tbody>
        {$playersRows}
    </tbody>
</table>

<h2>" . export_label("top_rooms") . "</h2>

<table>
    <thead>
        <tr>
            <th>" . export_label("code") . "</th>
            <th>" . export_label("room") . "</th>
            <th>" . export_label("status") . "</th>
            <th>" . export_label("players") . "</th>
            <th>" . export_label("precision") . "</th>
            <th>" . export_label("average_response_time") . "</th>
        </tr>
    </thead>
    <tbody>
        {$roomsRows}
    </tbody>
</table>

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
        {$categoryRows}
    </tbody>
</table>

<h2>" . export_label("most_failed_questions") . "</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>" . export_label("question") . "</th>
            <th>" . export_label("category") . "</th>
            <th>" . export_label("incorrect") . "</th>
            <th>" . export_label("failure_rate") . "</th>
            <th>" . export_label("average_response_time") . "</th>
        </tr>
    </thead>
    <tbody>
        {$failedRows}
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

$filename = "global_analytics_report_" . date("Y-m-d_H-i-s") . ".pdf";

$dompdf->stream($filename, [
    "Attachment" => true
]);

exit;
?>
