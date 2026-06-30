<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../badges/evaluate_badges.php';
require_once __DIR__ . '/../../config/adaptive_difficulty_schema.php';
require_once __DIR__ . '/result_calculation_helpers.php';

require_csrf_token();

ensure_adaptive_difficulty_columns($conn);

if (!current_session_is_active()) {
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

$total = (int)($data["total_questions"] ?? 0);
$lives = (int)($data["lives_remaining"] ?? 0);
$calculated = calculate_recent_result_from_answers($conn, [
    "user_id" => $user_id,
    "room_id" => null
], $total);

$score = $calculated["score"];
$correct = $calculated["correct_answers"];
$total = $calculated["total_questions"] > 0 ? $calculated["total_questions"] : $total;
$final_difficulty = $calculated["final_difficulty"];

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
        "details" => app_error_detail($conn->error)
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
        "details" => app_error_detail($stmt->error)
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>
