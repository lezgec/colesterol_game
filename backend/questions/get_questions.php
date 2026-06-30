<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/question_option_helpers.php';
require_once __DIR__ . '/question_workflow_helpers.php';

ensure_question_workflow_columns($conn);

$lang = $_GET['lang'] ?? 'es';
$target_difficulty = (int)round((float)($_GET['difficulty_level'] ?? 1));
$limit = (int)($_GET['limit'] ?? 10);

if (!in_array($lang, ['es', 'en'], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Idioma no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($target_difficulty < 1) {
    $target_difficulty = 1;
}

if ($target_difficulty > 5) {
    $target_difficulty = 5;
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
        AND visibility = 'global'
        AND global_request_status = 'approved'
    ORDER BY ABS(difficulty_level - ?) ASC, RAND()
    LIMIT ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar la consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("sdi", $lang, $target_difficulty, $limit);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Error al ejecutar la consulta",
        "error" => app_error_detail($stmt->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
        "category" => $row["category"],
        "difficulty_level" => $difficultyLevel,
        "language" => $row["language"]
    ];
}

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
