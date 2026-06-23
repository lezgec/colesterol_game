<?php
function ensure_adaptive_difficulty_columns(mysqli $conn): void {
    static $checked = false;

    if ($checked) {
        return;
    }

    ensure_decimal_column(
        $conn,
        "game_answers",
        "difficulty_level",
        "ALTER TABLE game_answers MODIFY difficulty_level DECIMAL(3,1) NOT NULL DEFAULT 1.0"
    );

    ensure_decimal_column(
        $conn,
        "game_results",
        "final_difficulty",
        "ALTER TABLE game_results MODIFY final_difficulty DECIMAL(3,1) NOT NULL DEFAULT 1.0"
    );

    $checked = true;
}

function ensure_decimal_column(mysqli $conn, string $table, string $column, string $alterSql): void {
    $stmt = $conn->prepare("
        SELECT DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $meta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $isDecimal =
        $meta &&
        strtolower((string)$meta["DATA_TYPE"]) === "decimal" &&
        (int)$meta["NUMERIC_SCALE"] >= 1;

    if (!$isDecimal && !$conn->query($alterSql)) {
        throw new Exception($conn->error);
    }
}
?>
