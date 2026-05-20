<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/gemini.php';

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$topic = trim($data["topic"] ?? "");
$quantity = (int)($data["quantity"] ?? 5);
$difficulty = trim($data["difficulty"] ?? "easy");
$language = trim($data["language"] ?? "es");

if ($topic === "") {
    echo json_encode([
        "success" => false,
        "message" => "El tema es obligatorio"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($quantity < 1) $quantity = 1;
if ($quantity > 20) $quantity = 20;

if (!in_array($difficulty, ["easy", "medium", "hard"], true)) {
    $difficulty = "easy";
}

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

$langInstruction = $language === "en"
    ? "Generate all questions in English."
    : "Genera todas las preguntas en español.";

$prompt = "
You are an educational content generator for a serious game about high cholesterol.

Create exactly {$quantity} multiple-choice questions about: {$topic}.
Difficulty: {$difficulty}.
Language: {$language}. {$langInstruction}

Rules:
- Each question must be educational and medically safe.
- Suitable for university students.
- Each question must have exactly four options.
- Only one option must be correct.
- correct_option must be A, B, C, or D.
- explanation must briefly explain why the correct answer is correct.
- category must be short.
- difficulty must be {$difficulty}.
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
(question, option_a, option_b, option_c, option_d, correct_option, explanation, category, difficulty, language)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
    $category = trim($q["category"] ?? $topic);
    $qDifficulty = trim($q["difficulty"] ?? $difficulty);
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

    if (!in_array($qDifficulty, ["easy", "medium", "hard"], true)) {
        $qDifficulty = $difficulty;
    }

    if (!in_array($qLanguage, ["es", "en"], true)) {
        $qLanguage = $language;
    }

    $stmt->bind_param(
        "ssssssssss",
        $question,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $correct_option,
        $explanation,
        $category,
        $qDifficulty,
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