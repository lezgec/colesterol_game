<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/question_workflow_helpers.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

ensure_question_workflow_columns($conn);

$stmtOwner = $conn->prepare("
    SELECT created_by_user_id
    FROM questions
    WHERE id = ?
    LIMIT 1
");

if (!$stmtOwner) {
    echo json_encode([
        "success" => false,
        "message" => "Error al verificar permisos",
        "error" => $conn->error
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
        "message" => "Solo puedes desactivar tus propias preguntas"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "UPDATE questions 
        SET is_active = 0 
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Pregunta desactivada correctamente"
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al desactivar pregunta",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>
