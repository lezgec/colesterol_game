<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/room_auth_helpers.php';
require_once __DIR__ . '/../questions/question_workflow_helpers.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$lang = $_GET["lang"] ?? "es";
$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if (!in_array($lang, ["es", "en"], true)) {
    $lang = "es";
}

ensure_question_workflow_columns($conn);
$accessSql = playable_question_access_sql("");
$roomId = 0;

if ($roomCode !== "") {
    $roomData = require_room_owner_or_super_admin($conn, $roomCode);
    $roomId = (int)$roomData["id"];
}

$sql = "SELECT
            id,
            question,
            category,
            difficulty_level,
            language,
            status,
            origin,
            is_active,
            CASE WHEN ? > 0 AND EXISTS (
                SELECT 1
                FROM room_questions rq
                WHERE rq.room_id = ?
                  AND rq.question_id = questions.id
            ) THEN 1 ELSE 0 END AS assigned_to_room
        FROM questions
        WHERE
            language = ?
            AND status = 'verified'
            AND is_active = 1
            AND {$accessSql}
        ORDER BY difficulty_level ASC, id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("iis", $roomId, $roomId, $lang);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = [
        "id" => (int)$row["id"],
        "question" => $row["question"],
        "category" => $row["category"],
        "difficulty_level" => (int)round((float)$row["difficulty_level"]),
        "language" => $row["language"],
        "status" => $row["status"],
        "origin" => $row["origin"],
        "is_active" => (int)$row["is_active"],
        "assigned_to_room" => (int)$row["assigned_to_room"]
    ];
}

echo json_encode($questions, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
