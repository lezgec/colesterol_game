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

$stmtRoom = $conn->prepare("
    SELECT id, current_question_index, question_count
    FROM game_rooms
    WHERE room_code = ?
");

$stmtRoom->bind_param("s", $room_code);
$stmtRoom->execute();

$result = $stmtRoom->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Sala no encontrada"], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $result->fetch_assoc();

$roomId = (int)$room["id"];
$current = (int)$room["current_question_index"];
$total = (int)$room["question_count"];

$stmtRoom->close();

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
?>