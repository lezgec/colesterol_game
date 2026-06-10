<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/gemini.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$language = trim($data["language"] ?? "es");
$category = trim($data["category"] ?? "");
$difficulty = (int)($data["difficulty"] ?? 1);
$quantity = (int)($data["quantity"] ?? 0);

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

$category = normalize_question_category($category, $language);

if ($difficulty < 1) {
    $difficulty = 1;
}

if ($difficulty > 5) {
    $difficulty = 5;
}

if ($quantity < 1) {
    echo json_encode([
        "success" => false,
        "message" => "Cantidad inválida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($quantity > 50) {
    $quantity = 50;
}

$difficultyLevel = (float)$difficulty;
$allowedCategoryList = array_values(array_unique(array_merge(
    question_categories($language),
    [$category]
)));
$allowedCategories = implode(", ", $allowedCategoryList);

$langInstruction = $language === "en"
    ? "Generate all questions in English."
    : "Genera todas las preguntas en español.";

$prompt = "
You are an educational content generator for a serious game about high cholesterol and cardiovascular prevention.

Create exactly {$quantity} multiple-choice questions.
Topic: {$category} in the context of cholesterol, cardiovascular risk, prevention, lifestyle, treatment, and public health.
Use this category exactly for every question: {$category}.

Difficulty level: {$difficultyLevel} out of 5.
Language: {$language}. {$langInstruction}

Difficulty guide:
- 1 = basic concepts and simple prevention.
- 2 = intermediate understanding.
- 3 = applied reasoning.
- 4 = advanced reasoning.
- 5 = advanced clinical/public health reasoning.

Rules:
- Each question must be educational and medically safe.
- Suitable for university students.
- Avoid personalized medical advice or diagnosis.
- Each question must have exactly four options.
- Only one option must be correct.
- correct_option must be A, B, C, or D.
- explanation must briefly explain why the correct answer is correct.
- category must be exactly one of: {$allowedCategories}.
- difficulty_level must be {$difficultyLevel}.
- language must be {$language}.
- Return only valid JSON.
";

$url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "responseMimeType" => "application/json",
        "responseSchema" => [
            "type" => "object",
            "properties" => [
                "questions" => [
                    "type" => "array",
                    "items" => [
                        "type" => "object",
                        "properties" => [
                            "question" => ["type" => "string"],
                            "option_a" => ["type" => "string"],
                            "option_b" => ["type" => "string"],
                            "option_c" => ["type" => "string"],
                            "option_d" => ["type" => "string"],
                            "correct_option" => [
                                "type" => "string",
                                "enum" => ["A", "B", "C", "D"]
                            ],
                            "explanation" => ["type" => "string"],
                            "category" => ["type" => "string"],
                            "difficulty_level" => ["type" => "number"],
                            "language" => [
                                "type" => "string",
                                "enum" => ["es", "en"]
                            ]
                        ],
                        "required" => [
                            "question",
                            "option_a",
                            "option_b",
                            "option_c",
                            "option_d",
                            "correct_option",
                            "explanation",
                            "category",
                            "difficulty_level",
                            "language"
                        ]
                    ]
                ]
            ],
            "required" => ["questions"]
        ]
    ]
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($curlError) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión con Gemini",
        "error" => $curlError
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    echo json_encode([
        "success" => false,
        "message" => "Gemini devolvió un error",
        "status" => $httpCode,
        "response" => json_decode($response, true)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = json_decode($response, true);
$text = $result["candidates"][0]["content"]["parts"][0]["text"] ?? "";
$generated = json_decode($text, true);

if (!$generated || !isset($generated["questions"]) || !is_array($generated["questions"])) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo interpretar la respuesta de Gemini",
        "raw" => $text
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO questions
        (
            question,
            option_a,
            option_b,
            option_c,
            option_d,
            correct_option,
            explanation,
            category,
            difficulty_level,
            language,
            status,
            origin,
            is_active
        )
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified', 'ai', 1)
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar insert",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$inserted = 0;
$skipped = 0;

foreach ($generated["questions"] as $q) {
    $question = trim($q["question"] ?? "");
    $optionA = trim($q["option_a"] ?? "");
    $optionB = trim($q["option_b"] ?? "");
    $optionC = trim($q["option_c"] ?? "");
    $optionD = trim($q["option_d"] ?? "");
    $correctOption = strtoupper(trim($q["correct_option"] ?? ""));
    $explanation = trim($q["explanation"] ?? "");
    $questionCategory = $category;
    $questionDifficulty = $difficultyLevel;
    $questionLanguage = in_array(($q["language"] ?? $language), ["es", "en"], true)
        ? $q["language"]
        : $language;

    if (
        $question === "" ||
        $optionA === "" ||
        $optionB === "" ||
        $optionC === "" ||
        $optionD === "" ||
        $explanation === "" ||
        !in_array($correctOption, ["A", "B", "C", "D"], true)
    ) {
        $skipped++;
        continue;
    }

    $stmt->bind_param(
        "ssssssssds",
        $question,
        $optionA,
        $optionB,
        $optionC,
        $optionD,
        $correctOption,
        $explanation,
        $questionCategory,
        $questionDifficulty,
        $questionLanguage
    );

    if ($stmt->execute()) {
        $inserted++;
    } else {
        $skipped++;
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Preguntas generadas correctamente",
    "inserted" => $inserted,
    "skipped" => $skipped
], JSON_UNESCAPED_UNICODE);
?>
