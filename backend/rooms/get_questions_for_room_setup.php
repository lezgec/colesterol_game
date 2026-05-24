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

$lang = $_GET["lang"] ?? "es";

if (!in_array($lang, ["es", "en"], true)) {
    $lang = "es";
}

$sql = "SELECT 
            id, 
            question, 
            category, 
            difficulty_level, 
            language,
            status,
            origin,
            is_active
        FROM questions
        WHERE 
            language = ?
            AND status = 'verified'
            AND is_active = 1
        ORDER BY difficulty_level ASC, id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $lang);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = [
        "id" => (int)$row["id"],
        "question" => $row["question"],
        "category" => $row["category"],
        "difficulty_level" => (float)$row["difficulty_level"],
        "language" => $row["language"],
        "status" => $row["status"],
        "origin" => $row["origin"],
        "is_active" => (int)$row["is_active"]
    ];
}

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>