<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$difficulty = trim($data["difficulty"] ?? "easy");
$language = trim($data["language"] ?? "es");
$question_count = (int)($data["question_count"] ?? 10);
$time_limit = (int)($data["time_limit"] ?? 20);
$question_mode = trim($data["question_mode"] ?? "random");
$selected_questions = $data["selected_questions"] ?? [];

if ($name === "") {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de la sala es obligatorio"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($difficulty, ["easy", "medium", "hard"], true)) {
    $difficulty = "easy";
}

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

if ($question_count < 1) {
    $question_count = 1;
}

if ($question_count > 50) {
    $question_count = 50;
}

if ($time_limit < 5) {
    $time_limit = 5;
}

if ($time_limit > 120) {
    $time_limit = 120;
}

if (!in_array($question_mode, ["random", "selected"], true)) {
    $question_mode = "random";
}

if ($question_mode === "selected") {
    if (!is_array($selected_questions) || count($selected_questions) === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Debe seleccionar al menos una pregunta"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $selected_questions = array_values(array_unique(array_map("intval", $selected_questions)));
    $question_count = count($selected_questions);
}

function generateRoomCode($length = 6) {
    $characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $code = "";

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}

$conn->begin_transaction();

try {
    $roomCode = "";
    $created = false;
    $attempts = 0;
    $roomId = 0;

    while (!$created && $attempts < 10) {
        $roomCode = generateRoomCode();

        $sql = "INSERT INTO game_rooms 
                (room_code, name, difficulty, language, question_count, time_limit, question_mode, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'waiting')";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "ssssiis",
            $roomCode,
            $name,
            $difficulty,
            $language,
            $question_count,
            $time_limit,
            $question_mode
        );

        if ($stmt->execute()) {
            $created = true;
            $roomId = $stmt->insert_id;
        } else {
            $attempts++;
        }

        $stmt->close();
    }

    if (!$created) {
        throw new Exception("No se pudo generar un código único para la sala");
    }

    $roomQuestionIds = [];

    if ($question_mode === "selected") {
        $roomQuestionIds = $selected_questions;
    } else {
        $getRandomQuestions = $conn->prepare("
            SELECT id 
            FROM questions 
            WHERE difficulty = ? AND language = ?
            ORDER BY RAND()
            LIMIT ?
        ");

        if (!$getRandomQuestions) {
            throw new Exception($conn->error);
        }

        $getRandomQuestions->bind_param("ssi", $difficulty, $language, $question_count);
        $getRandomQuestions->execute();

        $randomResult = $getRandomQuestions->get_result();

        while ($row = $randomResult->fetch_assoc()) {
            $roomQuestionIds[] = (int)$row["id"];
        }

        $getRandomQuestions->close();

        if (count($roomQuestionIds) === 0) {
            throw new Exception("No existen preguntas disponibles para esa dificultad e idioma");
        }

        $question_count = count($roomQuestionIds);

        $updateCount = $conn->prepare("UPDATE game_rooms SET question_count = ? WHERE id = ?");
        $updateCount->bind_param("ii", $question_count, $roomId);
        $updateCount->execute();
        $updateCount->close();
    }

    $insertQuestion = $conn->prepare(
        "INSERT INTO room_questions (room_id, question_id) VALUES (?, ?)"
    );

    if (!$insertQuestion) {
        throw new Exception($conn->error);
    }

    foreach ($roomQuestionIds as $questionId) {
        $insertQuestion->bind_param("ii", $roomId, $questionId);
        $insertQuestion->execute();
    }

    $insertQuestion->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Sala creada correctamente",
        "room_code" => $roomCode,
        "room_id" => $roomId,
        "name" => $name,
        "difficulty" => $difficulty,
        "language" => $language,
        "question_count" => $question_count,
        "time_limit" => $time_limit,
        "question_mode" => $question_mode
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