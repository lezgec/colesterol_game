<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$room_code = strtoupper(trim($_GET["code"] ?? ""));

if ($room_code === "") {
    echo json_encode([
        "success" => false,
        "message" => "Código vacío"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    SELECT id 
    FROM game_rooms 
    WHERE room_code = ?
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $room_code);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $result->fetch_assoc();
$room_id = (int)$room["id"];

$stmt->close();

$sql = "
    SELECT 
        player_name,
        joined_at
    FROM room_players
    WHERE room_id = ?
    ORDER BY joined_at ASC
";

$stmtPlayers = $conn->prepare($sql);

if (!$stmtPlayers) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar jugadores",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmtPlayers->bind_param("i", $room_id);
$stmtPlayers->execute();

$playersResult = $stmtPlayers->get_result();

$players = [];

while ($row = $playersResult->fetch_assoc()) {
    $players[] = [
        "player_name" => $row["player_name"],
        "joined_at" => $row["joined_at"]
    ];
}

echo json_encode($players, JSON_UNESCAPED_UNICODE);

$stmtPlayers->close();
$conn->close();
?>