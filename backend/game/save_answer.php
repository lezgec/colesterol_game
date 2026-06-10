<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = isset($data["user_id"]) ? (int)$data["user_id"] : null;
$room_id = isset($data["room_id"]) ? (int)$data["room_id"] : null;

$player_name = trim($data["player_name"] ?? "");

$question_id = (int)($data["question_id"] ?? 0);

$selected_option = trim($data["selected_option"] ?? "");
$correct_option = trim($data["correct_option"] ?? "");

$is_correct = (int)($data["is_correct"] ?? 0);

$response_time = (int)($data["response_time"] ?? 0);

$difficulty_level = (float)($data["difficulty_level"] ?? 1.0);

$score_earned = (int)($data["score_earned"] ?? 0);

$game_mode = trim($data["game_mode"] ?? "solo");

if ($question_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Pregunta inválida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO game_answers (
        user_id,
        room_id,
        player_name,
        question_id,
        selected_option,
        correct_option,
        is_correct,
        response_time,
        difficulty_level,
        score_earned,
        game_mode
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error prepare",
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "iisissiidis",
    $user_id,
    $room_id,
    $player_name,
    $question_id,
    $selected_option,
    $correct_option,
    $is_correct,
    $response_time,
    $difficulty_level,
    $score_earned,
    $game_mode
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error insert",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>