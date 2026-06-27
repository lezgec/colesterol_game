<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/adaptive_difficulty_schema.php';
require_once __DIR__ . '/streak_helpers.php';
require_once __DIR__ . '/result_calculation_helpers.php';

require_csrf_token();

ensure_adaptive_difficulty_columns($conn);

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "JSON invalido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room_id = isset($data["room_id"]) && $data["room_id"] !== null ? (int)$data["room_id"] : null;
$player_name = trim($data["player_name"] ?? "");
$question_id = (int)($data["question_id"] ?? 0);
$selected_option = strtoupper(trim($data["selected_option"] ?? ""));
$response_time = max(0, (int)($data["response_time"] ?? 0));
$difficulty_level = normalize_game_difficulty($data["difficulty_level"] ?? 1);
$game_mode = trim($data["game_mode"] ?? "solo");
$user_id = null;

if ($game_mode === "solo") {
    $user_id = current_session_is_active() ? current_user_id() : null;

    if (!$user_id) {
        echo json_encode([
            "success" => false,
            "message" => "Sesion requerida"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} elseif (current_session_is_active()) {
    $user_id = current_user_id();
}

if ($question_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Pregunta invalida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($selected_option !== "" && !in_array($selected_option, ["A", "B", "C", "D"], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Opcion invalida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "
    SELECT
        q.id,
        q.correct_option,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.explanation,
        q.difficulty_level
    FROM questions q
";

if ($room_id !== null) {
    $sql .= "
        INNER JOIN room_questions rq
            ON rq.question_id = q.id
           AND rq.room_id = ?
    ";
}

$sql .= "
    WHERE q.id = ?
      AND q.status = 'verified'
      AND q.is_active = 1
    LIMIT 1
";

$stmtQuestion = $conn->prepare($sql);

if (!$stmtQuestion) {
    echo json_encode([
        "success" => false,
        "message" => "Error preparando pregunta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($room_id !== null) {
    $stmtQuestion->bind_param("ii", $room_id, $question_id);
} else {
    $stmtQuestion->bind_param("i", $question_id);
}

$stmtQuestion->execute();
$questionResult = $stmtQuestion->get_result();

if ($questionResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Pregunta no disponible"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$question = $questionResult->fetch_assoc();
$correct_option = strtoupper(trim($question["correct_option"] ?? ""));
$is_correct = $selected_option !== "" && $selected_option === $correct_option ? 1 : 0;
$score_earned = calculate_answer_points($is_correct === 1, $response_time);
$correctKey = "option_" . strtolower($correct_option);
$correctText = $question[$correctKey] ?? "";

$stmt = $conn->prepare("
    INSERT INTO game_answers (
        user_id,
        room_id,
        player_name,
        question_id,
        selected_option,
        correct_option,
        is_correct,
        response_time,
        difficulty_level,
        score_earned,
        game_mode
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error prepare",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param(
    "iisissiidis",
    $user_id,
    $room_id,
    $player_name,
    $question_id,
    $selected_option,
    $correct_option,
    $is_correct,
    $response_time,
    $difficulty_level,
    $score_earned,
    $game_mode
);

if ($stmt->execute()) {
    register_player_answer_streak($conn, $user_id, $is_correct);

    echo json_encode([
        "success" => true,
        "is_correct" => $is_correct === 1,
        "score_earned" => $score_earned,
        "correct_option" => $correct_option,
        "correct_text" => $correctText,
        "correct_answer" => trim($correct_option . ". " . $correctText),
        "explanation" => $question["explanation"] ?? "",
        "difficulty_level" => $difficulty_level
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error insert",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmtQuestion->close();
$stmt->close();
$conn->close();
