<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$difficulty = $_GET['difficulty'] ?? 'easy';
$lang = $_GET['lang'] ?? 'es';

$allowedDifficulties = ['easy', 'medium', 'hard'];
$allowedLangs = ['es', 'en'];

if (!in_array($difficulty, $allowedDifficulties, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Dificultad no válida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($lang, $allowedLangs, true)) {
    echo json_encode([
        "success" => false,
        "message" => "Idioma no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT id, question, option_a, option_b, option_c, option_d, correct_option, explanation, category, difficulty, language
        FROM questions
        WHERE difficulty = ? AND language = ?
        ORDER BY RAND()
        LIMIT 10";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("ss", $difficulty, $lang);

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

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>