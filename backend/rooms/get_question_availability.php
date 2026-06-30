<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/question_categories.php';
require_once __DIR__ . '/../questions/question_workflow_helpers.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$language = $_GET["lang"] ?? "es";
$category = trim($_GET["category"] ?? "");
$difficulty = (int)($_GET["difficulty"] ?? 1);

if (!in_array($language, ["es", "en"], true)) {
    $language = "es";
}

$category = normalize_question_category($category, $language);

if ($difficulty < 1) {
    $difficulty = 1;
}

if ($difficulty > 5) {
    $difficulty = 5;
}

$minDifficulty = (float)$difficulty;
$maxDifficulty = $difficulty >= 5 ? 5.1 : (float)($difficulty + 1);
ensure_question_workflow_columns($conn);
$accessSql = playable_question_access_sql("");

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM questions
    WHERE language = ?
      AND category = ?
      AND difficulty_level >= ?
      AND difficulty_level < ?
      AND status = 'verified'
      AND is_active = 1
      AND {$accessSql}
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param(
    "ssdd",
    $language,
    $category,
    $minDifficulty,
    $maxDifficulty
);

$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "success" => true,
    "language" => $language,
    "category" => $category,
    "difficulty" => $difficulty,
    "available" => (int)($row["total"] ?? 0)
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
