<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$room_code = strtoupper(trim($data["room_code"] ?? ""));

if ($room_code === "") {
    echo json_encode(["success" => false, "message" => "Código vacío"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE game_rooms 
    SET status = 'started', started_at = NOW(), current_question_index = 0
    WHERE room_code = ?
");

$stmt->bind_param("s", $room_code);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Partida iniciada"]);
} else {
    echo json_encode(["success" => false, "message" => "No se pudo iniciar"]);
}
?>