<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$room_code = strtoupper(trim($_GET["code"] ?? ""));

if ($room_code === "") {
    echo json_encode(["success" => false, "message" => "Código vacío"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT status, started_at, time_limit, question_count
    FROM game_rooms
    WHERE room_code = ?
");

$stmt->bind_param("s", $room_code);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Sala no encontrada"]);
    exit;
}

$room = $result->fetch_assoc();

$status = $room["status"];
$startedAt = $room["started_at"];
$timeLimit = (int)$room["time_limit"];
$questionCount = (int)$room["question_count"];

if ($timeLimit <= 0) {
    $timeLimit = 20;
}

if ($status !== "started" || !$startedAt) {
    echo json_encode([
        "success" => true,
        "status" => $status,
        "current_question_index" => 0,
        "time_left" => $timeLimit,
        "finished" => false
    ]);
    exit;
}

$startedTimestamp = strtotime($startedAt);
$now = time();

$elapsed = max(0, $now - $startedTimestamp);

$currentQuestionIndex = floor($elapsed / $timeLimit);
$timeLeft = $timeLimit - ($elapsed % $timeLimit);

$finished = $currentQuestionIndex >= $questionCount;

echo json_encode([
    "success" => true,
    "status" => $status,
    "current_question_index" => (int)$currentQuestionIndex,
    "time_left" => (int)$timeLeft,
    "finished" => $finished
], JSON_UNESCAPED_UNICODE);
?>