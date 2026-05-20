<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
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
$difficulty = trim($data["difficulty"] ?? "easy");
$language = trim($data["language"] ?? "es");

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID no válido"]);
    exit;
}

if (
    $question === "" || $option_a === "" || $option_b === "" ||
    $option_c === "" || $option_d === "" || $explanation === "" ||
    $category === ""
) {
    echo json_encode(["success" => false, "message" => "Campos obligatorios incompletos"]);
    exit;
}

if (!in_array($correct_option, ["A", "B", "C", "D"], true)) {
    echo json_encode(["success" => false, "message" => "Respuesta correcta no válida"]);
    exit;
}

if (!in_array($difficulty, ["easy", "medium", "hard"], true)) {
    echo json_encode(["success" => false, "message" => "Dificultad no válida"]);
    exit;
}

if (!in_array($language, ["es", "en"], true)) {
    echo json_encode(["success" => false, "message" => "Idioma no válido"]);
    exit;
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
difficulty = ?,
language = ?
WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Error al preparar consulta", "error" => $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssssssssssi",
    $question,
    $option_a,
    $option_b,
    $option_c,
    $option_d,
    $correct_option,
    $explanation,
    $category,
    $difficulty,
    $language,
    $id
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Pregunta actualizada correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar pregunta", "error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>