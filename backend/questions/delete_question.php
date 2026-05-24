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

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID no válido"
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