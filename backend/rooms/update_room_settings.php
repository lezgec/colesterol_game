<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/room_auth_helpers.php';
require_once __DIR__ . '/../../config/room_question_requirements.php';
require_once __DIR__ . '/../../config/question_categories.php';

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
$questionCount = (int)($data["question_count"] ?? 10);
$timeLimit = (int)($data["time_limit"] ?? 20);
$requirementsInput = is_array($data["requirements"] ?? null) ? $data["requirements"] : [];

if ($roomCode === "") {
    echo json_encode([
        "success" => false,
        "message" => "Codigo de sala vacio"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$questionCount = max(1, min(50, $questionCount));
$timeLimit = max(5, min(120, $timeLimit));
$requirements = [];
$requirementsTotal = 0;

foreach ($requirementsInput as $requirement) {
    if (!is_array($requirement)) {
        continue;
    }

    $category = normalize_question_category((string)($requirement["category"] ?? ""), $_SESSION["lang"] ?? "es");
    $difficulty = (int)($requirement["difficulty"] ?? $requirement["difficulty_level"] ?? 1);
    $quantity = (int)($requirement["quantity"] ?? 0);

    $difficulty = max(1, min(5, $difficulty));
    $quantity = max(0, min(50, $quantity));

    if ($category === "" || $quantity <= 0) {
        continue;
    }

    $key = strtolower($category) . "|" . $difficulty;

    if (!isset($requirements[$key])) {
        $requirements[$key] = [
            "category" => $category,
            "difficulty" => $difficulty,
            "quantity" => 0
        ];
    }

    $requirements[$key]["quantity"] += $quantity;
}

$requirements = array_values($requirements);

foreach ($requirements as $requirement) {
    $requirementsTotal += (int)$requirement["quantity"];
}

try {
    $room = require_room_owner_or_super_admin($conn, $roomCode);

    if ($room["status"] === "finished") {
        echo json_encode([
            "success" => false,
            "message" => "La sala ya finalizó y no se puede modificar"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $roomId = (int)$room["id"];
    $minimumPlayableCount = ((int)$room["current_question_index"]) + 1;
    ensure_room_question_requirements_table($conn);

    $oldRequirementsTotal = 0;
    $oldRequirementKeys = [];
    $stmtOldRequirements = $conn->prepare("
        SELECT category, difficulty_level, quantity
        FROM room_question_requirements
        WHERE room_id = ?
    ");

    if (!$stmtOldRequirements) {
        throw new Exception($conn->error);
    }

    $stmtOldRequirements->bind_param("i", $roomId);
    $stmtOldRequirements->execute();
    $oldRequirementResult = $stmtOldRequirements->get_result();

    while ($oldRequirement = $oldRequirementResult->fetch_assoc()) {
        $oldRequirementsTotal += (int)$oldRequirement["quantity"];
        $oldRequirementKeys[strtolower($oldRequirement["category"]) . "|" . (int)$oldRequirement["difficulty_level"]] = true;
    }

    $stmtOldRequirements->close();

    if ($requirementsTotal > 0 && $room["status"] !== "waiting") {
            $currentRoomQuestionCount = (int)($room["question_count"] ?? $questionCount);

            $stmtAssignedForNewBlock = $conn->prepare("
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

            if (!$stmtAssignedForNewBlock) {
                throw new Exception($conn->error);
            }

            foreach ($requirements as &$requirement) {
                $key = strtolower($requirement["category"]) . "|" . (int)$requirement["difficulty"];

                if (isset($oldRequirementKeys[$key])) {
                    continue;
                }

                [$minDifficulty, $maxDifficulty] = room_requirement_difficulty_bounds((int)$requirement["difficulty"]);
                $category = $requirement["category"];

                $stmtAssignedForNewBlock->bind_param("isdd", $roomId, $category, $minDifficulty, $maxDifficulty);
                $stmtAssignedForNewBlock->execute();
                $assignedData = $stmtAssignedForNewBlock->get_result()->fetch_assoc();
                $requirement["quantity"] += (int)($assignedData["total"] ?? 0);
            }

            unset($requirement);
            $stmtAssignedForNewBlock->close();
    }

    $questionCount = max($minimumPlayableCount, min(50, $questionCount));

    $conn->begin_transaction();

    $stmtUpdate = $conn->prepare("
        UPDATE game_rooms
        SET question_count = ?, time_limit = ?, question_mode = 'random'
        WHERE id = ?
    ");

    if (!$stmtUpdate) {
        throw new Exception($conn->error);
    }

    $stmtUpdate->bind_param("iii", $questionCount, $timeLimit, $roomId);

    if (!$stmtUpdate->execute()) {
        throw new Exception($stmtUpdate->error);
    }

    $stmtUpdate->close();

    $stmtDeleteRequirements = $conn->prepare("
        DELETE FROM room_question_requirements
        WHERE room_id = ?
    ");

    if (!$stmtDeleteRequirements) {
        throw new Exception($conn->error);
    }

    $stmtDeleteRequirements->bind_param("i", $roomId);
    $stmtDeleteRequirements->execute();
    $stmtDeleteRequirements->close();

    if (!empty($requirements)) {
        $stmtInsertRequirement = $conn->prepare("
            INSERT INTO room_question_requirements
                (room_id, category, difficulty_level, quantity)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
        ");

        if (!$stmtInsertRequirement) {
            throw new Exception($conn->error);
        }

        foreach ($requirements as $requirement) {
            $category = $requirement["category"];
            $difficulty = (int)$requirement["difficulty"];
            $quantity = (int)$requirement["quantity"];

            $stmtInsertRequirement->bind_param("isii", $roomId, $category, $difficulty, $quantity);

            if (!$stmtInsertRequirement->execute()) {
                throw new Exception($stmtInsertRequirement->error);
            }
        }

        $stmtInsertRequirement->close();
    }

    $removed = 0;

    if ($room["status"] === "waiting") {
        $stmtDeleteExtra = $conn->prepare("
            DELETE rq
            FROM room_questions rq
            INNER JOIN (
                SELECT id
                FROM room_questions
                WHERE room_id = ?
                ORDER BY id ASC
                LIMIT 18446744073709551615 OFFSET ?
            ) extra ON extra.id = rq.id
        ");

        if (!$stmtDeleteExtra) {
            throw new Exception($conn->error);
        }

        $stmtDeleteExtra->bind_param("ii", $roomId, $questionCount);
        $stmtDeleteExtra->execute();
        $removed = $stmtDeleteExtra->affected_rows;
        $stmtDeleteExtra->close();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Configuracion actualizada",
        "question_count" => $questionCount,
        "time_limit" => $timeLimit,
        "requirements_count" => count($requirements),
        "removed_questions" => max(0, $removed)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // Ignore rollback errors so the original failure is returned.
    }

    echo json_encode([
        "success" => false,
        "message" => "No se pudo actualizar la configuracion",
        "error" => $error->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
