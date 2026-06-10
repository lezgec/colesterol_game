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
$room_code = strtoupper(trim($data["room_code"] ?? ""));

if ($room_code === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom = $conn->prepare("
    SELECT id, question_count, status
    FROM game_rooms
    WHERE room_code = ?
");

$stmtRoom->bind_param("s", $room_code);
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
$room_id = (int)$room["id"];
$requiredQuestions = (int)$room["question_count"];
$roomStatus = $room["status"];

$stmtRoom->close();

if ($roomStatus !== "waiting") {
    echo json_encode([
        "success" => false,
        "message" => "La sala no está disponible para iniciar"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtQuestions = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM room_questions
    INNER JOIN questions q ON room_questions.question_id = q.id
    WHERE room_questions.room_id = ?
      AND q.status = 'verified'
      AND q.is_active = 1
");

$stmtQuestions->bind_param("i", $room_id);
$stmtQuestions->execute();

$qData = $stmtQuestions->get_result()->fetch_assoc();
$availableQuestions = (int)($qData["total"] ?? 0);

$stmtQuestions->close();

if ($availableQuestions <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "La sala no tiene preguntas"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($requiredQuestions <= 0 || $availableQuestions < $requiredQuestions) {
    echo json_encode([
        "success" => false,
        "message" => "La sala aún no tiene el número completo de preguntas",
        "required_questions" => $requiredQuestions,
        "available_questions" => $availableQuestions,
        "missing_questions" => max(0, $requiredQuestions - $availableQuestions)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    UPDATE game_rooms
    SET
        status = 'started',
        started_at = NOW(),
        question_started_at = NOW(),
        paused_at = NULL,
        finished_at = NULL,
        current_question_index = 0
    WHERE id = ?
");

$stmt->bind_param("i", $room_id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Partida iniciada",
        "question_count" => $requiredQuestions
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo iniciar",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>
