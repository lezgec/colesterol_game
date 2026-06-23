<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/question_workflow_helpers.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$question = trim($data["question"] ?? "");
$option_a = trim($data["option_a"] ?? "");
$option_b = trim($data["option_b"] ?? "");
$option_c = trim($data["option_c"] ?? "");
$option_d = trim($data["option_d"] ?? "");
$correct_option = strtoupper(trim($data["correct_option"] ?? ""));
$explanation = trim($data["explanation"] ?? "");
$category = trim($data["category"] ?? "");
$difficulty_level = (int)round((float)($data["difficulty_level"] ?? 1));
$language = trim($data["language"] ?? "es");
$status = trim($data["status"] ?? "verified");
$origin = trim($data["origin"] ?? "manual");
$is_active = (int)($data["is_active"] ?? 1);

if (!ensure_question_workflow_columns($conn)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo preparar el flujo de preguntas",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    $question === "" ||
    $option_a === "" ||
    $option_b === "" ||
    $option_c === "" ||
    $option_d === "" ||
    $explanation === "" ||
    $category === ""
) {
    echo json_encode([
        "success" => false,
        "message" => "Campos obligatorios incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($correct_option, ["A", "B", "C", "D"], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Respuesta correcta no válida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($difficulty_level < 1) {
    $difficulty_level = 1;
}

if ($difficulty_level > 5) {
    $difficulty_level = 5;
}

if (!in_array($language, ["es", "en"], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Idioma no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$category = normalize_question_category($category, $language);

if (!in_array($status, ["pending", "verified", "rejected"], true)) {
    $status = "pending";
}

if (!in_array($origin, ["manual", "ai", "csv"], true)) {
    $origin = "manual";
}

$is_active = $is_active === 1 ? 1 : 0;

if ($is_active === 1 && $status !== "verified") {
    $is_active = 0;
}

$workflow = question_workflow_for_create($data, $status, $is_active);
$createdBy = current_user_id() ?: null;
$visibility = $workflow["visibility"];
$globalRequestStatus = $workflow["global_request_status"];
$status = $workflow["status"];
$is_active = $workflow["is_active"];

if ($is_active === 1 && $status !== "verified") {
    $is_active = 0;
}

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
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
    $questionId = $stmt->insert_id;

    if ($globalRequestStatus === "pending") {
        notify_super_admins_about_global_question_request(
            $conn,
            $questionId,
            $_SESSION["user_name"] ?? "Docente",
            $question
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Pregunta creada correctamente",
        "question_id" => $questionId,
        "global_request_status" => $globalRequestStatus,
        "visibility" => $visibility
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al crear pregunta",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>
