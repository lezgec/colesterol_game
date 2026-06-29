<?php
function ensure_room_question_requirements_table(mysqli $conn): void {
    $result = $conn->query("SHOW TABLES LIKE 'room_question_requirements'");

    if (!$result || $result->num_rows === 0) {
        throw new Exception("Falta aplicar la migracion de room_question_requirements");
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
