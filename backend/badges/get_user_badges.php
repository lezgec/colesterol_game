<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/badge_translations.php';

if (!current_session_is_active()) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$userId = is_super_admin() && $requestedUserId > 0
    ? $requestedUserId
    : (int)$_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT
        badge_key,
        badge_name,
        badge_description,
        badge_icon,
        earned_at
    FROM user_badges
    WHERE user_id = ?
    ORDER BY earned_at DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$badges = [];

while ($row = $result->fetch_assoc()) {
    $badges[] = translate_badge_payload([
        "badge_key" => $row["badge_key"],
        "badge_name" => $row["badge_name"],
        "badge_description" => $row["badge_description"],
        "badge_icon" => $row["badge_icon"],
        "earned_at" => $row["earned_at"]
    ]);
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "badges" => $badges
], JSON_UNESCAPED_UNICODE);
?>
