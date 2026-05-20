<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$room_code = strtoupper(trim($data["room_code"] ?? ""));
$player_name = trim($data["player_name"] ?? "");
$score = (int)($data["score"] ?? 0);
$correct_answers = (int)($data["correct_answers"] ?? 0);
$total_questions = (int)($data["total_questions"] ?? 0);

if ($room_code === "" || $player_name === "") {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$stmtRoom = $conn->prepare("SELECT id, difficulty FROM game_rooms WHERE room_code = ?");
$stmtRoom->bind_param("s", $room_code);
$stmtRoom->execute();
$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Sala no encontrada"]);
    exit;
}

$room = $roomResult->fetch_assoc();
$room_id = (int)$room["id"];
$difficulty = $room["difficulty"];

$check = $conn->prepare("SELECT id FROM game_results WHERE room_id = ? AND player_name = ?");
$check->bind_param("is", $room_id, $player_name);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();

    $stmt = $conn->prepare("
        UPDATE game_results
        SET score = ?, correct_answers = ?, total_questions = ?, difficulty = ?
        WHERE id = ?
    ");

    $stmt->bind_param("iiisi", $score, $correct_answers, $total_questions, $difficulty, $existing["id"]);
} else {
    $stmt = $conn->prepare("
        INSERT INTO game_results 
        (user_id, room_id, player_name, score, correct_answers, total_questions, lives_remaining, difficulty)
        VALUES (NULL, ?, ?, ?, ?, ?, 0, ?)
    ");

    $stmt->bind_param("isiiis", $room_id, $player_name, $score, $correct_answers, $total_questions, $difficulty);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}
?>