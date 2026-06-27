<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/room_auth_helpers.php';

require_csrf_token();

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$roomCode = strtoupper(trim($data["room_code"] ?? ""));
$room = require_room_owner_or_super_admin($conn, $roomCode);

$stmt = $conn->prepare("
    UPDATE game_rooms
    SET status = 'paused',
        paused_at = NOW()
    WHERE id = ?
      AND status = 'started'
");

$roomId = (int)$room["id"];
$stmt->bind_param("i", $roomId);
$success = $stmt->execute();

echo json_encode([
    "success" => $success,
    "message" => $success ? "Sala pausada" : "No se pudo pausar la sala"
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
