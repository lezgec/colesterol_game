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
$answerJoin = $isSuperAdmin ? "" : "INNER JOIN game_rooms gr_scope ON ga.room_id = gr_scope.id";
$answerWhere = $isSuperAdmin ? "1 = 1" : "ga.game_mode = 'room' AND gr_scope.created_by = {$userId}";
$roomWhere = $isSuperAdmin ? "1 = 1" : "created_by = {$userId}";
$roomWhereWithAlias = $isSuperAdmin ? "1 = 1" : "gr.created_by = {$userId}";

try {

    $summaryQuery = "
        SELECT
            (SELECT COUNT(DISTINCT ga.user_id) FROM game_answers ga {$answerJoin} WHERE {$answerWhere} AND ga.user_id IS NOT NULL) AS total_users,
            (SELECT COUNT(*) FROM game_results grs INNER JOIN game_rooms gr ON grs.room_id = gr.id WHERE {$roomWhereWithAlias}) AS total_games,
            (SELECT COUNT(*) FROM game_rooms WHERE {$roomWhere}) AS total_rooms,
            (SELECT COUNT(*) FROM questions) AS total_questions,

            (SELECT COUNT(*) FROM game_answers ga {$answerJoin} WHERE {$answerWhere}) AS total_answers,

            (
                SELECT COALESCE(ROUND((SUM(is_correct) / COUNT(*)) * 100, 2), 0)
                FROM game_answers ga {$answerJoin}
                WHERE {$answerWhere}
            ) AS global_precision,

            (
                SELECT COALESCE(ROUND(AVG(response_time), 2), 0)
                FROM game_answers ga {$answerJoin}
                WHERE {$answerWhere}
            ) AS avg_response_time,

            (
                SELECT COALESCE(ROUND(AVG(difficulty_level), 2), 0)
                FROM game_answers ga {$answerJoin}
                WHERE {$answerWhere}
            ) AS avg_difficulty,

            (
                SELECT COUNT(DISTINCT user_id)
                FROM game_answers ga {$answerJoin}
                WHERE {$answerWhere} AND user_id IS NOT NULL
            ) AS active_users,

            (
                SELECT COUNT(*)
                FROM game_answers ga {$answerJoin}
                WHERE {$answerWhere} AND game_mode = 'solo'
            ) AS solo_answers,

            (
                SELECT COUNT(*)
                FROM game_answers ga {$answerJoin}
                WHERE {$answerWhere} AND game_mode = 'room'
            ) AS room_answers
    ";

    $summaryResult = $conn->query($summaryQuery);

    if (!$summaryResult) {
        throw new Exception($conn->error);
    }

    $summary = $summaryResult->fetch_assoc();

    $topPlayers = [];

    $playersQuery = "
        SELECT
            COALESCE(u.name, ga.player_name, 'Guest') AS player_name,
            COUNT(*) AS total_answers,
            COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
            COALESCE(SUM(ga.score_earned), 0) AS total_score,
            COALESCE(ROUND(AVG(ga.response_time), 2), 0) AS avg_response_time,
            COALESCE(ROUND(AVG(ga.difficulty_level), 2), 0) AS avg_difficulty,
            COALESCE(ROUND(MAX(ga.difficulty_level), 2), 0) AS max_difficulty
        FROM game_answers ga
        {$answerJoin}
        LEFT JOIN users u ON ga.user_id = u.id
        WHERE {$answerWhere}
        GROUP BY COALESCE(u.name, ga.player_name, 'Guest')
        ORDER BY total_score DESC, correct_answers DESC, avg_response_time ASC
        LIMIT 10
    ";

    $playersResult = $conn->query($playersQuery);

    if (!$playersResult) {
        throw new Exception($conn->error);
    }

    while ($row = $playersResult->fetch_assoc()) {
        $totalAnswers = (int)$row["total_answers"];
        $correctAnswers = (int)$row["correct_answers"];

        $topPlayers[] = [
            "player_name" => $row["player_name"],
            "total_answers" => $totalAnswers,
            "correct_answers" => $correctAnswers,
            "precision" => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0,
            "total_score" => (int)$row["total_score"],
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1),
            "max_difficulty" => round((float)$row["max_difficulty"], 1)
        ];
    }

    $topRooms = [];

    $roomsQuery = "
        SELECT
            gr.room_code,
            gr.name,
            gr.status,
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
        WHERE {$roomWhereWithAlias}
        ORDER BY total_players DESC, total_answers DESC
        LIMIT 10
    ";

    $roomsResult = $conn->query($roomsQuery);

    if (!$roomsResult) {
        throw new Exception($conn->error);
    }

    while ($row = $roomsResult->fetch_assoc()) {
        $totalAnswers = (int)$row["total_answers"];
        $correctAnswers = (int)$row["correct_answers"];

        $topRooms[] = [
            "room_code" => $row["room_code"],
            "name" => $row["name"],
            "status" => $row["status"],
            "total_players" => (int)$row["total_players"],
            "total_answers" => $totalAnswers,
            "correct_answers" => $correctAnswers,
            "precision" => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0,
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
        ];
    }

    $categories = [];

    $categoryQuery = "
        SELECT
            q.category,
            COUNT(ga.id) AS total_answers,
            COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
            COALESCE(ROUND(AVG(ga.response_time), 2), 0) AS avg_response_time,
            COALESCE(ROUND(AVG(ga.difficulty_level), 2), 0) AS avg_difficulty
        FROM game_answers ga
        {$answerJoin}
        INNER JOIN questions q ON ga.question_id = q.id
        WHERE {$answerWhere}
        GROUP BY q.category
        ORDER BY (COALESCE(SUM(ga.is_correct), 0) / COUNT(ga.id)) ASC, total_answers DESC
    ";

    $categoryResult = $conn->query($categoryQuery);

    if (!$categoryResult) {
        throw new Exception($conn->error);
    }

    while ($row = $categoryResult->fetch_assoc()) {
        $totalAnswers = (int)$row["total_answers"];
        $correctAnswers = (int)$row["correct_answers"];

        $categories[] = [
            "category" => $row["category"],
            "total_answers" => $totalAnswers,
            "correct_answers" => $correctAnswers,
            "precision" => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 2) : 0,
            "avg_response_time" => round((float)$row["avg_response_time"], 2),
            "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
        ];
    }

    echo json_encode([
        "success" => true,
        "summary" => [
            "total_users" => (int)$summary["total_users"],
            "active_users" => (int)$summary["active_users"],
            "total_games" => (int)$summary["total_games"],
            "total_rooms" => (int)$summary["total_rooms"],
            "total_questions" => (int)$summary["total_questions"],
            "total_answers" => (int)$summary["total_answers"],
            "global_precision" => round((float)$summary["global_precision"], 2),
            "avg_response_time" => round((float)$summary["avg_response_time"], 2),
            "avg_difficulty" => round((float)$summary["avg_difficulty"], 1),
            "solo_answers" => (int)$summary["solo_answers"],
            "room_answers" => (int)$summary["room_answers"]
        ],
        "top_players" => $topPlayers,
        "top_rooms" => $topRooms,
        "categories" => $categories
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
