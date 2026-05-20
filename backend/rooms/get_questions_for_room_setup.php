<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$difficulty = $_GET["difficulty"] ?? "easy";
$lang = $_GET["lang"] ?? "es";

if (!in_array($difficulty, ["easy", "medium", "hard"], true)) {
    $difficulty = "easy";
}

if (!in_array($lang, ["es", "en"], true)) {
    $lang = "es";
}

$sql = "SELECT id, question, category, difficulty, language
        FROM questions
        WHERE difficulty = ? AND language = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("ss", $difficulty, $lang);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = [
        "id" => (int)$row["id"],
        "question" => $row["question"],
        "category" => $row["category"],
        "difficulty" => $row["difficulty"],
        "language" => $row["language"]
    ];
}

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>