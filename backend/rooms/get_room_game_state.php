<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$room_code = strtoupper(trim($_GET["code"] ?? ""));

if ($room_code === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        id,
        status,
        started_at,
        question_started_at,
        time_limit,
        question_count,
        current_question_index,
        initial_difficulty
    FROM game_rooms
    WHERE room_code = ?
");

$stmt->bind_param("s", $room_code);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sala no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $result->fetch_assoc();

$roomId = (int)$room["id"];
$status = $room["status"];
$timeLimit = (int)$room["time_limit"];
$questionCount = (int)$room["question_count"];
$currentQuestionIndex = (int)$room["current_question_index"];
$initialDifficulty = (float)$room["initial_difficulty"];
$questionStartedAt = $room["question_started_at"];

if ($timeLimit <= 0) {
    $timeLimit = 20;
}

if ($initialDifficulty < 1.0) {
    $initialDifficulty = 1.0;
}

if ($initialDifficulty > 5.0) {
    $initialDifficulty = 5.0;
}

if ($questionCount <= 0) {
    $stmtCount = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM room_questions
        WHERE room_id = ?
    ");

    $stmtCount->bind_param("i", $roomId);
    $stmtCount->execute();

    $countData = $stmtCount->get_result()->fetch_assoc();
    $questionCount = (int)($countData["total"] ?? 0);

    $stmtCount->close();
}

$timeLeft = $timeLimit;

if ($status === "started" && $questionStartedAt) {
    $elapsedStmt = $conn->prepare("
        SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) AS elapsed
    ");

    $elapsedStmt->bind_param("s", $questionStartedAt);
    $elapsedStmt->execute();

    $elapsedData = $elapsedStmt->get_result()->fetch_assoc();
    $elapsed = (int)($elapsedData["elapsed"] ?? 0);

    $elapsedStmt->close();

    if ($elapsed >= $timeLimit) {
        $steps = (int)floor($elapsed / $timeLimit);
        $currentQuestionIndex += $steps;

        if ($currentQuestionIndex >= $questionCount) {
            $currentQuestionIndex = max(0, $questionCount - 1);
            $status = "finished";

            $update = $conn->prepare("
                UPDATE game_rooms
                SET 
                    status = 'finished',
                    finished_at = NOW(),
                    current_question_index = ?
                WHERE id = ?
            ");

            $update->bind_param("ii", $currentQuestionIndex, $roomId);
            $update->execute();
            $update->close();

            $timeLeft = 0;
        } else {
            $update = $conn->prepare("
                UPDATE game_rooms
                SET 
                    current_question_index = ?,
                    question_started_at = NOW()
                WHERE id = ?
            ");

            $update->bind_param("ii", $currentQuestionIndex, $roomId);
            $update->execute();
            $update->close();

            $timeLeft = $timeLimit;
        }
    } else {
        $timeLeft = $timeLimit - $elapsed;
    }
}

echo json_encode([
    "success" => true,
    "status" => $status,
    "current_question_index" => $currentQuestionIndex,
    "time_left" => $timeLeft,
    "question_count" => $questionCount,
    "initial_difficulty" => $initialDifficulty,
    "finished" => $status === "finished",
    "paused" => $status === "paused"
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>