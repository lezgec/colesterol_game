<?php
session_start();

ini_set('display_errors', 1);
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

function fetchAllRows(mysqli $conn, string $sql): array {
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

try {

    $playersSql = "
        SELECT
            COALESCE(u.name, ga.player_name, 'Guest') AS player_name,
            COUNT(*) AS total_answers,
            COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
            COALESCE(SUM(ga.score_earned), 0) AS total_score,
            COALESCE(ROUND(AVG(ga.response_time), 2), 0) AS avg_response_time,
            COALESCE(ROUND(AVG(ga.difficulty_level), 2), 0) AS avg_difficulty,
            COALESCE(MAX(ga.difficulty_level), 0) AS max_difficulty
        FROM game_answers ga
        LEFT JOIN users u ON ga.user_id = u.id
        GROUP BY COALESCE(u.name, ga.player_name, 'Guest')
        ORDER BY total_score DESC
        LIMIT 20
    ";

    $playersRaw = fetchAllRows($conn, $playersSql);
    $players = [];

    foreach ($playersRaw as $row) {
        $total = (int)$row["total_answers"];
        $correct = (int)$row["correct_answers"];

        $players[] = [
            "label" => $row["player_name"],
            "total_answers" => $total,
            "correct_answers" => $correct,
            "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            "total_score" => (int)$row["total_score"],
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1),
            "max_difficulty" => round((float)$row["max_difficulty"], 1)
        ];
    }

    $roomsSql = "
        SELECT
            gr.room_code,
            gr.name,
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
                COALESCE(ROUND(AVG(response_time), 2), 0) AS avg_response_time,
                COALESCE(ROUND(AVG(difficulty_level), 2), 0) AS avg_difficulty
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
                COALESCE(ROUND(AVG(final_difficulty), 2), 0) AS avg_difficulty
            FROM game_results
            WHERE room_id IS NOT NULL
            GROUP BY room_id
        ) result_stats ON result_stats.room_id = gr.id
        ORDER BY total_answers DESC
        LIMIT 20
    ";

    $roomsRaw = fetchAllRows($conn, $roomsSql);
    $rooms = [];

    foreach ($roomsRaw as $row) {
        $total = (int)$row["total_answers"];
        $correct = (int)$row["correct_answers"];

        $rooms[] = [
            "label" => $row["room_code"] . " - " . $row["name"],
            "room_code" => $row["room_code"],
            "name" => $row["name"],
            "total_players" => (int)$row["total_players"],
            "total_answers" => $total,
            "correct_answers" => $correct,
            "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
        ];
    }

    $categoriesSql = "
        SELECT
            q.category,
            COUNT(*) AS total_answers,
            COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
            COALESCE(SUM(ga.score_earned), 0) AS total_score,
            COALESCE(ROUND(AVG(ga.response_time), 2), 0) AS avg_response_time,
            COALESCE(ROUND(AVG(ga.difficulty_level), 2), 0) AS avg_difficulty
        FROM game_answers ga
        INNER JOIN questions q ON ga.question_id = q.id
        GROUP BY q.category
        ORDER BY total_answers DESC
    ";

    $categoriesRaw = fetchAllRows($conn, $categoriesSql);
    $categories = [];

    foreach ($categoriesRaw as $row) {
        $total = (int)$row["total_answers"];
        $correct = (int)$row["correct_answers"];

        $categories[] = [
            "label" => $row["category"],
            "category" => $row["category"],
            "total_answers" => $total,
            "correct_answers" => $correct,
            "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            "total_score" => (int)$row["total_score"],
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
        ];
    }

    $modesSql = "
        SELECT
            game_mode,
            COUNT(*) AS total_answers,
            COALESCE(SUM(is_correct), 0) AS correct_answers,
            COALESCE(SUM(score_earned), 0) AS total_score,
            COALESCE(ROUND(AVG(response_time), 2), 0) AS avg_response_time,
            COALESCE(ROUND(AVG(difficulty_level), 2), 0) AS avg_difficulty
        FROM game_answers
        GROUP BY game_mode
        ORDER BY total_answers DESC
    ";

    $modesRaw = fetchAllRows($conn, $modesSql);
    $modes = [];

    foreach ($modesRaw as $row) {
        $total = (int)$row["total_answers"];
        $correct = (int)$row["correct_answers"];

        $modes[] = [
            "label" => $row["game_mode"],
            "game_mode" => $row["game_mode"],
            "total_answers" => $total,
            "correct_answers" => $correct,
            "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            "total_score" => (int)$row["total_score"],
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
        ];
    }

    $difficultySql = "
        SELECT
            CASE
                WHEN difficulty_level < 1.5 THEN '1.0 - Basic'
                WHEN difficulty_level < 2.5 THEN '2.0 - Low'
                WHEN difficulty_level < 3.5 THEN '3.0 - Medium'
                WHEN difficulty_level < 4.5 THEN '4.0 - High'
                ELSE '5.0 - Expert'
            END AS difficulty_range,
            COUNT(*) AS total_answers,
            COALESCE(SUM(is_correct), 0) AS correct_answers,
            COALESCE(ROUND(AVG(response_time), 2), 0) AS avg_response_time,
            COALESCE(SUM(score_earned), 0) AS total_score
        FROM game_answers
        GROUP BY difficulty_range
        ORDER BY MIN(difficulty_level)
    ";

    $difficultyRaw = fetchAllRows($conn, $difficultySql);
    $difficultyLevels = [];

    foreach ($difficultyRaw as $row) {
        $total = (int)$row["total_answers"];
        $correct = (int)$row["correct_answers"];

        $difficultyLevels[] = [
            "label" => $row["difficulty_range"],
            "total_answers" => $total,
            "correct_answers" => $correct,
            "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "total_score" => (int)$row["total_score"]
        ];
    }

    echo json_encode([
        "success" => true,
        "players" => $players,
        "rooms" => $rooms,
        "categories" => $categories,
        "modes" => $modes,
        "difficulty_levels" => $difficultyLevels
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
