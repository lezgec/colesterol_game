<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../badges/evaluate_badges.php';

require_csrf_token();

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "JSON no valido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim($data["name"] ?? "");
$language = trim($data["language"] ?? "es");
$questionCount = (int)($data["question_count"] ?? 10);
$timeLimit = (int)($data["time_limit"] ?? 20);
$questionMode = trim($data["question_mode"] ?? "random");
$createdBy = (int)$_SESSION["user_id"];
$initialDifficulty = 1.0;

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

if ($questionCount < 1) {
    $questionCount = 10;
}

if ($questionCount > 50) {
    $questionCount = 50;
}

if ($timeLimit < 5) {
    $timeLimit = 20;
}

if ($timeLimit > 120) {
    $timeLimit = 120;
}

if (!in_array($questionMode, ["configured", "random", "selected"], true)) {
    $questionMode = "random";
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
    $roomCode = "";
    $roomId = 0;
    $created = false;

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $roomCode = generateRoomCode();

        $stmt = $conn->prepare("
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

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "ssdsiisi",
            $roomCode,
            $name,
            $initialDifficulty,
            $language,
            $questionCount,
            $timeLimit,
            $questionMode,
            $createdBy
        );

        if ($stmt->execute()) {
            $roomId = (int)$stmt->insert_id;
            $created = true;
            $stmt->close();
            break;
        }

        $lastError = $stmt->error;
        $stmt->close();

        if (stripos($lastError, "duplicate") === false) {
            throw new Exception($lastError);
        }
    }

    if (!$created) {
        throw new Exception("No se pudo generar un codigo unico para la sala");
    }

    $newTeacherBadges = [];

    try {
        $newTeacherBadges = evaluateTeacherBadges($conn, $createdBy);
    } catch (Throwable $badgeError) {
        $newTeacherBadges = [];
    }

    echo json_encode([
        "success" => true,
        "message" => "Sala creada correctamente",
        "room_code" => $roomCode,
        "room_id" => $roomId,
        "question_count" => $questionCount,
        "assigned_question_count" => 0,
        "missing_question_count" => $questionCount,
        "new_teacher_badges" => $newTeacherBadges
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al crear la sala",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
