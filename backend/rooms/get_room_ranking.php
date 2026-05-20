<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    echo json_encode([]);
    exit;
}

$stmtRoom = $conn->prepare("SELECT id FROM game_rooms WHERE room_code = ?");
$stmtRoom->bind_param("s", $roomCode);
$stmtRoom->execute();
$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$room = $roomResult->fetch_assoc();
$roomId = (int)$room["id"];

$sql = "
    SELECT 
        player_name,
        MAX(score) AS best_score,
        MAX(correct_answers) AS best_correct,
        MAX(total_questions) AS total_questions
    FROM game_results
    WHERE room_id = ?
    GROUP BY player_name
    ORDER BY best_score DESC, best_correct DESC, player_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $roomId);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "player_name" => $row["player_name"],
        "best_score" => (int)$row["best_score"],
        "best_correct" => (int)$row["best_correct"],
        "total_questions" => (int)$row["total_questions"]
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>