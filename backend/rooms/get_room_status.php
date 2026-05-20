<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../../config/db.php';

$room_code = strtoupper(trim($_GET["code"] ?? ""));

$stmt = $conn->prepare("SELECT status FROM game_rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Sala no encontrada"]);
    exit;
}

$room = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "status" => $room["status"]
]);
?>