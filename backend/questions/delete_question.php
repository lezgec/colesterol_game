<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID no válido"]);
    exit;
}

$sql = "DELETE FROM questions WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Error al preparar consulta", "error" => $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Pregunta eliminada correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar pregunta", "error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>