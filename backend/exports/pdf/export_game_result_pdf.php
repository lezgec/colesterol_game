<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../export_helpers.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!current_session_is_active()) {
    die("No autorizado");
}

$resultId = (int)($_GET["result_id"] ?? 0);
$currentUserId = current_user_id();

function pdf_h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function pdf_option_label_text($answer, $letter) {
    $letter = strtoupper((string)$letter);
    $map = [
        "A" => "option_a",
        "B" => "option_b",
        "C" => "option_c",
        "D" => "option_d"
    ];

    if (!isset($map[$letter])) {
        return "-";
    }

    return trim($letter . ". " . ($answer[$map[$letter]] ?? ""));
}

$stmt = $conn->prepare("
    SELECT
        gr.*,
        r.name AS room_name,
        r.room_code,
        r.created_by AS room_created_by
    FROM game_results gr
    LEFT JOIN game_rooms r ON r.id = gr.room_id
    WHERE gr.id = ?
    LIMIT 1
");

if (!$stmt) {
    die("No se pudo preparar el reporte");
}

$stmt->bind_param("i", $resultId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    die("Resultado no disponible");
}

$roomOwnerId = $result["room_created_by"] !== null
    ? (int)$result["room_created_by"]
    : null;

$canView = ((int)($result["user_id"] ?? 0) === $currentUserId)
    || is_super_admin()
    || ($roomOwnerId !== null && $roomOwnerId === $currentUserId);

if (!$canView) {
    die("No autorizado");
}

$answers = [];
$limit = max(1, (int)$result["total_questions"]);
$roomId = $result["room_id"] !== null ? (int)$result["room_id"] : null;

if ($roomId !== null) {
    $playerName = (string)($result["player_name"] ?? "");
    $stmt = $conn->prepare("
        SELECT
            ga.*,
            q.question,
            q.option_a,
            q.option_b,
            q.option_c,
            q.option_d,
            q.category
        FROM game_answers ga
        INNER JOIN questions q ON q.id = ga.question_id
        WHERE ga.room_id = ?
          AND ga.player_name = ?
          AND ga.answered_at <= ?
        ORDER BY ga.answered_at DESC, ga.id DESC
        LIMIT ?
    ");

    if ($stmt) {
        $stmt->bind_param("issi", $roomId, $playerName, $result["played_at"], $limit);
    }
} else {
    $stmt = $conn->prepare("
        SELECT
            ga.*,
            q.question,
            q.option_a,
            q.option_b,
            q.option_c,
            q.option_d,
            q.category
        FROM game_answers ga
        INNER JOIN questions q ON q.id = ga.question_id
        WHERE ga.user_id = ?
          AND ga.room_id IS NULL
          AND ga.answered_at <= ?
        ORDER BY ga.answered_at DESC, ga.id DESC
        LIMIT ?
    ");

    if ($stmt) {
        $stmt->bind_param("isi", $currentUserId, $result["played_at"], $limit);
    }
}

if ($stmt) {
    $stmt->execute();
    $answerResult = $stmt->get_result();

    while ($row = $answerResult->fetch_assoc()) {
        $answers[] = $row;
    }

    $stmt->close();
}

$answers = array_reverse($answers);

$categoryStats = [];
$difficultyStats = [];
$answersRows = "";

foreach ($answers as $index => $answer) {
    $category = trim((string)($answer["category"] ?? export_label("category")));
    if ($category === "") {
        $category = export_label("category");
    }

    $difficulty = (string)round((float)$answer["difficulty_level"], 1);
    $isCorrect = (int)$answer["is_correct"] === 1;
    $responseTime = (float)$answer["response_time"];

    if (!isset($categoryStats[$category])) {
        $categoryStats[$category] = [
            "total" => 0,
            "correct" => 0,
            "time" => 0,
            "difficulty" => 0
        ];
    }

    $categoryStats[$category]["total"]++;
    $categoryStats[$category]["correct"] += $isCorrect ? 1 : 0;
    $categoryStats[$category]["time"] += $responseTime;
    $categoryStats[$category]["difficulty"] += (float)$answer["difficulty_level"];

    if (!isset($difficultyStats[$difficulty])) {
        $difficultyStats[$difficulty] = [
            "total" => 0,
            "correct" => 0
        ];
    }

    $difficultyStats[$difficulty]["total"]++;
    $difficultyStats[$difficulty]["correct"] += $isCorrect ? 1 : 0;

    $selected = pdf_option_label_text($answer, $answer["selected_option"] ?? "");
    $correctText = pdf_option_label_text($answer, $answer["correct_option"] ?? "");
    $state = $isCorrect ? export_label("correct") : export_label("incorrect");

    $answersRows .= "
        <tr>
            <td>" . ($index + 1) . "</td>
            <td>" . pdf_h($answer["question"]) . "</td>
            <td>" . pdf_h($category) . "</td>
            <td>" . pdf_h($selected) . "</td>
            <td>" . pdf_h($correctText) . "</td>
            <td>" . pdf_h($state) . "</td>
            <td>" . round($responseTime, 2) . "s</td>
            <td>" . round((float)$answer["difficulty_level"], 1) . " / 5</td>
        </tr>
    ";
}

$categoryRows = "";
foreach ($categoryStats as $category => $stat) {
    $total = (int)$stat["total"];
    $correct = (int)$stat["correct"];
    $precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    $avgTime = $total > 0 ? round($stat["time"] / $total, 2) : 0;
    $avgDifficulty = $total > 0 ? round($stat["difficulty"] / $total, 1) : 0;

    $categoryRows .= "
        <tr>
            <td>" . pdf_h($category) . "</td>
            <td>{$total}</td>
            <td>{$correct}</td>
            <td>{$precision}%</td>
            <td>{$avgTime}s</td>
            <td>{$avgDifficulty} / 5</td>
        </tr>
    ";
}

ksort($difficultyStats, SORT_NUMERIC);
$difficultyRows = "";
foreach ($difficultyStats as $difficulty => $stat) {
    $total = (int)$stat["total"];
    $correct = (int)$stat["correct"];
    $precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    $difficultyRows .= "
        <tr>
            <td>" . pdf_h($difficulty) . " / 5</td>
            <td>{$total}</td>
            <td>{$correct}</td>
            <td>{$precision}%</td>
        </tr>
    ";
}

if ($answersRows === "") {
    $answersRows = "<tr><td colspan='8'>Sin detalle de respuestas</td></tr>";
}

if ($categoryRows === "") {
    $categoryRows = "<tr><td colspan='6'>Sin datos</td></tr>";
}

if ($difficultyRows === "") {
    $difficultyRows = "<tr><td colspan='4'>Sin datos</td></tr>";
}

$correct = (int)$result["correct_answers"];
$total = (int)$result["total_questions"];
$precision = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
$mode = $roomId !== null
    ? export_label("room") . " " . ($result["room_code"] ?? ("#" . $roomId))
    : "Solo";

if ($precision >= 80) {
    $feedbackTitle = current_lang() === "en" ? "Excellent mastery" : "Excelente dominio";
    $feedbackMessage = current_lang() === "en"
        ? "Accuracy was high. Keep the pace and try higher difficulty rounds."
        : "La precision fue alta. Mantén el ritmo e intenta sostener el rendimiento en dificultades superiores.";
} elseif ($precision >= 60) {
    $feedbackTitle = current_lang() === "en" ? "Good progress" : "Buen avance";
    $feedbackMessage = current_lang() === "en"
        ? "There is a solid base. Review missed topics to improve quickly."
        : "Hay una base solida. Repasar los temas fallados puede mejorar rapidamente la precision.";
} else {
    $feedbackTitle = current_lang() === "en" ? "Practice round" : "Partida para reforzar";
    $feedbackMessage = current_lang() === "en"
        ? "This result shows topics worth practicing before increasing difficulty."
        : "Este resultado muestra temas que conviene practicar antes de subir la dificultad.";
}

$conn->close();

$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "Helvetica");

$dompdf = new Dompdf($options);
$reportTitle = export_label("game_result_report");
$logoHtml = export_pdf_logo_html();
$traceHtml = export_pdf_trace_html($reportTitle, [
    export_label("result_id") => $resultId,
    export_label("player") => $result["player_name"] ?: ($_SESSION["user_name"] ?? ""),
    export_label("game_mode") => $mode,
    export_label("date") => $result["played_at"]
]);

$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
    body { font-family: Helvetica, Arial, sans-serif; color: #111827; font-size: 11px; }
    .report-header { border-bottom: 2px solid #e5e7eb; margin-bottom: 14px; padding-bottom: 10px; }
    .report-logo { width: 42px; height: 42px; vertical-align: middle; margin-right: 10px; }
    h1 { display: inline-block; vertical-align: middle; color: #1f2937; margin-bottom: 4px; }
    h2 { color: #374151; margin-top: 22px; border-bottom: 1px solid #d1d5db; padding-bottom: 6px; }
    .meta { color: #6b7280; margin-bottom: 16px; }
    .summary { display: table; width: 100%; margin-top: 14px; }
    .card { display: table-cell; width: 16%; border: 1px solid #d1d5db; padding: 8px; text-align: center; }
    .card strong { display: block; font-size: 15px; margin-top: 5px; }
    .feedback { border: 1px solid #bfdbfe; background: #eff6ff; padding: 12px; border-radius: 8px; margin-top: 12px; }
    .feedback strong { display: block; color: #1e3a8a; margin-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #1f2937; color: white; padding: 6px; border: 1px solid #d1d5db; font-size: 10px; }
    td { padding: 6px; border: 1px solid #d1d5db; vertical-align: top; font-size: 9px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .footer { margin-top: 24px; font-size: 9px; color: #6b7280; }
</style>
</head>
<body>
<div class='report-header'>{$logoHtml}<h1>" . pdf_h($reportTitle) . "</h1></div>
{$traceHtml}
<div class='summary'>
    <div class='card'>" . export_label("score") . "<strong>" . (int)$result["score"] . "</strong></div>
    <div class='card'>" . export_label("correct") . "<strong>{$correct} / {$total}</strong></div>
    <div class='card'>" . export_label("precision") . "<strong>{$precision}%</strong></div>
    <div class='card'>" . export_label("difficulty") . "<strong>" . round((float)$result["final_difficulty"], 1) . " / 5</strong></div>
    <div class='card'>" . export_label("time") . "<strong>" . pdf_h($result["played_at"]) . "</strong></div>
</div>
<div class='feedback'>
    <strong>" . pdf_h($feedbackTitle) . "</strong>
    " . pdf_h($feedbackMessage) . "
</div>
<h2>" . export_label("performance_by_category") . "</h2>
<table>
    <thead><tr><th>" . export_label("category") . "</th><th>" . export_label("total") . "</th><th>" . export_label("correct") . "</th><th>" . export_label("precision") . "</th><th>" . export_label("average_response_time") . "</th><th>" . export_label("average_difficulty") . "</th></tr></thead>
    <tbody>{$categoryRows}</tbody>
</table>
<h2>" . export_label("difficulty") . "</h2>
<table>
    <thead><tr><th>" . export_label("difficulty") . "</th><th>" . export_label("total") . "</th><th>" . export_label("correct") . "</th><th>" . export_label("precision") . "</th></tr></thead>
    <tbody>{$difficultyRows}</tbody>
</table>
<h2>" . export_label("answers") . "</h2>
<table>
    <thead><tr><th>#</th><th>" . export_label("question") . "</th><th>" . export_label("category") . "</th><th>" . export_label("selected") . "</th><th>" . export_label("correct_answer") . "</th><th>" . export_label("status") . "</th><th>" . export_label("time") . "</th><th>" . export_label("difficulty") . "</th></tr></thead>
    <tbody>{$answersRows}</tbody>
</table>
<div class='footer'>" . export_label("report_footer") . "</div>
</body>
</html>
";

$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

$filename = "game_result_report_" . $resultId . "_" . date("Y-m-d_H-i-s") . ".pdf";

$dompdf->stream($filename, [
    "Attachment" => true
]);

exit;
?>
