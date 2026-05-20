<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../../config/db.php';

$room_code = strtoupper(trim($_GET["code"] ?? ""));

$stmt = $conn->prepare("SELECT id FROM game_rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$room = $result->fetch_assoc();
$room_id = (int)$room["id"];

$sql = "SELECT player_name, joined_at FROM room_players WHERE room_id = ? ORDER BY joined_at ASC";
$stmtPlayers = $conn->prepare($sql);
$stmtPlayers->bind_param("i", $room_id);
$stmtPlayers->execute();

$playersResult = $stmtPlayers->get_result();
$players = [];

while ($row = $playersResult->fetch_assoc()) {
    $players[] = $row;
}

echo json_encode($players, JSON_UNESCAPED_UNICODE);
?>