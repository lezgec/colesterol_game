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
        "correct" => $optionPayload["correct"],
        "correct_option" => $optionPayload["correct_option"],
        "display_correct_option" => $optionPayload["display_correct_option"],
        "category" => $row["category"],
        "difficulty_level" => $difficultyLevel,
        "language" => $row["language"]
    ];
}

if (count($questions) === 0) {
    $otherLang = $lang === "es" ? "en" : "es";
    $otherLangLabel = $otherLang === "es" ? "Español" : "English";
    $currentLangLabel = $lang === "es" ? "Español" : "English";
    $otherLanguageTotal = 0;

    $availabilityStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM questions
        WHERE
            language = ?
            AND status = 'verified'
            AND is_active = 1
            AND visibility = 'global'
            AND global_request_status = 'approved'
    ");

    if ($availabilityStmt) {
        $availabilityStmt->bind_param("s", $otherLang);
        $availabilityStmt->execute();
        $availabilityResult = $availabilityStmt->get_result();
        $availabilityRow = $availabilityResult->fetch_assoc();
        $otherLanguageTotal = (int)($availabilityRow["total"] ?? 0);
        $availabilityStmt->close();
    }

    if ($otherLanguageTotal > 0) {
        $message = $lang === "es"
            ? "No hay preguntas disponibles en español por ahora."
            : "There are no questions available in English right now.";

        $action = $lang === "es"
            ? "Puedes cambiar el idioma a {$otherLangLabel} para jugar con las preguntas disponibles o contactar al administrador para que active preguntas en español."
            : "You can switch the language to {$otherLangLabel} to play with the available questions or contact the administrator to enable English questions.";
    } else {
        $message = $lang === "es"
            ? "Todavía no hay preguntas activas para jugar."
            : "There are no active questions to play yet.";

        $action = $lang === "es"
            ? "Contacta al administrador para que revise y active preguntas en el banco global."
            : "Contact the administrator to review and activate questions in the global bank.";
    }

    echo json_encode([
        "success" => false,
        "code" => "no_questions_for_language",
        "message" => $message,
        "action" => $action,
        "language" => $lang,
        "language_label" => $currentLangLabel,
        "available_alternative_language" => $otherLanguageTotal > 0 ? $otherLang : null,
        "available_alternative_language_label" => $otherLanguageTotal > 0 ? $otherLangLabel : null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
