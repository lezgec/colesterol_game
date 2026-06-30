<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limit.php';
require_once __DIR__ . '/../../lang/translate.php';

require_csrf_token();

$data = json_decode(file_get_contents("php://input"), true);

$room_code = strtoupper(trim($data["room_code"] ?? ""));
$player_name = preg_replace('/\s+/u', ' ', trim($data["player_name"] ?? ""));

require_rate_limit($conn, "join-room:" . $room_code, 20, 300);

if ($room_code === "" || $player_name === "") {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, status
    FROM game_rooms
    WHERE room_code = ?
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar sala",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $room_code);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Sala no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room = $result->fetch_assoc();

if ($room["status"] !== "waiting") {
    echo json_encode([
        "success" => false,
        "message" => "La partida ya inició"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$room_id = (int)$room["id"];

$stmt->close();

$check = $conn->prepare("
    SELECT id
    FROM room_players
    WHERE room_id = ?
      AND LOWER(TRIM(player_name)) = LOWER(TRIM(?))
    LIMIT 1
");

if (!$check) {
    echo json_encode([
        "success" => false,
        "message" => "Error al validar jugador",
        "error" => app_error_detail($conn->error)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$check->bind_param("is", $room_id, $player_name);
$check->execute();

$checkResult = $check->get_result();

if ($checkResult->num_rows === 0) {
    $insert = $conn->prepare("
        INSERT INTO room_players
            (room_id, player_name)
        VALUES
            (?, ?)
    ");

    if (!$insert) {
        echo json_encode([
            "success" => false,
            "message" => "Error al insertar jugador",
            "error" => app_error_detail($conn->error)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $insert->bind_param("is", $room_id, $player_name);

    if (!$insert->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "No se pudo unir a la sala",
            "error" => $insert->error
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $insert->close();
} else {
    echo json_encode([
        "success" => false,
        "code" => "duplicate_player_name",
        "message" => t("duplicate_room_player_name")
    ], JSON_UNESCAPED_UNICODE);
    $check->close();
    $conn->close();
    exit;
}

$check->close();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Jugador unido correctamente",
    "room_code" => $room_code,
    "player_name" => $player_name
], JSON_UNESCAPED_UNICODE);
?>
