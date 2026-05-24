<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

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
        "error" => $conn->error
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
$initialDifficulty = (float)$room["initial_difficulty"];
$questionCount = (int)$room["question_count"];
$timeLimit = (int)$room["time_limit"];

if ($initialDifficulty < 1.0) {
    $initialDifficulty = 1.0;
}

if ($initialDifficulty > 5.0) {
    $initialDifficulty = 5.0;
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
        ORDER BY q.difficulty_level ASC, rq.id ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta de preguntas",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $roomId);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $letters = ["A", "B", "C", "D"];
    $correctOption = strtoupper(trim($row["correct_option"]));
    $correctIndex = array_search($correctOption, $letters, true);

    if ($correctIndex === false) {
        $correctIndex = 0;
    }

    $difficultyLevel = (float)$row["difficulty_level"];

    if ($difficultyLevel < 1.0) {
        $difficultyLevel = 1.0;
    }

    if ($difficultyLevel > 5.0) {
        $difficultyLevel = 5.0;
    }

    $questions[] = [
        "id" => (int)$row["id"],
        "question" => $row["question"],
        "options" => [
            $row["option_a"],
            $row["option_b"],
            $row["option_c"],
            $row["option_d"]
        ],
        "correct" => $correctIndex,
        "correct_option" => $correctOption,
        "explanation" => $row["explanation"],
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