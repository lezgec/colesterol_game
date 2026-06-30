<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/question_workflow_helpers.php';

require_csrf_token();

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ensure_question_workflow_columns($conn)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo preparar el flujo de preguntas",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES["file"])) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió ningún archivo"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES["file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Error al subir el archivo"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

if ($extension !== "csv") {
    echo json_encode([
        "success" => false,
        "message" => "El archivo debe ser CSV"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$handle = fopen($file["tmp_name"], "r");

if (!$handle) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo leer el archivo CSV"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$requiredHeaders = [
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
];

$optionalHeaders = [
    "status",
    "origin",
    "is_active"
];

$headers = fgetcsv($handle);

if (!$headers) {
    echo json_encode([
        "success" => false,
        "message" => "El CSV está vacío"
    ], JSON_UNESCAPED_UNICODE);
    fclose($handle);
    exit;
}

$headers = array_map(function ($header) {
    $header = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header);
    return strtolower(trim($header));
}, $headers);

$missingHeaders = array_diff($requiredHeaders, $headers);

if (!empty($missingHeaders)) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan columnas requeridas: " . implode(", ", $missingHeaders)
    ], JSON_UNESCAPED_UNICODE);
    fclose($handle);
    exit;
}

$headerIndexes = array_flip($headers);

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
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    fclose($handle);
    exit;
}

$inserted = 0;
$skipped = 0;
$errors = [];

while (($row = fgetcsv($handle)) !== false) {
    $question = trim($row[$headerIndexes["question"]] ?? "");
    $option_a = trim($row[$headerIndexes["option_a"]] ?? "");
    $option_b = trim($row[$headerIndexes["option_b"]] ?? "");
    $option_c = trim($row[$headerIndexes["option_c"]] ?? "");
    $option_d = trim($row[$headerIndexes["option_d"]] ?? "");
    $correct_option = strtoupper(trim($row[$headerIndexes["correct_option"]] ?? ""));
    $explanation = trim($row[$headerIndexes["explanation"]] ?? "");
    $category = trim($row[$headerIndexes["category"]] ?? "");
    $difficulty_level = (float)($row[$headerIndexes["difficulty_level"]] ?? 1);
    $language = trim($row[$headerIndexes["language"]] ?? "es");
    $status = isset($headerIndexes["status"])
        ? strtolower(trim($row[$headerIndexes["status"]] ?? "verified"))
        : "verified";
    $origin = isset($headerIndexes["origin"])
        ? strtolower(trim($row[$headerIndexes["origin"]] ?? "csv"))
        : "csv";
    $is_active = isset($headerIndexes["is_active"])
        ? (int)($row[$headerIndexes["is_active"]] ?? 1)
        : 1;

    if (
        $question === "" ||
        $option_a === "" ||
        $option_b === "" ||
        $option_c === "" ||
        $option_d === "" ||
        $explanation === "" ||
        $category === ""
    ) {
        $skipped++;
        continue;
    }

    if (!in_array($correct_option, ["A", "B", "C", "D"], true)) {
        $skipped++;
        $errors[] = "Fila omitida: respuesta correcta no válida.";
        continue;
    }

    if ($difficulty_level < 1) {
        $difficulty_level = 1;
    }

    if ($difficulty_level > 5) {
        $difficulty_level = 5;
    }

    if (!in_array($language, ["es", "en"], true)) {
        $language = "es";
    }

    if (!in_array($status, ["pending", "verified", "rejected"], true)) {
        $status = "verified";
    }

    if ($origin === "ai_generated") {
        $origin = "ai";
    }

    if (!in_array($origin, ["manual", "ai", "csv"], true)) {
        $origin = "csv";
    }

    $is_active = $is_active === 1 ? 1 : 0;

    if ($is_active === 1 && $status !== "verified") {
        $is_active = 0;
    }

    $category = normalize_question_category($category, $language);
    $workflow = question_workflow_for_create(["question_scope" => is_super_admin() ? "global" : "private"], $status, $is_active);
    $createdBy = current_user_id() ?: null;
    $visibility = $workflow["visibility"];
    $globalRequestStatus = $workflow["global_request_status"];
    $status = $workflow["status"];
    $is_active = $workflow["is_active"];

    if ($is_active === 1 && $status !== "verified") {
        $is_active = 0;
    }

    $globalRequestedAt = $workflow["global_requested_at"];

    $stmt->bind_param(
        "ssssssssdsssiisss",
        $question,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $correct_option,
        $explanation,
        $category,
        $difficulty_level,
        $language,
        $status,
        $origin,
        $is_active,
        $createdBy,
        $visibility,
        $globalRequestStatus,
        $globalRequestedAt
    );

    if ($stmt->execute()) {
        $inserted++;
    } else {
        $skipped++;
        if (env_bool("APP_DEBUG", false)) {
            $errors[] = $stmt->error;
        }
    }
}

fclose($handle);
$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Importación finalizada. Insertadas: $inserted. Omitidas: $skipped.",
    "inserted" => $inserted,
    "skipped" => $skipped,
    "errors" => env_bool("APP_DEBUG", false) ? $errors : []
], JSON_UNESCAPED_UNICODE);
?>
