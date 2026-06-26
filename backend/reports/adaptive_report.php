<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 0);
ini_set("precision", "10");
ini_set("serialize_precision", "-1");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$isSuperAdmin = is_super_admin();
$userId = (int)($_SESSION["user_id"] ?? 0);
$roomCode = strtoupper(trim($_GET["code"] ?? ""));

$params = [];
$types = "";

$where = "1 = 1";

if ($roomCode !== "") {
    $stmtRoom = $conn->prepare("
        SELECT id, room_code, name, created_by
        FROM game_rooms
        WHERE room_code = ?
    ");

    $stmtRoom->bind_param("s", $roomCode);
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

    if (!$isSuperAdmin && (int)$room["created_by"] !== $userId) {
        echo json_encode([
            "success" => false,
            "message" => "No autorizado"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $roomId = (int)$room["id"];

    $where = "ga.room_id = ?";
    $params[] = $roomId;
    $types .= "i";

    $stmtRoom->close();
} else {
    $room = null;

    if (!$isSuperAdmin) {
        $where = "ga.game_mode = 'room' AND gr_scope.created_by = ?";
        $params[] = $userId;
        $types .= "i";
    }
}

$sql = "
    SELECT
        ga.id,
        ga.player_name,
        ga.user_id,
        ga.room_id,
        ga.question_id,
        q.question,
        q.category,
        ga.is_correct,
        ga.response_time,
        ga.difficulty_level,
        ga.score_earned,
        ga.game_mode,
        ga.answered_at
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    " . (!$isSuperAdmin && $roomCode === "" ? "INNER JOIN game_rooms gr_scope ON ga.room_id = gr_scope.id" : "") . "
    WHERE $where
    ORDER BY ga.player_name ASC, ga.answered_at ASC, ga.id ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error prepare",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$timeline = [];
$byPlayer = [];

while ($row = $result->fetch_assoc()) {
    $player = $row["player_name"] ?: ("User #" . $row["user_id"]);

    if (!isset($byPlayer[$player])) {
        $byPlayer[$player] = [
            "player_name" => $player,
            "points" => [],
            "total_answers" => 0,
            "correct_answers" => 0,
            "avg_response_time" => 0,
            "avg_difficulty" => 0
        ];
    }

    $byPlayer[$player]["total_answers"]++;
    $byPlayer[$player]["correct_answers"] += (int)$row["is_correct"];
    $byPlayer[$player]["avg_response_time"] += (float)$row["response_time"];
    $byPlayer[$player]["avg_difficulty"] += (float)$row["difficulty_level"];

    $point = [
        "answer_id" => (int)$row["id"],
        "player_name" => $player,
        "question_id" => (int)$row["question_id"],
        "question" => $row["question"],
        "category" => $row["category"],
        "is_correct" => (int)$row["is_correct"],
        "response_time" => round((float)$row["response_time"], 2),
        "difficulty_level" => round((float)$row["difficulty_level"], 1),
        "score_earned" => (int)$row["score_earned"],
        "game_mode" => $row["game_mode"],
        "answered_at" => $row["answered_at"]
    ];

    $timeline[] = $point;
    $byPlayer[$player]["points"][] = $point;
}

$stmt->close();

$hasDetailedAnswers = count($timeline) > 0;

if (!$hasDetailedAnswers && $roomCode !== "") {
    $fallbackSql = "
        SELECT
            id,
            player_name,
            score,
            correct_answers,
            total_questions,
            final_difficulty,
            played_at
        FROM game_results
        WHERE room_id = ?
        ORDER BY player_name ASC, played_at ASC, id ASC
    ";

    $stmt = $conn->prepare($fallbackSql);
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $fallbackResult = $stmt->get_result();

    while ($row = $fallbackResult->fetch_assoc()) {
        $player = $row["player_name"] ?: ("Result #" . $row["id"]);
        $total = (int)$row["total_questions"];
        $correct = (int)$row["correct_answers"];
        $difficulty = round((float)$row["final_difficulty"], 1);

        $point = [
            "answer_id" => (int)$row["id"],
            "player_name" => $player,
            "question_id" => 0,
            "question" => "Resultado agregado de sala",
            "category" => "Sala",
            "is_correct" => $total > 0 && $correct >= ($total / 2) ? 1 : 0,
            "response_time" => 0,
            "difficulty_level" => $difficulty,
            "score_earned" => (int)$row["score"],
            "game_mode" => "room",
            "answered_at" => $row["played_at"]
        ];

        $timeline[] = $point;
        $byPlayer[$player] = [
            "player_name" => $player,
            "points" => [$point],
            "total_answers" => $total,
            "correct_answers" => $correct,
            "avg_response_time" => 0,
            "avg_difficulty" => $difficulty
        ];
    }

    $stmt->close();
}

$players = [];
$summaryTotalAnswers = 0;

foreach ($byPlayer as $playerData) {
    $total = $playerData["total_answers"];
    $correct = $playerData["correct_answers"];
    $summaryTotalAnswers += $total;

    $playerData["precision"] = $total > 0
        ? round(($correct / $total) * 100, 2)
        : 0;

    $playerData["avg_response_time"] = $total > 0 && $hasDetailedAnswers
        ? round($playerData["avg_response_time"] / $total, 2)
        : round((float)$playerData["avg_response_time"], 2);

    $playerData["avg_difficulty"] = $total > 0 && $hasDetailedAnswers
        ? round($playerData["avg_difficulty"] / $total, 1)
        : round((float)$playerData["avg_difficulty"], 1);

    $players[] = $playerData;
}

echo json_encode([
    "success" => true,
    "room" => $room,
    "summary" => [
        "total_players" => count($players),
        "total_answers" => $hasDetailedAnswers
            ? count($timeline)
            : $summaryTotalAnswers
    ],
    "players" => $players,
    "timeline" => $timeline
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
