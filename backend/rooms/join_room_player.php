<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$room_code = strtoupper(trim($data["room_code"] ?? ""));
$player_name = trim($data["player_name"] ?? "");

if ($room_code === "" || $player_name === "") {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, status FROM game_rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Sala no encontrada"]);
    exit;
}

$room = $result->fetch_assoc();

if ($room["status"] !== "waiting") {
    echo json_encode(["success" => false, "message" => "La partida ya inició"]);
    exit;
}

$room_id = (int)$room["id"];

$check = $conn->prepare("SELECT id FROM room_players WHERE room_id = ? AND player_name = ?");
$check->bind_param("is", $room_id, $player_name);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO room_players (room_id, player_name) VALUES (?, ?)");
    $insert->bind_param("is", $room_id, $player_name);
    $insert->execute();
}

echo json_encode(["success" => true]);