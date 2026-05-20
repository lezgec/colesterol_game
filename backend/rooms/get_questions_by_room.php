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

$sqlRoom = "SELECT id, difficulty, language, question_count, time_limit, question_mode 
            FROM game_rooms 
            WHERE room_code = ?";

$stmtRoom = $conn->prepare($sqlRoom);
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
$questionCount = (int)$room["question_count"];
$timeLimit = (int)$room["time_limit"];

if ($questionCount <= 0) {
    $questionCount = 10;
}

if ($timeLimit <= 0) {
    $timeLimit = 20;
}

$sql = "SELECT q.id, q.question, q.option_a, q.option_b, q.option_c, q.option_d,
               q.correct_option, q.explanation, q.category, q.difficulty, q.language
        FROM room_questions rq
        INNER JOIN questions q ON rq.question_id = q.id
        WHERE rq.room_id = ?
        ORDER BY rq.id ASC";

$stmt = $conn->prepare($sql);
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
        "difficulty" => $row["difficulty"],
        "language" => $row["language"]
    ];
}

echo json_encode([
    "success" => true,
    "room" => [
        "id" => $roomId,
        "difficulty" => $room["difficulty"],
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