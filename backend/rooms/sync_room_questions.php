<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/room_auth_helpers.php';
require_once __DIR__ . '/../../config/room_question_requirements.php';
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

if ($roomCode === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código de sala vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    ensure_room_question_requirements_table($conn);
    ensure_question_workflow_columns($conn);
    $accessSql = playable_question_access_sql("q");

    $room = require_room_owner_or_super_admin($conn, $roomCode);
    $roomId = (int)$room["id"];
    $language = $room["language"] ?: "es";
    $status = $room["status"];
    $questionCount = (int)$room["question_count"];
    $initialDifficulty = (float)($room["initial_difficulty"] ?? 1);

    if ($status === "finished") {
        echo json_encode([
            "success" => false,
            "message" => "La sala ya finalizó y no se pueden agregar preguntas"
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
          AND {$accessSql}
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
          AND {$accessSql}
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
    $hasRequirements = false;

    $conn->begin_transaction();

    while ($requirement = $requirementsResult->fetch_assoc()) {
        $hasRequirements = true;
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

    $stmtReady = $conn->prepare("
        SELECT COUNT(DISTINCT q.id) AS total
        FROM room_questions rq
        INNER JOIN questions q ON rq.question_id = q.id
        WHERE rq.room_id = ?
          AND q.status = 'verified'
          AND q.is_active = 1
          AND {$accessSql}
    ");

    $stmtRandomCandidates = $conn->prepare("
        SELECT q.id
        FROM questions q
        WHERE q.language = ?
          AND q.status = 'verified'
          AND q.is_active = 1
          AND {$accessSql}
          AND NOT EXISTS (
              SELECT 1
              FROM room_questions rq
              WHERE rq.room_id = ?
                AND rq.question_id = q.id
          )
        ORDER BY ABS(q.difficulty_level - ?) ASC, RAND()
        LIMIT ?
    ");

    if (!$stmtReady || !$stmtRandomCandidates) {
        throw new Exception($conn->error);
    }

    $stmtReady->bind_param("i", $roomId);
    $stmtReady->execute();
    $readyData = $stmtReady->get_result()->fetch_assoc();
    $assignedReady = (int)($readyData["total"] ?? 0);
    $missing = max(0, $questionCount - $assignedReady);

    if ($missing > 0) {
        $stmtRandomCandidates->bind_param(
            "sidi",
            $language,
            $roomId,
            $initialDifficulty,
            $missing
        );
        $stmtRandomCandidates->execute();
        $candidateResult = $stmtRandomCandidates->get_result();

        while ($candidate = $candidateResult->fetch_assoc()) {
            $questionId = (int)$candidate["id"];

            $stmtInsert->bind_param("ii", $roomId, $questionId);

            if (!$stmtInsert->execute()) {
                throw new Exception($stmtInsert->error);
            }

            $inserted++;
        }
    }

    $stmtReady->close();
    $stmtRandomCandidates->close();

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
