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
    ]);
    exit;
}

$data = [];

$queries = [
    "total_users" => "SELECT COUNT(*) AS total FROM users",
    "total_questions" => "SELECT COUNT(*) AS total FROM questions",
    "total_games" => "SELECT COUNT(*) AS total FROM game_results",
    "total_rooms" => "SELECT COUNT(*) AS total FROM game_rooms",
    "active_rooms" => "SELECT COUNT(*) AS total FROM game_rooms WHERE status = 'waiting'",
    "started_rooms" => "SELECT COUNT(*) AS total FROM game_rooms WHERE status = 'started'"
];

foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $data[$key] = (int)$row["total"];
}

$avgSql = "SELECT COALESCE(AVG(score), 0) AS avg_score FROM game_results";
$avgResult = $conn->query($avgSql);
$avgRow = $avgResult->fetch_assoc();

$data["avg_score"] = round((float)$avgRow["avg_score"], 2);

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>