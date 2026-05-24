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
            is_active
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified', 'csv', 1)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
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
    $difficulty_level = (float)($row[$headerIndexes["difficulty_level"]] ?? 1.0);
    $language = trim($row[$headerIndexes["language"]] ?? "es");

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
        $difficulty_level,
        $language
    );

    if ($stmt->execute()) {
        $inserted++;
    } else {
        $skipped++;
        $errors[] = $stmt->error;
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
    "errors" => $errors
], JSON_UNESCAPED_UNICODE);
?>