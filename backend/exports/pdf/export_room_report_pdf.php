<?php
session_start();

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../lang/translate.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!has_role(["teacher", "super_admin"])) {
    die("No autorizado");
}

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    die("Código de sala vacío");
}

$stmtRoom = $conn->prepare("
    SELECT id, room_code, name, status, question_count, time_limit, initial_difficulty, created_at, started_at, finished_at
    FROM game_rooms
    WHERE room_code = ?
");

$stmtRoom->bind_param("s", $roomCode);
$stmtRoom->execute();
$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    die("Sala no encontrada");
}

$room = $roomResult->fetch_assoc();
$roomId = (int)$room["id"];
$roomStatusLabel = room_status_label($room["status"]);
$stmtRoom->close();

$rankingRows = "";

$rankingSql = "
    SELECT
        player_name,
        COUNT(*) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(SUM(score_earned), 0) AS total_score,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty
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

$totalPlayers = 0;
$totalAnswersGlobal = 0;
$totalCorrectGlobal = 0;

while ($row = $result->fetch_assoc()) {
    $totalPlayers++;

    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];

    $totalAnswersGlobal += $total;
    $totalCorrectGlobal += $correct;

    $precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    $rankingRows .= "
        <tr>
            <td>{$totalPlayers}</td>
            <td>" . htmlspecialchars($row["player_name"]) . "</td>
            <td>{$row["total_score"]}</td>
            <td>{$correct} / {$total}</td>
            <td>{$precision}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
            <td>" . round((float)$row["avg_difficulty"], 1) . " / 5</td>
        </tr>
    ";
}

$stmt->close();

$globalPrecision = $totalAnswersGlobal > 0
    ? round(($totalCorrectGlobal / $totalAnswersGlobal) * 100, 2)
    : 0;

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

    $categoryRows .= "
        <tr>
            <td>" . htmlspecialchars($row["category"]) . "</td>
            <td>{$total}</td>
            <td>{$correct}</td>
            <td>{$precision}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
            <td>" . round((float)$row["avg_difficulty"], 1) . " / 5</td>
        </tr>
    ";
}

$stmt->close();

$failedRows = "";

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
    LIMIT 10
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

    $failedRows .= "
        <tr>
            <td>" . htmlspecialchars($row["question"]) . "</td>
            <td>" . htmlspecialchars($row["category"]) . "</td>
            <td>{$incorrect} / {$total}</td>
            <td>{$failureRate}%</td>
            <td>" . round((float)$row["avg_response_time"], 2) . "s</td>
        </tr>
    ";
}

$stmt->close();
$conn->close();

$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "Helvetica");

$dompdf = new Dompdf($options);

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

    h1 {
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
        width: 20%;
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

<h1>Room Report</h1>

<div class='meta'>
    Room: <strong>" . htmlspecialchars($room["name"]) . "</strong><br>
    Code: <strong>" . htmlspecialchars($room["room_code"]) . "</strong><br>
    Status: " . htmlspecialchars($roomStatusLabel) . "<br>
    Generated at: " . date("Y-m-d H:i:s") . "
</div>

<div class='summary'>
    <div class='card'>Players<strong>{$totalPlayers}</strong></div>
    <div class='card'>Answers<strong>{$totalAnswersGlobal}</strong></div>
    <div class='card'>Correct<strong>{$totalCorrectGlobal}</strong></div>
    <div class='card'>Precision<strong>{$globalPrecision}%</strong></div>
    <div class='card'>Questions<strong>" . (int)$room["question_count"] . "</strong></div>
</div>

<h2>Ranking</h2>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Player</th>
            <th>Score</th>
            <th>Correct</th>
            <th>Precision</th>
            <th>Avg Time</th>
            <th>Avg Difficulty</th>
        </tr>
    </thead>
    <tbody>
        {$rankingRows}
    </tbody>
</table>

<h2>Performance by Category</h2>

<table>
    <thead>
        <tr>
            <th>Category</th>
            <th>Total</th>
            <th>Correct</th>
            <th>Precision</th>
            <th>Avg Time</th>
            <th>Avg Difficulty</th>
        </tr>
    </thead>
    <tbody>
        {$categoryRows}
    </tbody>
</table>

<h2>Most Failed Questions</h2>

<table>
    <thead>
        <tr>
            <th>Question</th>
            <th>Category</th>
            <th>Incorrect</th>
            <th>Failure Rate</th>
            <th>Avg Time</th>
        </tr>
    </thead>
    <tbody>
        {$failedRows}
    </tbody>
</table>

<div class='footer'>
    Report generated by Serious Game: Cholesterol.
</div>

</body>
</html>
";

$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

$filename = "room_report_" . $roomCode . "_" . date("Y-m-d_H-i-s") . ".pdf";

$dompdf->stream($filename, [
    "Attachment" => true
]);

exit;
?>
