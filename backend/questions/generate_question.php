<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no autenticado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$topic = trim($data["topic"] ?? "");
$difficulty = trim($data["difficulty"] ?? "easy");
$language = trim($data["language"] ?? "es");

if ($topic === "") {
    echo json_encode([
        "success" => false,
        "message" => "El tema es obligatorio"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($difficulty, ["easy", "medium", "hard"], true)) {
    $difficulty = "easy";
}

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

$langInstruction = $language === "en"
    ? "Generate the question in English."
    : "Genera la pregunta en español.";

$prompt = "
You are an educational content generator for a serious game about high cholesterol.
Create ONE multiple-choice question about this topic: {$topic}.
Difficulty: {$difficulty}.
Language: {$language}. {$langInstruction}

Rules:
- The question must be educational and medically safe.
- It must be suitable for university students.
- It must have exactly four options.
- Only one option must be correct.
- correct_option must be A, B, C, or D.
- explanation must briefly explain why the correct answer is correct.
- category must be short.
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
                "difficulty" => [
                    "type" => "string",
                    "enum" => ["easy", "medium", "hard"]
                ],
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
                "difficulty",
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

$generated["success"] = true;

echo json_encode($generated, JSON_UNESCAPED_UNICODE);
?>