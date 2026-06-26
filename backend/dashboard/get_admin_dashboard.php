<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../badges/evaluate_badges.php';
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../badges/badge_translations.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION["user_id"];
$isSuperAdmin = is_super_admin();
$requestedTeacherId = (int)($_GET["user_id"] ?? 0);

if ($isSuperAdmin && $requestedTeacherId > 0) {
    $userId = $requestedTeacherId;
    $isSuperAdmin = false;
}

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

function getOpenRooms($conn, $isSuperAdmin, $userId) {
    $sql = "
        SELECT
            gr.id,
            gr.room_code,
            gr.name,
            gr.status,
            gr.question_count,
            gr.created_at,
            COUNT(DISTINCT rp.player_name) AS total_players
        FROM game_rooms gr
        LEFT JOIN room_players rp ON rp.room_id = gr.id
        WHERE gr.status IN ('waiting', 'started', 'paused')
    ";

    $types = "";
    $params = [];

    if (!$isSuperAdmin) {
        $sql .= " AND gr.created_by = ?";
        $types = "i";
        $params[] = $userId;
    }

    $sql .= "
        GROUP BY gr.id, gr.room_code, gr.name, gr.status, gr.question_count, gr.created_at
        ORDER BY gr.created_at DESC
        LIMIT 8
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = [];

    while ($row = $result->fetch_assoc()) {
        $rooms[] = [
            "room_code" => $row["room_code"],
            "name" => $row["name"],
            "status" => $row["status"],
            "question_count" => (int)$row["question_count"],
            "total_players" => (int)$row["total_players"],
            "created_at" => $row["created_at"]
        ];
    }

    $stmt->close();

    return $rooms;
}

function getTeacherBadges($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT
            badge_key,
            badge_name,
            badge_description,
            badge_icon,
            earned_at
        FROM user_badges
        WHERE user_id = ?
          AND badge_key LIKE 'teacher_%'
        ORDER BY earned_at DESC
    ");

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $badges = [];

    while ($row = $result->fetch_assoc()) {
        $badges[] = translate_badge_payload([
            "badge_key" => $row["badge_key"],
            "badge_name" => $row["badge_name"],
            "badge_description" => $row["badge_description"],
            "badge_icon" => $row["badge_icon"],
            "earned_at" => $row["earned_at"]
        ]);
    }

    $stmt->close();

    return $badges;
}

if ($isSuperAdmin) {
    $data["total_users"] = getCount($conn, "SELECT COUNT(*) AS total FROM users");
    $data["total_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions");
    $data["verified_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions WHERE status = 'verified'");
    $data["pending_questions"] = getCount($conn, "SELECT COUNT(*) AS total FROM questions WHERE status = 'pending'");
    $data["total_games"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_results");
    $data["total_rooms"] = getCount($conn, "SELECT COUNT(*) AS total FROM game_rooms");
    $data["total_participants"] = getCount($conn, "SELECT COUNT(*) AS total FROM room_players");
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
    $data["total_participants"] = getCount(
        $conn,
        "SELECT COUNT(rp.id) AS total FROM room_players rp INNER JOIN game_rooms r ON rp.room_id = r.id WHERE r.created_by = ?",
        "i",
        [$userId]
    );
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
$data["open_rooms"] = getOpenRooms($conn, $isSuperAdmin, $userId);
$data["new_teacher_badges"] = evaluateTeacherBadges($conn, $userId);
$data["teacher_badges"] = getTeacherBadges($conn, $userId);

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
