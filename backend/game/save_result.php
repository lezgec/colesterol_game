<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php'; 

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario no autenticado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "No llegaron datos JSON válidos",
        "raw_input" => $input
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$score = isset($data["score"]) ? (int)$data["score"] : 0;
$correct = isset($data["correct_answers"]) ? (int)$data["correct_answers"] : 0;
$total = isset($data["total_questions"]) ? (int)$data["total_questions"] : 0;
$lives = isset($data["lives_remaining"]) ? (int)$data["lives_remaining"] : 0;
$difficulty = isset($data["difficulty"]) ? trim($data["difficulty"]) : "easy";

$allowedDifficulties = ["easy", "medium", "hard"];
if (!in_array($difficulty, $allowedDifficulties, true)) {
    $difficulty = "easy";
}

$sql = "INSERT INTO game_results (user_id, score, correct_answers, total_questions, lives_remaining, difficulty)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error en prepare",
        "details" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("iiiiis", $user_id, $score, $correct, $total, $lives, $difficulty);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Resultado guardado correctamente"
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al ejecutar INSERT",
        "details" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>