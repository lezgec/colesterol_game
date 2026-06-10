<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../badges/evaluate_badges.php';

if (!is_logged_in()) {
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

$score = (int)($data["score"] ?? 0);
$correct = (int)($data["correct_answers"] ?? 0);
$total = (int)($data["total_questions"] ?? 0);
$lives = (int)($data["lives_remaining"] ?? 0);
$final_difficulty = (float)($data["final_difficulty"] ?? 1.0);

if ($final_difficulty < 1.0) {
    $final_difficulty = 1.0;
}

if ($final_difficulty > 5.0) {
    $final_difficulty = 5.0;
}

$final_difficulty = round($final_difficulty, 1);

$sql = "INSERT INTO game_results 
        (
            user_id, 
            room_id,
            player_name,
            score, 
            correct_answers, 
            total_questions, 
            lives_remaining, 
            final_difficulty
        )
        VALUES (?, NULL, NULL, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error en prepare",
        "details" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param(
    "iiiiid",
    $user_id,
    $score,
    $correct,
    $total,
    $lives,
    $final_difficulty
);

if ($stmt->execute()) {

    if (isset($_SESSION["user_id"])) {
        $newBadges = [];

        if (isset($_SESSION["user_id"])) {
            $newBadges = evaluateBadges($conn, (int)$_SESSION["user_id"]);
        }
    }
    echo json_encode([
        "success" => true,
        "message" => "Resultado guardado correctamente",
        "final_difficulty" => $final_difficulty,
        "new_badges" => $newBadges
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