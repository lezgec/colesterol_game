<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=utf-8");
ini_set("precision", "10");
ini_set("serialize_precision", "-1");

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
$where = "";
$types = "";
$params = [];

if (!$isSuperAdmin) {
    $where = "WHERE gr.created_by = ?";
    $types = "i";
    $params[] = $userId;
}

$sql = "
    SELECT
        gr.id,
        gr.room_code,
        gr.name,
        gr.status,
        gr.question_count,
        gr.time_limit,
        gr.created_at,
        gr.started_at,
        gr.finished_at,
        COALESCE(answer_stats.total_players, result_stats.total_players, 0) AS total_players,
        COALESCE(answer_stats.total_answers, result_stats.total_answers, 0) AS total_answers,
        COALESCE(answer_stats.correct_answers, result_stats.correct_answers, 0) AS correct_answers,
        COALESCE(answer_stats.avg_response_time, 0) AS avg_response_time,
        COALESCE(answer_stats.avg_difficulty, result_stats.avg_difficulty, 0) AS avg_difficulty
    FROM game_rooms gr
    LEFT JOIN (
        SELECT
            room_id,
            COUNT(DISTINCT player_name) AS total_players,
            COUNT(*) AS total_answers,
            COALESCE(SUM(is_correct), 0) AS correct_answers,
            COALESCE(AVG(response_time), 0) AS avg_response_time,
            COALESCE(AVG(difficulty_level), 0) AS avg_difficulty
        FROM game_answers
        WHERE game_mode = 'room'
        GROUP BY room_id
    ) answer_stats ON answer_stats.room_id = gr.id
    LEFT JOIN (
        SELECT
            room_id,
            COUNT(DISTINCT player_name) AS total_players,
            COALESCE(SUM(total_questions), 0) AS total_answers,
            COALESCE(SUM(correct_answers), 0) AS correct_answers,
            COALESCE(AVG(final_difficulty), 0) AS avg_difficulty
        FROM game_results
        WHERE room_id IS NOT NULL
        GROUP BY room_id
    ) result_stats ON result_stats.room_id = gr.id
    {$where}
    ORDER BY gr.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener reportes de salas",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$rooms = [];

while ($row = $result->fetch_assoc()) {
    $totalAnswers = (int)$row["total_answers"];
    $correctAnswers = (int)$row["correct_answers"];

    $rooms[] = [
        "id" => (int)$row["id"],
        "room_code" => $row["room_code"],
        "name" => $row["name"],
        "status" => $row["status"],
        "question_count" => (int)$row["question_count"],
        "time_limit" => (int)$row["time_limit"],
        "created_at" => $row["created_at"],
        "started_at" => $row["started_at"],
        "finished_at" => $row["finished_at"],
        "total_players" => (int)$row["total_players"],
        "total_answers" => $totalAnswers,
        "correct_answers" => $correctAnswers,
        "precision" => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0,
        "avg_response_time" => round((float)$row["avg_response_time"], 2),
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
    ];
}

echo json_encode([
    "success" => true,
    "rooms" => $rooms
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
