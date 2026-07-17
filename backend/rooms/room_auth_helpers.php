<?php

require_once __DIR__ . '/../support/api_response.php';

function room_json_response(array $payload): void {
    api_response($payload);
}

function require_room_owner_or_super_admin(mysqli $conn, string $roomCode): array {
    $roomCode = strtoupper(trim($roomCode));

    if ($roomCode === "") {
        room_json_response([
            "success" => false,
            "message" => "Codigo de sala vacio"
        ]);
    }

    $stmt = $conn->prepare("
        SELECT *
        FROM game_rooms
        WHERE room_code = ?
        LIMIT 1
    ");

    if (!$stmt) {
        room_json_response([
            "success" => false,
            "message" => "Error al validar sala",
            "error" => app_error_detail($conn->error)
        ]);
    }

    $stmt->bind_param("s", $roomCode);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$room) {
        room_json_response([
            "success" => false,
            "message" => "Sala no encontrada"
        ]);
    }

    if (!is_super_admin() && (int)($room["created_by"] ?? 0) !== current_user_id()) {
        room_json_response([
            "success" => false,
            "message" => "No tienes permisos para modificar esta sala"
        ]);
    }

    return $room;
}
