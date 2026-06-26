<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../questions/question_workflow_helpers.php';

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

$questionIds = array_values(array_unique(array_filter(array_map("intval", $questionIds), fn($id) => $id > 0)));

if ($roomCode === "" || count($questionIds) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $inTransaction = false;
    ensure_question_workflow_columns($conn);
    $accessSql = playable_question_access_sql("q");

    $stmtRoom = $conn->prepare("
        SELECT id, status, question_count
        FROM game_rooms
        WHERE room_code = ?
        LIMIT 1
    ");

    if (!$stmtRoom) {
        throw new Exception($conn->error);
    }

    $stmtRoom->bind_param("s", $roomCode);
    $stmtRoom->execute();
    $room = $stmtRoom->get_result()->fetch_assoc();
    $stmtRoom->close();

    if (!$room) {
        echo json_encode([
            "success" => false,
            "message" => "Sala no encontrada"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (($room["status"] ?? "") === "finished") {
        echo json_encode([
            "success" => false,
            "message" => "La sala ya finalizo y no se pueden agregar preguntas"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $roomId = (int)$room["id"];
    $placeholders = implode(",", array_fill(0, count($questionIds), "?"));
    $types = str_repeat("i", count($questionIds));

    $stmtQuestions = $conn->prepare("
        SELECT q.id
        FROM questions q
        WHERE q.id IN ({$placeholders})
          AND q.status = 'verified'
          AND q.is_active = 1
          AND {$accessSql}
    ");

    if (!$stmtQuestions) {
        throw new Exception($conn->error);
    }

    $stmtQuestions->bind_param($types, ...$questionIds);
    $stmtQuestions->execute();
    $result = $stmtQuestions->get_result();

    $allowedIds = [];

    while ($row = $result->fetch_assoc()) {
        $allowedIds[] = (int)$row["id"];
    }

    $stmtQuestions->close();

    if (count($allowedIds) === 0) {
        echo json_encode([
            "success" => false,
            "message" => "No hay preguntas validas para agregar"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtInsert = $conn->prepare("
        INSERT INTO room_questions (room_id, question_id)
        SELECT ?, ?
        WHERE NOT EXISTS (
            SELECT 1
            FROM room_questions
            WHERE room_id = ?
              AND question_id = ?
        )
    ");

    if (!$stmtInsert) {
        throw new Exception($conn->error);
    }

    $inserted = 0;

    $conn->begin_transaction();
    $inTransaction = true;

    foreach ($allowedIds as $questionId) {
        $stmtInsert->bind_param("iiii", $roomId, $questionId, $roomId, $questionId);

        if (!$stmtInsert->execute()) {
            throw new Exception($stmtInsert->error);
        }

        $inserted += $stmtInsert->affected_rows;
    }

    $stmtInsert->close();

    $stmtCount = $conn->prepare("
        SELECT COUNT(DISTINCT question_id) AS total
        FROM room_questions
        WHERE room_id = ?
    ");

    if (!$stmtCount) {
        throw new Exception($conn->error);
    }

    $stmtCount->bind_param("i", $roomId);
    $stmtCount->execute();
    $totalAssigned = (int)($stmtCount->get_result()->fetch_assoc()["total"] ?? 0);
    $stmtCount->close();

    if ($totalAssigned > (int)$room["question_count"]) {
        $stmtUpdate = $conn->prepare("
            UPDATE game_rooms
            SET question_count = ?
            WHERE id = ?
        ");

        if (!$stmtUpdate) {
            throw new Exception($conn->error);
        }

        $stmtUpdate->bind_param("ii", $totalAssigned, $roomId);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    $conn->commit();
    $inTransaction = false;

    echo json_encode([
        "success" => true,
        "message" => "Preguntas agregadas a la sala",
        "inserted" => $inserted,
        "assigned_total" => $totalAssigned
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $exception) {
    if (!empty($inTransaction)) {
        $conn->rollback();
    }

    echo json_encode([
        "success" => false,
        "message" => "No se pudieron agregar las preguntas",
        "error" => $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
