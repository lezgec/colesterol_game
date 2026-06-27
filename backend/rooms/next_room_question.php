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

$roomId = (int)$room["id"];
$current = (int)$room["current_question_index"];
$total = (int)$room["question_count"];
$next = $current + 1;

if ($next >= $total) {
    $next = max(0, $total - 1);

    $stmt = $conn->prepare("
        UPDATE game_rooms
        SET status = 'finished',
            finished_at = NOW(),
            current_question_index = ?
        WHERE id = ?
    ");

    $stmt->bind_param("ii", $next, $roomId);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Sala finalizada",
        "finished" => true,
        "current_question_index" => $next
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();
    exit;
}

$stmt = $conn->prepare("
    UPDATE game_rooms
    SET current_question_index = ?,
        question_started_at = NOW(),
        status = 'started'
    WHERE id = ?
");

$stmt->bind_param("ii", $next, $roomId);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Pregunta avanzada",
    "finished" => false,
    "current_question_index" => $next
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
