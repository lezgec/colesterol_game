<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode(["success" => false, "message" => "No autorizado"], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$room_code = strtoupper(trim($data["room_code"] ?? ""));

if ($room_code === "") {
    echo json_encode(["success" => false, "message" => "Código vacío"], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    UPDATE game_rooms
    SET status = 'started',
        paused_at = NULL,
        question_started_at = NOW()
    WHERE room_code = ?
      AND status = 'paused'
");

$stmt->bind_param("s", $room_code);

echo json_encode([
    "success" => $stmt->execute(),
    "message" => "Sala reanudada"
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>