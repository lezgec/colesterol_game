<?php
function ensure_room_question_requirements_table(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS room_question_requirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            category VARCHAR(100) NOT NULL,
            difficulty_level TINYINT NOT NULL,
            quantity INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY room_category_difficulty (room_id, category, difficulty_level),
            INDEX idx_room_requirements_room (room_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($sql)) {
        throw new Exception($conn->error);
    }
}

function room_requirement_difficulty_bounds(int $difficulty): array {
    if ($difficulty < 1) {
        $difficulty = 1;
    }

    if ($difficulty > 5) {
        $difficulty = 5;
    }

    return [
        (float)$difficulty,
        $difficulty >= 5 ? 5.1 : (float)($difficulty + 1)
    ];
}
?>
