<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$user_id = (int)$_SESSION["user_id"];

$sql = "
    SELECT 
        score,
        correct_answers,
        total_questions,
        lives_remaining,
        difficulty,
        final_difficulty,
        room_id,
        player_name,
        played_at
    FROM game_results
    WHERE user_id = ?
    ORDER BY played_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {

    $correct = (int)$row["correct_answers"];
    $total = (int)$row["total_questions"];

    $precision = 0;

    if ($total > 0) {
        $precision = round(($correct / $total) * 100, 2);
    }

    $data[] = [
        "score" => (int)$row["score"],
        "correct_answers" => $correct,
        "total_questions" => $total,
        "precision" => $precision,
        "lives_remaining" => (int)$row["lives_remaining"],
        "difficulty" => $row["difficulty"],
        "final_difficulty" => (float)$row["final_difficulty"],
        "room_id" => $row["room_id"] !== null ? (int)$row["room_id"] : null,
        "player_name" => $row["player_name"],
        "played_at" => $row["played_at"]
    ];
}

echo json_encode([
    "success" => true,
    "results" => $data
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>