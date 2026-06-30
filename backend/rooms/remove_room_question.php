<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/room_auth_helpers.php';

require_csrf_token();

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$roomCode = strtoupper(trim($data["room_code"] ?? ""));
$questionIds = $data["question_ids"] ?? [];

if (!is_array($questionIds)) {
    $questionIds = [];
}

if (isset($data["question_id"])) {
    $questionIds[] = (int)$data["question_id"];
}

$questionIds = array_values(array_unique(array_filter(array_map("intval", $questionIds), fn($id) => $id > 0)));

if ($roomCode === "" || count($questionIds) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = require_room_owner_or_super_admin($conn, $roomCode);
$roomId = (int)$room["id"];
$status = $room["status"];

if ($status === "finished") {
    echo json_encode([
        "success" => false,
        "message" => "La sala ya finalizó y no se pueden quitar preguntas"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$placeholders = implode(",", array_fill(0, count($questionIds), "?"));
$types = str_repeat("i", count($questionIds));

$stmtDelete = $conn->prepare("
    DELETE rq
    FROM room_questions rq
    WHERE rq.room_id = ?
      AND rq.question_id IN ({$placeholders})
      AND NOT EXISTS (
          SELECT 1
          FROM game_answers ga
          WHERE ga.room_id = rq.room_id
            AND ga.question_id = rq.question_id
      )
");

if (!$stmtDelete) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar eliminación",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtDelete->bind_param("i" . $types, $roomId, ...$questionIds);

if ($stmtDelete->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Preguntas quitadas de la sala",
        "removed" => $stmtDelete->affected_rows
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
