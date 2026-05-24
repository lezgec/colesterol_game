<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
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
$difficulty_level = (float)($data["difficulty_level"] ?? 1.0);
$language = trim($data["language"] ?? "es");

if ($topic === "") {
    echo json_encode([
        "success" => false,
        "message" => "El tema es obligatorio"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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

$langInstruction = $language === "en"
    ? "Generate the question in English."
    : "Genera la pregunta en español.";

$prompt = "
You are an educational content generator for a serious game about high cholesterol and cardiovascular prevention.

Create ONE multiple-choice question about this topic: {$topic}.

Difficulty level: {$difficulty_level} out of 5.
Language: {$language}. {$langInstruction}

Difficulty guide:
- 1.0 to 1.9 = basic concepts and simple prevention.
- 2.0 to 2.9 = intermediate understanding.
- 3.0 to 3.9 = applied reasoning.
- 4.0 to 5.0 = advanced clinical/public health reasoning.

Rules:
- The question must be educational and medically safe.
- It must be suitable for university students.
- It must avoid personalized medical advice or diagnosis.
- It must have exactly four options.
- Only one option must be correct.
- correct_option must be A, B, C, or D.
- explanation must briefly explain why the correct answer is correct.
- category must be short.
- difficulty_level must be a number between 1.0 and 5.0.
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

$generatedDifficulty = (float)($generated["difficulty_level"] ?? $difficulty_level);

if ($generatedDifficulty < 1.0) {
    $generatedDifficulty = 1.0;
}

if ($generatedDifficulty > 5.0) {
    $generatedDifficulty = 5.0;
}

$generated["difficulty_level"] = round($generatedDifficulty, 1);
$generated["language"] = in_array(($generated["language"] ?? $language), ["es", "en"], true)
    ? $generated["language"]
    : $language;

$generated["status"] = "pending";
$generated["origin"] = "ai";
$generated["is_active"] = 0;
$generated["success"] = true;

echo json_encode($generated, JSON_UNESCAPED_UNICODE);
?>