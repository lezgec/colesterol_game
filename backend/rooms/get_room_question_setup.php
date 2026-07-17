<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/room_auth_helpers.php';
require_once __DIR__ . '/../../config/room_question_requirements.php';
require_once __DIR__ . '/../questions/question_workflow_helpers.php';

require_json_role(["teacher", "super_admin"]);

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

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
    $requiredTotal = (int)$room["question_count"];


    $stmtReady = $conn->prepare("
        SELECT COUNT(DISTINCT q.id) AS total
        FROM room_questions rq
        INNER JOIN questions q ON rq.question_id = q.id
        WHERE rq.room_id = ?
          AND q.status = 'verified'
          AND q.is_active = 1
          AND {$accessSql}
    ");

    if (!$stmtReady) {
        throw new Exception($conn->error);
    }

    $stmtReady->bind_param("i", $roomId);
    $stmtReady->execute();
    $readyData = $stmtReady->get_result()->fetch_assoc();
    $assignedReadyTotal = (int)($readyData["total"] ?? 0);
    $stmtReady->close();

    $stmtRequirements = $conn->prepare("
        SELECT id, category, difficulty_level, quantity
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

    $requirements = [];
    $requirementMissingTotal = 0;

    $stmtAssignedBlock = $conn->prepare("
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

    $stmtAvailableBlock = $conn->prepare("
        SELECT COUNT(*) AS total
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
    ");

    if (!$stmtAssignedBlock || !$stmtAvailableBlock) {
        throw new Exception($conn->error);
    }

    while ($requirement = $requirementsResult->fetch_assoc()) {
        $category = $requirement["category"];
        $difficulty = (int)$requirement["difficulty_level"];
        $quantity = (int)$requirement["quantity"];
        [$minDifficulty, $maxDifficulty] = room_requirement_difficulty_bounds($difficulty);

        $stmtAssignedBlock->bind_param(
            "isdd",
            $roomId,
            $category,
            $minDifficulty,
            $maxDifficulty
        );
        $stmtAssignedBlock->execute();
        $assignedData = $stmtAssignedBlock->get_result()->fetch_assoc();
        $assigned = (int)($assignedData["total"] ?? 0);

        $stmtAvailableBlock->bind_param(
            "ssddi",
            $language,
            $category,
            $minDifficulty,
            $maxDifficulty,
            $roomId
        );
        $stmtAvailableBlock->execute();
        $availableData = $stmtAvailableBlock->get_result()->fetch_assoc();
        $available = (int)($availableData["total"] ?? 0);
        $missing = max(0, $quantity - $assigned);
        $requirementMissingTotal += $missing;

        $requirements[] = [
            "id" => (int)$requirement["id"],
            "category" => $category,
            "difficulty" => $difficulty,
            "quantity" => $quantity,
            "assigned" => $assigned,
            "available" => $available,
            "missing" => $missing
        ];
    }

    $stmtRequirements->close();
    $stmtAssignedBlock->close();
    $stmtAvailableBlock->close();

    $stmtQuestions = $conn->prepare("
        SELECT
            rq.id AS room_question_id,
            q.id,
            q.question,
            q.category,
            q.difficulty_level,
            q.language,
            q.status,
            q.is_active,
            q.correct_option
        FROM room_questions rq
        INNER JOIN questions q ON rq.question_id = q.id
        WHERE rq.room_id = ?
        ORDER BY rq.id ASC
    ");

    if (!$stmtQuestions) {
        throw new Exception($conn->error);
    }

    $stmtQuestions->bind_param("i", $roomId);
    $stmtQuestions->execute();
    $questionsResult = $stmtQuestions->get_result();

    $assignedQuestions = [];

    while ($question = $questionsResult->fetch_assoc()) {
        $assignedQuestions[] = [
            "room_question_id" => (int)$question["room_question_id"],
            "id" => (int)$question["id"],
            "question" => $question["question"],
            "category" => $question["category"],
            "difficulty_level" => (int)round((float)$question["difficulty_level"]),
            "language" => $question["language"],
            "status" => $question["status"],
            "is_active" => (int)$question["is_active"],
            "correct_option" => $question["correct_option"]
        ];
    }

    $stmtQuestions->close();

    $missingByTotal = max(0, $requiredTotal - $assignedReadyTotal);
    $missingTotal = count($requirements) > 0
        ? max($requirementMissingTotal, $missingByTotal)
        : $missingByTotal;

    echo json_encode([
        "success" => true,
        "room" => [
            "id" => $roomId,
            "room_code" => $room["room_code"],
            "name" => $room["name"],
            "status" => $room["status"],
            "language" => $language,
            "question_count" => $requiredTotal,
            "time_limit" => (int)$room["time_limit"],
            "question_mode" => $room["question_mode"]
        ],
        "required_total" => $requiredTotal,
        "assigned_ready_total" => $assignedReadyTotal,
        "missing_total" => $missingTotal,
        "ready" => $requiredTotal > 0 && $assignedReadyTotal >= $requiredTotal && $missingTotal === 0,
        "requirements" => $requirements,
        "assigned_questions" => $assignedQuestions
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo cargar la configuración de preguntas",
        "error" => app_error_detail($e)
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
