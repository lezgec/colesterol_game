<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/question_workflow_helpers.php';

require_csrf_token();

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)($data["id"] ?? 0);

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
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID no válido"
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

$stmtOwner = $conn->prepare("
    SELECT created_by_user_id, visibility, global_request_status
    FROM questions
    WHERE id = ?
    LIMIT 1
");

if (!$stmtOwner) {
    echo json_encode([
        "success" => false,
        "message" => "Error al verificar permisos",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtOwner->bind_param("i", $id);
$stmtOwner->execute();
$existing = $stmtOwner->get_result()->fetch_assoc();
$stmtOwner->close();

if (!$existing) {
    echo json_encode([
        "success" => false,
        "message" => "Pregunta no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_super_admin() && (int)($existing["created_by_user_id"] ?? 0) !== current_user_id()) {
    echo json_encode([
        "success" => false,
        "message" => "Solo puedes editar tus propias preguntas"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$visibility = $existing["visibility"] ?: "global";
$globalRequestStatus = $existing["global_request_status"] ?: "approved";
$globalRequestedAt = null;
$globalReviewedBy = null;
$globalReviewedAt = null;

if (is_super_admin()) {
    $requestedVisibility = $data["visibility"] ?? $visibility;
    $requestedGlobalRequestStatus = $data["global_request_status"] ?? $globalRequestStatus;

    $visibility = in_array($requestedVisibility, ["private", "global"], true)
        ? $requestedVisibility
        : $visibility;
    $globalRequestStatus = in_array($requestedGlobalRequestStatus, ["none", "pending", "approved", "rejected"], true)
        ? $requestedGlobalRequestStatus
        : $globalRequestStatus;

    if ($visibility === "global" && $status === "verified" && $is_active === 1) {
        $globalRequestStatus = "approved";
        $globalReviewedBy = current_user_id();
        $globalReviewedAt = date("Y-m-d H:i:s");
    } elseif ($status === "rejected") {
        $globalRequestStatus = "rejected";
        $is_active = 0;
        $globalReviewedBy = current_user_id();
        $globalReviewedAt = date("Y-m-d H:i:s");
    }
} else {
    $scope = requested_question_scope($data);

    if ($scope === "global_request") {
        $visibility = "global";
        $globalRequestStatus = "pending";
        $status = "pending";
        $is_active = 0;
        $globalRequestedAt = date("Y-m-d H:i:s");
    } else {
        $visibility = "private";
        $globalRequestStatus = "none";

        if (!in_array($status, ["pending", "verified", "rejected"], true)) {
            $status = "pending";
        }

        if ($status !== "verified") {
            $is_active = 0;
        }
    }
}

if ($is_active === 1 && $status !== "verified") {
    $is_active = 0;
}

$sql = "UPDATE questions SET
            question = ?,
            option_a = ?,
            option_b = ?,
            option_c = ?,
            option_d = ?,
            correct_option = ?,
            explanation = ?,
            category = ?,
            difficulty_level = ?,
            language = ?,
            status = ?,
            origin = ?,
            is_active = ?,
            visibility = ?,
            global_request_status = ?,
            global_requested_at = COALESCE(?, global_requested_at),
            global_reviewed_by = COALESCE(?, global_reviewed_by),
            global_reviewed_at = COALESCE(?, global_reviewed_at)
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param(
    "ssssssssisssisssisi",
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
    $visibility,
    $globalRequestStatus,
    $globalRequestedAt,
    $globalReviewedBy,
    $globalReviewedAt,
    $id
);

if ($stmt->execute()) {
    if (!is_super_admin() && $globalRequestStatus === "pending") {
        notify_super_admins_about_global_question_request(
            $conn,
            $id,
            $_SESSION["user_name"] ?? "Docente",
            $question
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Pregunta actualizada correctamente",
        "status" => $status,
        "is_active" => $is_active,
        "visibility" => $visibility,
        "global_request_status" => $globalRequestStatus
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar pregunta",
        "error" => app_error_detail($stmt->error)
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>
