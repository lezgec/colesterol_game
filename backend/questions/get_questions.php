<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$lang = $_GET['lang'] ?? 'es';
$target_difficulty = (float)($_GET['difficulty_level'] ?? 1.0);
$limit = (int)($_GET['limit'] ?? 10);

if (!in_array($lang, ['es', 'en'], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Idioma no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($target_difficulty < 1.0) {
    $target_difficulty = 1.0;
}

if ($target_difficulty > 5.0) {
    $target_difficulty = 5.0;
}

if ($limit < 1) {
    $limit = 10;
}

if ($limit > 50) {
    $limit = 50;
}

$sql = "
    SELECT 
        id, 
        question, 
        option_a, 
        option_b, 
        option_c, 
        option_d, 
        correct_option, 
        explanation, 
        category, 
        difficulty_level, 
        language
    FROM questions
    WHERE 
        language = ?
        AND status = 'verified'
        AND is_active = 1
    ORDER BY ABS(difficulty_level - ?) ASC, RAND()
    LIMIT ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("sdi", $lang, $target_difficulty, $limit);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Error al ejecutar la consulta",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $correctOption = strtoupper(trim($row["correct_option"]));
    $letters = ["A", "B", "C", "D"];
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
        "difficulty_level" => round($difficultyLevel, 1),
        "language" => $row["language"]
    ];
}

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>