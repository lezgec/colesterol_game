<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT
        badge_key,
        badge_name,
        badge_description,
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
    $badges[] = [
        "badge_key" => $row["badge_key"],
        "badge_name" => $row["badge_name"],
        "badge_description" => $row["badge_description"],
        "earned_at" => $row["earned_at"]
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "badges" => $badges
], JSON_UNESCAPED_UNICODE);
?>