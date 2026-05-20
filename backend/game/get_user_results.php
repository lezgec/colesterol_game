<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no autenticado"
    ]);
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT score, correct_answers, total_questions, lives_remaining, difficulty, played_at 
        FROM game_results 
        WHERE user_id = ? 
        ORDER BY played_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>