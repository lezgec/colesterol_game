<?php
header("Content-Type: application/json; charset=utf-8");
ini_set("serialize_precision", "-1");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION["user_id"];
$isSuperAdmin = is_super_admin();

$answerScopeJoin = "";
$answerScopeWhere = "";
$answerScopeTypes = "";
$answerScopeParams = [];

if (!$isSuperAdmin) {
    $answerScopeJoin = "LEFT JOIN game_rooms gr ON ga.room_id = gr.id";
    $answerScopeWhere = "WHERE gr.created_by = ?";
    $answerScopeTypes = "i";
    $answerScopeParams = [$userId];
}

function fetchOne($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return null;
    }

    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row;
}

function fetchAll($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

$summarySql = "
    SELECT
        COUNT(*) AS total_answered,
        COALESCE(SUM(is_correct), 0) AS total_correct
    FROM game_answers ga
    {$answerScopeJoin}
    {$answerScopeWhere}
";

$summary = fetchOne(
    $conn,
    $summarySql,
    $answerScopeTypes,
    $answerScopeParams
) ?? [];

$totalAnswered = (int)($summary["total_answered"] ?? 0);
$totalCorrect = (int)($summary["total_correct"] ?? 0);

$accuracy = [
    "total_correct" => $totalCorrect,
    "total_answered" => $totalAnswered,
    "percentage" => $totalAnswered > 0
        ? round(($totalCorrect / $totalAnswered) * 100, 2)
        : 0
];

$difficultyRows = fetchAll(
    $conn,
    "
        SELECT
            ROUND(COALESCE(ga.difficulty_level, 1.0), 1) AS difficulty,
            COUNT(*) AS total_questions,
            COALESCE(SUM(ga.is_correct), 0) AS total_correct
        FROM game_answers ga
        {$answerScopeJoin}
        {$answerScopeWhere}
        GROUP BY ROUND(COALESCE(ga.difficulty_level, 1.0), 1)
        ORDER BY difficulty ASC
    ",
    $answerScopeTypes,
    $answerScopeParams
);

$difficultyStats = [];

foreach ($difficultyRows as $row) {
    $questions = (int)$row["total_questions"];
    $correct = (int)$row["total_correct"];

    $difficultyStats[] = [
        "difficulty" => round((float)$row["difficulty"], 1) . " / 5",
        "correct" => $correct,
        "questions" => $questions,
        "percentage" => $questions > 0
            ? round(($correct / $questions) * 100, 2)
            : 0
    ];
}

if ($isSuperAdmin) {
    $topPlayersSql = "
        SELECT
            COALESCE(NULLIF(ga.player_name, ''), u.name, CONCAT('Usuario #', ga.user_id), ?) AS player_name,
            COALESCE(SUM(ga.score_earned), 0) AS best_score,
            COALESCE(SUM(ga.is_correct), 0) AS total_correct
        FROM game_answers ga
        LEFT JOIN users u ON ga.user_id = u.id
        GROUP BY COALESCE(NULLIF(ga.player_name, ''), u.name, CONCAT('Usuario #', ga.user_id))
        ORDER BY best_score DESC, total_correct DESC
        LIMIT 10
    ";
    $topPlayers = fetchAll($conn, $topPlayersSql, "s", [t("unnamed_player")]);
} else {
    $topPlayersSql = "
        SELECT
            COALESCE(NULLIF(ga.player_name, ''), u.name, CONCAT('Usuario #', ga.user_id), ?) AS player_name,
            COALESCE(SUM(ga.score_earned), 0) AS best_score,
            COALESCE(SUM(ga.is_correct), 0) AS total_correct
        FROM game_answers ga
        LEFT JOIN users u ON ga.user_id = u.id
        LEFT JOIN game_rooms gr ON ga.room_id = gr.id
        WHERE gr.created_by = ?
        GROUP BY COALESCE(NULLIF(ga.player_name, ''), u.name, CONCAT('Usuario #', ga.user_id), ?)
        ORDER BY best_score DESC, total_correct DESC
        LIMIT 10
    ";
    $topPlayers = fetchAll(
        $conn,
        $topPlayersSql,
        "sis",
        [t("unnamed_player"), $userId, t("unnamed_player")]
    );
}

$topPlayers = array_map(
    static function ($row) {
        return [
            "player_name" => $row["player_name"],
            "best_score" => (int)$row["best_score"],
            "total_correct" => (int)$row["total_correct"]
        ];
    },
    $topPlayers
);

echo json_encode([
    "success" => true,
    "data" => [
        "accuracy" => $accuracy,
        "difficulty_stats" => $difficultyStats,
        "top_players" => $topPlayers,
        "no_data_message" => t("no_data_available")
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
