<?php
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

$userId = (int)$_SESSION["user_id"];
$isSuperAdmin = is_super_admin();

$data = [];

function getCount($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($types !== "" && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row["total"] ?? 0);
}

if ($isSuperAdmin) {
    $data["total_users"] = getCount($conn, "SELECT COUNT(*) AS total FROM users");
    $data["total_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions");
    $data["verified_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions WHERE status = 'verified'");
    $data["pending_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions WHERE status = 'pending'");
    $data["total_games"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_results");
    $data["total_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms");
    $data["active_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms WHERE status = 'waiting'");
    $data["started_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms WHERE status = 'started'");

    $avgSql = "
        SELECT 
            COALESCE(AVG(score), 0) AS avg_score,
            COALESCE(AVG(final_difficulty), 1.0) AS avg_difficulty
        FROM game_results
    ";

    $avgResult = $conn->query($avgSql);
    $avgRow = $avgResult->fetch_assoc();

} else {
    $data["total_users"] = 0;
    $data["total_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions");
    $data["verified_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions WHERE status = 'verified'");
    $data["pending_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions WHERE status = 'pending'");
    $data["total_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms WHERE created_by = ?", "i", [$userId]);
    $data["active_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms WHERE status = 'waiting' AND created_by = ?", "i", [$userId]);
    $data["started_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms WHERE status = 'started' AND created_by = ?", "i", [$userId]);

    $sqlTeacherGames = "
        SELECT COUNT(gr.id) AS total
        FROM game_results gr
        INNER JOIN game_rooms r ON gr.room_id = r.id
        WHERE r.created_by = ?
    ";

    $data["total_games"] = getCount($conn, $sqlTeacherGames, "i", [$userId]);

    $avgStmt = $conn->prepare("
        SELECT 
            COALESCE(AVG(gr.score), 0) AS avg_score,
            COALESCE(AVG(gr.final_difficulty), 1.0) AS avg_difficulty
        FROM game_results gr
        INNER JOIN game_rooms r ON gr.room_id = r.id
        WHERE r.created_by = ?
    ");

    $avgStmt->bind_param("i", $userId);
    $avgStmt->execute();
    $avgRow = $avgStmt->get_result()->fetch_assoc();
    $avgStmt->close();
}

$data["avg_score"] = round((float)($avgRow["avg_score"] ?? 0), 2);
$data["avg_difficulty"] = round((float)($avgRow["avg_difficulty"] ?? 1.0), 1);

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>