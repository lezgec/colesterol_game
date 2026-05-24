<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

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

if (!in_array($question_mode, ["random", "selected"], true)) {
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

try {
    $conn->begin_transaction();

    $room_code = "";
    $roomId = 0;
    $created = false;

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $room_code = generateRoomCode();

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
            "ssdsiiii",
            $room_code,
            $name,
            $initialDifficulty,
            $language,
            $question_count,
            $time_limit,
            $question_mode,
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

    if ($question_mode === "selected") {
        if (!is_array($selected_questions) || count($selected_questions) === 0) {
            throw new Exception("Debe seleccionar preguntas");
        }

        $roomQuestionIds = array_values(array_unique(array_map("intval", $selected_questions)));
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
    }

    if (count($roomQuestionIds) === 0) {
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

    foreach ($roomQuestionIds as $questionId) {
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

    $realQuestionCount = count($roomQuestionIds);

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
        $realQuestionCount,
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
        "question_count" => $realQuestionCount
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