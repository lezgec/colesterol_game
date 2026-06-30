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

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código de sala vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom = $conn->prepare("
    SELECT
        id,
        room_code,
        name,
        status,
        question_count,
        time_limit,
        initial_difficulty,
        created_by,
        created_at,
        started_at,
        finished_at
    FROM game_rooms
    WHERE room_code = ?
");

if (!$stmtRoom) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta de sala",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
$roomId = (int)$room["id"];

if (!is_super_admin() && (int)$room["created_by"] !== (int)($_SESSION["user_id"] ?? 0)) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtRoom->close();

$summarySql = "
    SELECT
        COUNT(DISTINCT player_name) AS total_players,
        COUNT(*) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty,
        COALESCE(MAX(difficulty_level), 0) AS max_difficulty,
        COALESCE(SUM(score_earned), 0) AS total_points
    FROM game_answers
    WHERE room_id = ?
      AND game_mode = 'room'
";

$stmt = $conn->prepare($summarySql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalAnswers = (int)$summary["total_answers"];
$correctAnswers = (int)$summary["correct_answers"];

$fallbackSummarySql = "
    SELECT
        COUNT(DISTINCT player_name) AS total_players,
        COALESCE(SUM(total_questions), 0) AS total_answers,
        COALESCE(SUM(correct_answers), 0) AS correct_answers,
        COALESCE(AVG(final_difficulty), 0) AS avg_difficulty,
        COALESCE(MAX(final_difficulty), 0) AS max_difficulty,
        COALESCE(SUM(score), 0) AS total_points
    FROM game_results
    WHERE room_id = ?
";

$stmt = $conn->prepare($fallbackSummarySql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$fallbackSummary = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ((int)($fallbackSummary["total_answers"] ?? 0) > $totalAnswers) {
    $summary = $fallbackSummary;
    $totalAnswers = (int)($summary["total_answers"] ?? 0);
    $correctAnswers = (int)($summary["correct_answers"] ?? 0);
    $summary["avg_response_time"] = 0;
}

$precision = $totalAnswers > 0
    ? round(($correctAnswers / $totalAnswers) * 100, 2)
    : 0;

$rankingSql = "
    SELECT
        player_name,
        COUNT(*) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(SUM(score_earned), 0) AS total_score,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty,
        COALESCE(MAX(difficulty_level), 0) AS max_difficulty
    FROM game_answers
    WHERE room_id = ?
      AND game_mode = 'room'
    GROUP BY player_name
    ORDER BY total_score DESC, correct_answers DESC, avg_response_time ASC
";

$stmt = $conn->prepare($rankingSql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$rankingResult = $stmt->get_result();

$ranking = [];

while ($row = $rankingResult->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];

    $ranking[] = [
        "player_name" => $row["player_name"],
        "total_answers" => $total,
        "correct_answers" => $correct,
        "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
        "total_score" => (int)$row["total_score"],
        "avg_response_time" => round((float)$row["avg_response_time"], 2),
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1),
        "max_difficulty" => round((float)$row["max_difficulty"], 1)
    ];
}

$stmt->close();

$fallbackRankingSql = "
    SELECT
        player_name,
        total_questions AS total_answers,
        correct_answers,
        score AS total_score,
        final_difficulty AS avg_difficulty,
        final_difficulty AS max_difficulty
    FROM game_results
    WHERE room_id = ?
    ORDER BY score DESC, correct_answers DESC
";

$stmt = $conn->prepare($fallbackRankingSql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$fallbackRankingResult = $stmt->get_result();

while ($row = $fallbackRankingResult->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $fallbackRow = [
        "player_name" => $row["player_name"],
        "total_answers" => $total,
        "correct_answers" => $correct,
        "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
        "total_score" => (int)$row["total_score"],
        "avg_response_time" => 0,
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1),
        "max_difficulty" => round((float)$row["max_difficulty"], 1)
    ];

    $existingIndex = null;
    foreach ($ranking as $index => $rankingRow) {
        if ($rankingRow["player_name"] === $fallbackRow["player_name"]) {
            $existingIndex = $index;
            break;
        }
    }

    if ($existingIndex === null) {
        $ranking[] = $fallbackRow;
    } elseif ($fallbackRow["total_answers"] > $ranking[$existingIndex]["total_answers"]) {
        $ranking[$existingIndex] = $fallbackRow;
    }
}

$stmt->close();

usort($ranking, function ($a, $b) {
    return [$b["total_score"], $b["correct_answers"], $a["avg_response_time"]]
        <=> [$a["total_score"], $a["correct_answers"], $b["avg_response_time"]];
});

$categorySql = "
    SELECT
        q.category,
        COUNT(*) AS total_answers,
        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty
    FROM game_answers ga
    INNER JOIN questions q ON ga.question_id = q.id
    WHERE ga.room_id = ?
      AND ga.game_mode = 'room'
    GROUP BY q.category
    ORDER BY (COALESCE(SUM(ga.is_correct), 0) / COUNT(*)) DESC, COUNT(*) DESC
";

$stmt = $conn->prepare($categorySql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$categoryResult = $stmt->get_result();

$categories = [];

while ($row = $categoryResult->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];

    $categories[] = [
        "category" => $row["category"],
        "total_answers" => $total,
        "correct_answers" => $correct,
        "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
        "avg_response_time" => round((float)$row["avg_response_time"], 2),
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1)
    ];
}

$stmt->close();

$questionSql = "
    SELECT
        q.id,
        q.question,
        q.category,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_option,
        COUNT(ga.id) AS total_answers,
        COALESCE(SUM(CASE WHEN ga.is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct_answers,
        COALESCE(SUM(CASE WHEN ga.is_correct = 0 THEN 1 ELSE 0 END), 0) AS incorrect_answers,
        COALESCE(SUM(CASE WHEN ga.selected_option = 'A' THEN 1 ELSE 0 END), 0) AS option_a_count,
        COALESCE(SUM(CASE WHEN ga.selected_option = 'B' THEN 1 ELSE 0 END), 0) AS option_b_count,
        COALESCE(SUM(CASE WHEN ga.selected_option = 'C' THEN 1 ELSE 0 END), 0) AS option_c_count,
        COALESCE(SUM(CASE WHEN ga.selected_option = 'D' THEN 1 ELSE 0 END), 0) AS option_d_count,
        COALESCE(SUM(CASE WHEN ga.id IS NOT NULL AND (ga.selected_option IS NULL OR ga.selected_option = '') THEN 1 ELSE 0 END), 0) AS no_answer_count,
        COALESCE(AVG(ga.response_time), 0) AS avg_response_time,
        COALESCE(AVG(ga.difficulty_level), 0) AS avg_difficulty
    FROM room_questions rq
    INNER JOIN questions q ON rq.question_id = q.id
    LEFT JOIN game_answers ga
        ON ga.room_id = rq.room_id
        AND ga.question_id = q.id
        AND ga.game_mode = 'room'
    WHERE rq.room_id = ?
    GROUP BY
        q.id,
        q.question,
        q.category,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_option,
        rq.id
    ORDER BY rq.id ASC
";

$stmt = $conn->prepare($questionSql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$questionResult = $stmt->get_result();

$questionStatistics = [];

while ($row = $questionResult->fetch_assoc()) {
    $total = (int)$row["total_answers"];
    $correct = (int)$row["correct_answers"];
    $incorrect = (int)$row["incorrect_answers"];
    $correctOption = strtoupper(trim($row["correct_option"]));
    $optionCounts = [
        "A" => (int)$row["option_a_count"],
        "B" => (int)$row["option_b_count"],
        "C" => (int)$row["option_c_count"],
        "D" => (int)$row["option_d_count"]
    ];
    $optionTexts = [
        "A" => $row["option_a"],
        "B" => $row["option_b"],
        "C" => $row["option_c"],
        "D" => $row["option_d"]
    ];
    $mostSelectedOption = null;
    $mostSelectedOptionCount = 0;
    $mostSelectedWrongOption = null;
    $mostSelectedWrongOptionCount = 0;

    foreach ($optionCounts as $option => $count) {
        if ($count > $mostSelectedOptionCount) {
            $mostSelectedOption = $option;
            $mostSelectedOptionCount = $count;
        }

        if ($option !== $correctOption && $count > $mostSelectedWrongOptionCount) {
            $mostSelectedWrongOption = $option;
            $mostSelectedWrongOptionCount = $count;
        }
    }

    $questionStatistics[] = [
        "question_id" => (int)$row["id"],
        "question" => $row["question"],
        "category" => $row["category"],
        "options" => $optionTexts,
        "option_counts" => $optionCounts,
        "correct_option" => $correctOption,
        "total_answers" => $total,
        "correct_answers" => $correct,
        "incorrect_answers" => $incorrect,
        "no_answer_count" => (int)$row["no_answer_count"],
        "failure_rate" => $total > 0 ? round(($incorrect / $total) * 100, 2) : 0,
        "precision" => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
        "avg_response_time" => round((float)$row["avg_response_time"], 2),
        "avg_difficulty" => round((float)$row["avg_difficulty"], 1),
        "most_selected_option" => $mostSelectedOptionCount > 0 ? $mostSelectedOption : null,
        "most_selected_option_count" => $mostSelectedOptionCount,
        "most_selected_wrong_option" => $mostSelectedWrongOptionCount > 0 ? $mostSelectedWrongOption : null,
        "most_selected_wrong_option_count" => $mostSelectedWrongOptionCount
    ];
}

$stmt->close();

$mostFailedQuestions = $questionStatistics;
usort($mostFailedQuestions, function ($a, $b) {
    return [
        $b["failure_rate"],
        $b["incorrect_answers"],
        $b["total_answers"]
    ] <=> [
        $a["failure_rate"],
        $a["incorrect_answers"],
        $a["total_answers"]
    ];
});

$mostFailedQuestions = array_slice($mostFailedQuestions, 0, 10);

echo json_encode([
    "success" => true,
    "room" => [
        "id" => $roomId,
        "room_code" => $room["room_code"],
        "name" => $room["name"],
        "status" => $room["status"],
        "question_count" => (int)$room["question_count"],
        "time_limit" => (int)$room["time_limit"],
        "initial_difficulty" => round((float)$room["initial_difficulty"], 1),
        "created_at" => $room["created_at"],
        "started_at" => $room["started_at"],
        "finished_at" => $room["finished_at"]
    ],
    "summary" => [
        "total_players" => (int)$summary["total_players"],
        "total_answers" => $totalAnswers,
        "correct_answers" => $correctAnswers,
        "precision" => $precision,
        "avg_response_time" => round((float)$summary["avg_response_time"], 2),
        "avg_difficulty" => round((float)$summary["avg_difficulty"], 1),
        "max_difficulty" => round((float)$summary["max_difficulty"], 1),
        "total_points" => (int)$summary["total_points"]
    ],
    "ranking" => $ranking,
    "categories" => $categories,
    "most_failed_questions" => $mostFailedQuestions,
    "question_statistics" => $questionStatistics
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
