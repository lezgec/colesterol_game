<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/gemini.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/question_option_helpers.php';
require_once __DIR__ . '/question_workflow_helpers.php';

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
$difficulty_level = (int)round((float)($data["difficulty_level"] ?? 1));
$language = trim($data["language"] ?? "es");

if (!ensure_question_workflow_columns($conn)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo preparar el flujo de preguntas",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($quantity < 1) $quantity = 1;
if ($quantity > 20) $quantity = 20;

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
- Distribute correct_option as evenly as possible across A, B, C, and D.
- Avoid acronyms or abbreviations in the questions and answer options whenever possible.
- Do not write standalone abbreviations such as LDL, HDL, VLDL, TG, IMC, BMI, ECV, or CVD.
- If an abbreviation is truly necessary, write the full meaning first and the abbreviation in parentheses in the same sentence, using the selected language. Example in Spanish: lipoproteína de alta densidad (HDL). Example in English: high-density lipoprotein (HDL).
- Do not use an abbreviation later by itself unless its full meaning appeared earlier in that same question or option.
- explanation must briefly explain why the correct answer is correct.
- category must be exactly one of: {$allowedCategories}.
- difficulty_level must be the integer {$difficulty_level}.
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

$workflow = question_workflow_for_create(
    array_merge($data, ["requires_review" => true]),
    "pending",
    0
);
$createdBy = current_user_id() ?: null;
$visibility = $workflow["visibility"];
$globalRequestStatus = $workflow["global_request_status"];
$insertStatus = $workflow["status"];
$insertActive = $workflow["is_active"];
$globalRequestedAt = $workflow["global_requested_at"];

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
            is_active,
            created_by_user_id,
            visibility,
            global_request_status,
            global_requested_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ai', ?, ?, ?, ?, ?)";

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
$generatedIds = [];
$targetLetters = question_option_letters();
$targetOffset = random_int(0, count($targetLetters) - 1);

foreach ($generated["questions"] as $index => $q) {
    $q = normalize_correct_option_position(
        $q,
        $targetLetters[($index + $targetOffset) % count($targetLetters)]
    );

    $question = trim($q["question"] ?? "");
    $option_a = trim($q["option_a"] ?? "");
    $option_b = trim($q["option_b"] ?? "");
    $option_c = trim($q["option_c"] ?? "");
    $option_d = trim($q["option_d"] ?? "");
    $correct_option = strtoupper(trim($q["correct_option"] ?? ""));
    $explanation = trim($q["explanation"] ?? "");
    $category = normalize_question_category(trim($q["category"] ?? $requestedCategory), $language);
    $qDifficultyLevel = (int)round((float)($q["difficulty_level"] ?? $difficulty_level));
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

    if ($qDifficultyLevel < 1) {
        $qDifficultyLevel = 1;
    }

    if ($qDifficultyLevel > 5) {
        $qDifficultyLevel = 5;
    }

    if (!in_array($qLanguage, ["es", "en"], true)) {
        $qLanguage = $language;
    }

    $stmt->bind_param(
        "ssssssssdssiisss",
        $question,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $correct_option,
        $explanation,
        $category,
        $qDifficultyLevel,
        $qLanguage,
        $insertStatus,
        $insertActive,
        $createdBy,
        $visibility,
        $globalRequestStatus,
        $globalRequestedAt
    );

    if ($stmt->execute()) {
        $inserted++;
        $generatedIds[] = (int)$stmt->insert_id;
        if ($globalRequestStatus === "pending" && $inserted === 1) {
            notify_super_admins_about_global_question_request(
                $conn,
                (int)$stmt->insert_id,
                $_SESSION["user_name"] ?? "Docente",
                $question
            );
        }
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
    "skipped" => $skipped,
    "generated_ids" => $generatedIds,
    "visibility" => $visibility,
    "global_request_status" => $globalRequestStatus
], JSON_UNESCAPED_UNICODE);
?>
