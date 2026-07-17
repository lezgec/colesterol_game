<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/gemini.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limit.php';
require_once __DIR__ . '/question_option_helpers.php';

require_json_role(["teacher", "super_admin"]);

require_csrf_token();

require_rate_limit($conn, "gemini-single:" . current_user_id(), 12, 900);

$data = json_decode(file_get_contents("php://input"), true);

$topic = trim($data["topic"] ?? "");
$category = trim($data["category"] ?? "");
$difficulty_level = (int)round((float)($data["difficulty_level"] ?? 1));
$language = trim($data["language"] ?? "es");

if ($difficulty_level < 1) {
    $difficulty_level = 1;
}

if ($difficulty_level > 5) {
    $difficulty_level = 5;
}

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

$category = normalize_question_category($category, $language);
$topicFocus = $topic !== "" ? $topic : $category;
$allowedCategoryList = array_values(array_unique(array_merge(
    question_categories($language),
    [$category]
)));
$allowedCategories = implode(", ", $allowedCategoryList);

$langInstruction = $language === "en"
    ? "Generate the question in English."
    : "Genera la pregunta en español.";

$prompt = "
You are an educational content generator for a serious game about high cholesterol and cardiovascular prevention.

Create ONE multiple-choice question.
Content focus: {$topicFocus}.
Use this category exactly: {$category}.

Difficulty level: {$difficulty_level} out of 5.
Language: {$language}. {$langInstruction}

Difficulty guide:
- 1 = basic concepts and simple prevention.
- 2 = intermediate understanding.
- 3 = applied reasoning.
- 4 = advanced reasoning.
- 5 = advanced clinical/public health reasoning.

Rules:
- The question must be educational and medically safe.
- It must be suitable for university students.
- It must avoid personalized medical advice or diagnosis.
- It must have exactly four options.
- Only one option must be correct.
- correct_option must be A, B, C, or D.
- Avoid always placing the correct answer in A. Use a varied correct_option position.
- Avoid acronyms or abbreviations in the question and answer options whenever possible.
- Do not write standalone abbreviations such as LDL, HDL, VLDL, TG, IMC, BMI, ECV, or CVD.
- If an abbreviation is truly necessary, write the full meaning first and the abbreviation in parentheses in the same sentence, using the selected language. Example in Spanish: lipoproteína de baja densidad (LDL). Example in English: low-density lipoprotein (LDL).
- Do not use an abbreviation later by itself unless its full meaning appeared earlier in that same question or option.
- explanation must briefly explain why the correct answer is correct.
- category must be exactly one of: {$allowedCategories}.
- difficulty_level must be the integer {$difficulty_level}.
- language must be es or en.
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
                "difficulty_level" => ["type" => "integer"],
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

if (!$generated) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo interpretar la respuesta de Gemini",
        "raw" => $text
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$generatedDifficulty = (int)round((float)($generated["difficulty_level"] ?? $difficulty_level));

if ($generatedDifficulty < 1) {
    $generatedDifficulty = 1;
}

if ($generatedDifficulty > 5) {
    $generatedDifficulty = 5;
}

$generated["difficulty_level"] = $generatedDifficulty;
$generated["language"] = in_array(($generated["language"] ?? $language), ["es", "en"], true)
    ? $generated["language"]
    : $language;
$generated["category"] = $category;
$generated = normalize_correct_option_position($generated);

$generated["status"] = "pending";
$generated["origin"] = "ai";
$generated["is_active"] = 0;
$generated["success"] = true;

echo json_encode($generated, JSON_UNESCAPED_UNICODE);
?>
