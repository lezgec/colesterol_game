<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
$sql = "
    SELECT 
        u.name,
        MAX(g.score) AS best_score,
        COUNT(g.id) AS total_games
    FROM game_results g
    INNER JOIN users u ON g.user_id = u.id
    GROUP BY g.user_id, u.name
    ORDER BY best_score DESC, total_games DESC, u.name ASC
    LIMIT 10
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener el ranking",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "name" => $row["name"],
        "best_score" => (int)$row["best_score"],
        "total_games" => (int)$row["total_games"]
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$conn->close();
?>