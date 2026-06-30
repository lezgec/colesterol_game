<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$room_code = strtoupper(trim($_GET["code"] ?? ""));

if ($room_code === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        id,
        status,
        started_at,
        question_started_at,
        paused_at,
        finished_at,
        question_count,
        current_question_index
    FROM game_rooms
    WHERE room_code = ?
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $room_code);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sala no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "room_id" => (int)$room["id"],
    "status" => $room["status"],
    "started_at" => $room["started_at"],
    "question_started_at" => $room["question_started_at"],
    "paused_at" => $room["paused_at"],
    "finished_at" => $room["finished_at"],
    "question_count" => (int)$room["question_count"],
    "current_question_index" => (int)$room["current_question_index"],
    "finished" => $room["status"] === "finished",
    "paused" => $room["status"] === "paused"
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>