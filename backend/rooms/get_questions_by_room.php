<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../questions/question_option_helpers.php';

$code = strtoupper(trim($_GET["code"] ?? ""));

if ($code === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código de sala vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sqlRoom = "SELECT
                id,
                initial_difficulty,
                language,
                question_count,
                time_limit,
                question_mode
            FROM game_rooms
            WHERE room_code = ?";

$stmtRoom = $conn->prepare($sqlRoom);

if (!$stmtRoom) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta de sala",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom->bind_param("s", $code);
$stmtRoom->execute();
$resRoom = $stmtRoom->get_result();

if ($resRoom->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sala no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $resRoom->fetch_assoc();

$roomId = (int)$room["id"];
$initialDifficulty = (int)round((float)$room["initial_difficulty"]);
$questionCount = (int)$room["question_count"];
$timeLimit = (int)$room["time_limit"];

if ($initialDifficulty < 1) {
    $initialDifficulty = 1;
}

if ($initialDifficulty > 5) {
    $initialDifficulty = 5;
}

if ($questionCount <= 0) {
    $questionCount = 10;
}

if ($timeLimit <= 0) {
    $timeLimit = 20;
}

$sql = "SELECT
            q.id,
            q.question,
            q.option_a,
            q.option_b,
            q.option_c,
            q.option_d,
            q.correct_option,
            q.explanation,
            q.category,
            q.language,
            q.difficulty_level,
            q.status,
            q.origin,
            q.is_active
        FROM room_questions rq
        INNER JOIN questions q ON rq.question_id = q.id
        WHERE
            rq.room_id = ?
            AND q.status = 'verified'
            AND q.is_active = 1
        ORDER BY rq.id ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta de preguntas",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $roomId);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $optionPayload = build_shuffled_question_payload($row);

    $difficultyLevel = (int)round((float)$row["difficulty_level"]);

    if ($difficultyLevel < 1) {
        $difficultyLevel = 1;
    }

    if ($difficultyLevel > 5) {
        $difficultyLevel = 5;
    }

    $questions[] = [
        "id" => (int)$row["id"],
        "question" => $row["question"],
        "options" => $optionPayload["options"],
        "option_letters" => $optionPayload["option_letters"],
        "correct" => $optionPayload["correct"],
        "correct_option" => $optionPayload["correct_option"],
        "display_correct_option" => $optionPayload["display_correct_option"],
        "category" => $row["category"],
        "language" => $row["language"],
        "difficulty_level" => $difficultyLevel
    ];
}

echo json_encode([
    "success" => true,
    "room" => [
        "id" => $roomId,
        "initial_difficulty" => $initialDifficulty,
        "language" => $room["language"],
        "question_count" => $questionCount,
        "time_limit" => $timeLimit,
        "question_mode" => $room["question_mode"]
    ],
    "questions" => $questions
], JSON_UNESCAPED_UNICODE);

$stmtRoom->close();
$stmt->close();
$conn->close();
?>
