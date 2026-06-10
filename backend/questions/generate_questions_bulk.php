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

$topic = trim($data["topic"] ?? "");
$category = trim($data["category"] ?? "");
$quantity = (int)($data["quantity"] ?? 5);
$difficulty_level = (float)($data["difficulty_level"] ?? 1.0);
$language = trim($data["language"] ?? "es");

if ($quantity < 1) $quantity = 1;
if ($quantity > 20) $quantity = 20;

if ($difficulty_level < 1.0) {
    $difficulty_level = 1.0;
}

if ($difficulty_level > 5.0) {
    $difficulty_level = 5.0;
}

$difficulty_level = round($difficulty_level, 1);

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

$category = normalize_question_category($category, $language);
$topicFocus = $topic !== "" ? $topic : $category;
$requestedCategory = $category;
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
Content focus: {$topicFocus}.
Use this category exactly for every question: {$category}.

Difficulty level: {$difficulty_level} out of 5.
Language: {$language}. {$langInstruction}

Difficulty guide:
- 1.0 to 1.9 = basic concepts and simple prevention.
- 2.0 to 2.9 = intermediate understanding.
- 3.0 to 3.9 = applied reasoning.
- 4.0 to 5.0 = advanced clinical/public health reasoning.

Rules:
- Each question must be educational and medically safe.
- Suitable for university students.
- Avoid personalized medical advice or diagnosis.
- Each question must have exactly four options.
- Only one option must be correct.
- correct_option must be A, B, C, or D.
- explanation must briefly explain why the correct answer is correct.
- category must be exactly one of: {$allowedCategories}.
- difficulty_level must be a number between 1.0 and 5.0.
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

$sql = "INSERT INTO questions 
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
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'ai', 0)";

$stmt = $conn->prepare($sql);

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
    $option_a = trim($q["option_a"] ?? "");
    $option_b = trim($q["option_b"] ?? "");
    $option_c = trim($q["option_c"] ?? "");
    $option_d = trim($q["option_d"] ?? "");
    $correct_option = strtoupper(trim($q["correct_option"] ?? ""));
    $explanation = trim($q["explanation"] ?? "");
    $category = normalize_question_category(trim($q["category"] ?? $requestedCategory), $language);
    $qDifficultyLevel = (float)($q["difficulty_level"] ?? $difficulty_level);
    $qLanguage = trim($q["language"] ?? $language);

    if (
        $question === "" ||
        $option_a === "" ||
        $option_b === "" ||
        $option_c === "" ||
        $option_d === "" ||
        $explanation === "" ||
        !in_array($correct_option, ["A", "B", "C", "D"], true)
    ) {
        $skipped++;
        continue;
    }

    if ($qDifficultyLevel < 1.0) {
        $qDifficultyLevel = 1.0;
    }

    if ($qDifficultyLevel > 5.0) {
        $qDifficultyLevel = 5.0;
    }

    $qDifficultyLevel = round($qDifficultyLevel, 1);

    if (!in_array($qLanguage, ["es", "en"], true)) {
        $qLanguage = $language;
    }

    $stmt->bind_param(
        "sssssssdss",
        $question,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $correct_option,
        $explanation,
        $category,
        $qDifficultyLevel,
        $qLanguage
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
    "message" => "Generación masiva finalizada",
    "inserted" => $inserted,
    "skipped" => $skipped
], JSON_UNESCAPED_UNICODE);
?>
