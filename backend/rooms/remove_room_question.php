<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$roomCode = strtoupper(trim($data["room_code"] ?? ""));
$questionId = (int)($data["question_id"] ?? 0);

if ($roomCode === "" || $questionId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom = $conn->prepare("
    SELECT id, status
    FROM game_rooms
    WHERE room_code = ?
");

if (!$stmtRoom) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom->bind_param("s", $roomCode);
$stmtRoom->execute();
$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sala no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $roomResult->fetch_assoc();
$roomId = (int)$room["id"];
$status = $room["status"];
$stmtRoom->close();

if ($status !== "waiting") {
    echo json_encode([
        "success" => false,
        "message" => "Solo se pueden quitar preguntas antes de iniciar la sala"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtDelete = $conn->prepare("
    DELETE FROM room_questions
    WHERE room_id = ?
      AND question_id = ?
    LIMIT 1
");

if (!$stmtDelete) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar eliminación",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtDelete->bind_param("ii", $roomId, $questionId);

if ($stmtDelete->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Pregunta quitada de la sala"
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo quitar la pregunta",
        "error" => $stmtDelete->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmtDelete->close();
$conn->close();
?>
