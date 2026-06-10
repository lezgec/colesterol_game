<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/room_question_requirements.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$roomCode = strtoupper(trim($data["room_code"] ?? ""));

if ($roomCode === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código de sala vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    ensure_room_question_requirements_table($conn);

    $stmtRoom = $conn->prepare("
        SELECT id, status, language
        FROM game_rooms
        WHERE room_code = ?
    ");

    if (!$stmtRoom) {
        throw new Exception($conn->error);
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
    $language = $room["language"] ?: "es";
    $status = $room["status"];
    $stmtRoom->close();

    if ($status !== "waiting") {
        echo json_encode([
            "success" => false,
            "message" => "Solo se pueden modificar preguntas antes de iniciar la sala"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmtRequirements = $conn->prepare("
        SELECT category, difficulty_level, quantity
        FROM room_question_requirements
        WHERE room_id = ?
        ORDER BY category ASC, difficulty_level ASC
    ");

    if (!$stmtRequirements) {
        throw new Exception($conn->error);
    }

    $stmtRequirements->bind_param("i", $roomId);
    $stmtRequirements->execute();
    $requirementsResult = $stmtRequirements->get_result();

    $stmtAssigned = $conn->prepare("
        SELECT COUNT(DISTINCT q.id) AS total
        FROM room_questions rq
        INNER JOIN questions q ON rq.question_id = q.id
        WHERE rq.room_id = ?
          AND q.category = ?
          AND q.difficulty_level >= ?
          AND q.difficulty_level < ?
          AND q.status = 'verified'
          AND q.is_active = 1
    ");

    $stmtCandidates = $conn->prepare("
        SELECT q.id
        FROM questions q
        WHERE q.language = ?
          AND q.category = ?
          AND q.difficulty_level >= ?
          AND q.difficulty_level < ?
          AND q.status = 'verified'
          AND q.is_active = 1
          AND NOT EXISTS (
              SELECT 1
              FROM room_questions rq
              WHERE rq.room_id = ?
                AND rq.question_id = q.id
          )
        ORDER BY RAND()
        LIMIT ?
    ");

    $stmtInsert = $conn->prepare("
        INSERT INTO room_questions
            (room_id, question_id)
        VALUES
            (?, ?)
    ");

    if (!$stmtAssigned || !$stmtCandidates || !$stmtInsert) {
        throw new Exception($conn->error);
    }

    $inserted = 0;
    $blocks = 0;

    $conn->begin_transaction();

    while ($requirement = $requirementsResult->fetch_assoc()) {
        $category = $requirement["category"];
        $difficulty = (int)$requirement["difficulty_level"];
        $quantity = (int)$requirement["quantity"];
        [$minDifficulty, $maxDifficulty] = room_requirement_difficulty_bounds($difficulty);

        $stmtAssigned->bind_param(
            "isdd",
            $roomId,
            $category,
            $minDifficulty,
            $maxDifficulty
        );
        $stmtAssigned->execute();
        $assignedData = $stmtAssigned->get_result()->fetch_assoc();
        $assigned = (int)($assignedData["total"] ?? 0);
        $missing = max(0, $quantity - $assigned);

        if ($missing <= 0) {
            continue;
        }

        $stmtCandidates->bind_param(
            "ssddii",
            $language,
            $category,
            $minDifficulty,
            $maxDifficulty,
            $roomId,
            $missing
        );
        $stmtCandidates->execute();
        $candidateResult = $stmtCandidates->get_result();

        while ($candidate = $candidateResult->fetch_assoc()) {
            $questionId = (int)$candidate["id"];

            $stmtInsert->bind_param("ii", $roomId, $questionId);

            if (!$stmtInsert->execute()) {
                throw new Exception($stmtInsert->error);
            }

            $inserted++;
        }

        $blocks++;
    }

    $conn->commit();

    $stmtRequirements->close();
    $stmtAssigned->close();
    $stmtCandidates->close();
    $stmtInsert->close();

    echo json_encode([
        "success" => true,
        "message" => "Preguntas sincronizadas",
        "inserted" => $inserted,
        "blocks" => $blocks
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "No se pudieron sincronizar las preguntas",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
