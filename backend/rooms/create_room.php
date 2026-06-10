<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../../config/room_question_requirements.php';

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    !in_array($_SESSION["user_role"], ["teacher", "super_admin"], true)
) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$language = trim($data["language"] ?? "es");
$question_count = (int)($data["question_count"] ?? 10);
$time_limit = (int)($data["time_limit"] ?? 20);
$question_mode = trim($data["question_mode"] ?? "random");
$selected_questions = $data["selected_questions"] ?? [];
$question_blocks = $data["question_blocks"] ?? [];

$initialDifficulty = 1.0;
$createdBy = (int)$_SESSION["user_id"];

if ($name === "") {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de la sala es obligatorio"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

if ($question_count < 1) {
    $question_count = 10;
}

if ($question_count > 50) {
    $question_count = 50;
}

if ($time_limit < 5) {
    $time_limit = 20;
}

if ($time_limit > 120) {
    $time_limit = 120;
}

if (!in_array($question_mode, ["configured", "random", "selected"], true)) {
    $question_mode = "random";
}

function generateRoomCode($length = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $code;
}

function difficultyBounds(int $difficulty): array {
    if ($difficulty < 1) {
        $difficulty = 1;
    }

    if ($difficulty > 5) {
        $difficulty = 5;
    }

    return [
        (float)$difficulty,
        $difficulty >= 5 ? 5.1 : (float)($difficulty + 1)
    ];
}

function interleaveQuestionBuckets(array $buckets): array {
    $ordered = [];
    $hasItems = true;

    while ($hasItems) {
        $hasItems = false;

        foreach ($buckets as $bucketIndex => $bucket) {
            if (count($bucket) === 0) {
                continue;
            }

            $ordered[] = array_shift($buckets[$bucketIndex]);
            $hasItems = true;
        }
    }

    return $ordered;
}

try {
    ensure_room_question_requirements_table($conn);

    $conn->begin_transaction();

    $room_code = "";
    $roomId = 0;
    $created = false;

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $room_code = generateRoomCode();
        $storedQuestionMode = $question_mode;

        $stmtRoom = $conn->prepare("
            INSERT INTO game_rooms
            (
                room_code,
                name,
                initial_difficulty,
                language,
                question_count,
                time_limit,
                question_mode,
                status,
                current_question_index,
                created_by
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, 'waiting', 0, ?)
        ");

        if (!$stmtRoom) {
            throw new Exception($conn->error);
        }

        $stmtRoom->bind_param(
            "ssdsiisi",
            $room_code,
            $name,
            $initialDifficulty,
            $language,
            $question_count,
            $time_limit,
            $storedQuestionMode,
            $createdBy
        );

        if ($stmtRoom->execute()) {
            $roomId = (int)$stmtRoom->insert_id;
            $created = true;
            $stmtRoom->close();
            break;
        }

        $stmtRoom->close();
    }

    if (!$created) {
        throw new Exception("No se pudo generar un código único para la sala");
    }

    $roomQuestionIds = [];
    $roomRequirements = [];
    $requiredQuestionCount = $question_count;

    if ($question_mode === "configured") {
        if (!is_array($question_blocks) || count($question_blocks) === 0) {
            throw new Exception("Debe agregar al menos un bloque de preguntas");
        }

        $buckets = [];
        $totalRequested = 0;

        foreach ($question_blocks as $block) {
            $category = normalize_question_category(
                (string)($block["category"] ?? ""),
                $language
            );
            $difficulty = (int)($block["difficulty"] ?? 1);
            $quantity = (int)($block["quantity"] ?? 0);

            if ($quantity < 1) {
                continue;
            }

            if ($quantity > 50) {
                $quantity = 50;
            }

            [$minDifficulty, $maxDifficulty] = room_requirement_difficulty_bounds($difficulty);

            $requirementKey = strtolower($category) . "|" . $difficulty;

            if (!isset($roomRequirements[$requirementKey])) {
                $roomRequirements[$requirementKey] = [
                    "category" => $category,
                    "difficulty" => $difficulty,
                    "quantity" => 0
                ];
            }

            $roomRequirements[$requirementKey]["quantity"] += $quantity;

            $stmtQuestions = $conn->prepare("
                SELECT id
                FROM questions
                WHERE 
                    language = ?
                    AND category = ?
                    AND difficulty_level >= ?
                    AND difficulty_level < ?
                    AND status = 'verified'
                    AND is_active = 1
                ORDER BY RAND()
                LIMIT ?
            ");

            if (!$stmtQuestions) {
                throw new Exception($conn->error);
            }

            $stmtQuestions->bind_param(
                "ssddi",
                $language,
                $category,
                $minDifficulty,
                $maxDifficulty,
                $quantity
            );

            if (!$stmtQuestions->execute()) {
                throw new Exception($stmtQuestions->error);
            }

            $resultQuestions = $stmtQuestions->get_result();
            $bucket = [];

            while ($row = $resultQuestions->fetch_assoc()) {
                $bucket[] = (int)$row["id"];
            }

            $stmtQuestions->close();

            $buckets[] = $bucket;
            $totalRequested += $quantity;
        }

        if ($totalRequested === 0) {
            throw new Exception("Debe agregar al menos un bloque válido");
        }

        if ($totalRequested > 50) {
            throw new Exception("La sala no puede tener más de 50 preguntas");
        }

        $roomQuestionIds = interleaveQuestionBuckets($buckets);
        $question_count = $totalRequested;
        $requiredQuestionCount = $totalRequested;

    } elseif ($question_mode === "selected") {
        if (!is_array($selected_questions) || count($selected_questions) === 0) {
            throw new Exception("Debe seleccionar preguntas");
        }

        $roomQuestionIds = array_values(array_unique(array_map("intval", $selected_questions)));
        $requiredQuestionCount = count($roomQuestionIds);
    } else {
        $stmtQuestions = $conn->prepare("
            SELECT id
            FROM questions
            WHERE 
                language = ?
                AND status = 'verified'
                AND is_active = 1
            ORDER BY ABS(difficulty_level - ?) ASC, RAND()
            LIMIT ?
        ");

        if (!$stmtQuestions) {
            throw new Exception($conn->error);
        }

        $stmtQuestions->bind_param(
            "sdi",
            $language,
            $initialDifficulty,
            $question_count
        );

        if (!$stmtQuestions->execute()) {
            throw new Exception($stmtQuestions->error);
        }

        $resultQuestions = $stmtQuestions->get_result();

        while ($row = $resultQuestions->fetch_assoc()) {
            $roomQuestionIds[] = (int)$row["id"];
        }

        $stmtQuestions->close();
        $requiredQuestionCount = count($roomQuestionIds);
    }

    if (count($roomQuestionIds) === 0 && $question_mode !== "configured") {
        throw new Exception("No se encontraron preguntas verificadas y activas para este idioma");
    }

    $stmtInsertQuestion = $conn->prepare("
        INSERT INTO room_questions
            (room_id, question_id)
        VALUES
            (?, ?)
    ");

    if (!$stmtInsertQuestion) {
        throw new Exception($conn->error);
    }

    foreach (array_values(array_unique($roomQuestionIds)) as $questionId) {
        $stmtInsertQuestion->bind_param(
            "ii",
            $roomId,
            $questionId
        );

        if (!$stmtInsertQuestion->execute()) {
            throw new Exception($stmtInsertQuestion->error);
        }
    }

    $stmtInsertQuestion->close();

    if ($question_mode === "configured" && count($roomRequirements) > 0) {
        $stmtRequirement = $conn->prepare("
            INSERT INTO room_question_requirements
                (room_id, category, difficulty_level, quantity)
            VALUES
                (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantity = VALUES(quantity)
        ");

        if (!$stmtRequirement) {
            throw new Exception($conn->error);
        }

        foreach ($roomRequirements as $requirement) {
            $requirementCategory = $requirement["category"];
            $requirementDifficulty = (int)$requirement["difficulty"];
            $requirementQuantity = (int)$requirement["quantity"];

            $stmtRequirement->bind_param(
                "isii",
                $roomId,
                $requirementCategory,
                $requirementDifficulty,
                $requirementQuantity
            );

            if (!$stmtRequirement->execute()) {
                throw new Exception($stmtRequirement->error);
            }
        }

        $stmtRequirement->close();
    }

    $assignedQuestionCount = count(array_values(array_unique($roomQuestionIds)));

    $stmtUpdateCount = $conn->prepare("
        UPDATE game_rooms
        SET question_count = ?
        WHERE id = ?
    ");

    if (!$stmtUpdateCount) {
        throw new Exception($conn->error);
    }

    $stmtUpdateCount->bind_param(
        "ii",
        $requiredQuestionCount,
        $roomId
    );

    if (!$stmtUpdateCount->execute()) {
        throw new Exception($stmtUpdateCount->error);
    }

    $stmtUpdateCount->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Sala creada correctamente",
        "room_code" => $room_code,
        "room_id" => $roomId,
        "question_count" => $requiredQuestionCount,
        "assigned_question_count" => $assignedQuestionCount,
        "missing_question_count" => max(0, $requiredQuestionCount - $assignedQuestionCount)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "Error al crear la sala",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
