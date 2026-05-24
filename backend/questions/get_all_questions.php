<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$sql = "SELECT 
            id, 
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
        FROM questions
        ORDER BY id DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener preguntas",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $row["id"] = (int)$row["id"];
    $row["difficulty_level"] = (float)$row["difficulty_level"];
    $row["is_active"] = (int)$row["is_active"];

    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$conn->close();
?>