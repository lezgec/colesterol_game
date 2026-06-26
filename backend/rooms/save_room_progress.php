<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/adaptive_difficulty_schema.php';
require_once __DIR__ . '/../game/result_calculation_helpers.php';

require_csrf_token();

ensure_adaptive_difficulty_columns($conn);

$data = json_decode(file_get_contents("php://input"), true);

$room_code = strtoupper(trim($data["room_code"] ?? ""));
$player_name = trim($data["player_name"] ?? "");
$total_questions = (int)($data["total_questions"] ?? 0);

if ($room_code === "" || $player_name === "") {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom = $conn->prepare("
    SELECT id
    FROM game_rooms
    WHERE room_code = ?
");

if (!$stmtRoom) {
    echo json_encode([
        "success" => false,
        "message" => "Error preparando sala",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom->bind_param("s", $room_code);
$stmtRoom->execute();
$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sala no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $roomResult->fetch_assoc();
$room_id = (int)$room["id"];

$calculated = calculate_recent_result_from_answers($conn, [
    "room_id" => $room_id,
    "player_name" => $player_name
], $total_questions);

$score = $calculated["score"];
$correct_answers = $calculated["correct_answers"];
$total_questions = $calculated["total_questions"] > 0 ? $calculated["total_questions"] : $total_questions;
$final_difficulty = $calculated["final_difficulty"];

$check = $conn->prepare("
    SELECT id
    FROM game_results
    WHERE room_id = ?
      AND player_name = ?
");

if (!$check) {
    echo json_encode([
        "success" => false,
        "message" => "Error preparando validación",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$check->bind_param("is", $room_id, $player_name);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    $existingId = (int)$existing["id"];

    $stmt = $conn->prepare("
        UPDATE game_results
        SET
            score = ?,
            correct_answers = ?,
            total_questions = ?,
            final_difficulty = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Error preparando actualización",
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param(
        "iiidi",
        $score,
        $correct_answers,
        $total_questions,
        $final_difficulty,
        $existingId
    );

} else {
    $stmt = $conn->prepare("
        INSERT INTO game_results
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
        VALUES
            (
                NULL,
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                ?
            )
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Error preparando inserción",
            "error" => $conn->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param(
        "isiiid",
        $room_id,
        $player_name,
        $score,
        $correct_answers,
        $total_questions,
        $final_difficulty
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "final_difficulty" => $final_difficulty
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmtRoom->close();
$check->close();
$stmt->close();
$conn->close();
?>
