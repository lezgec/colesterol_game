<?php
function ensure_adaptive_difficulty_columns(mysqli $conn): void {
    static $checked = false;

    if ($checked) {
        return;
    }

    require_decimal_column(
        $conn,
        "game_answers",
        "difficulty_level"
    );

    require_decimal_column(
        $conn,
        "game_results",
        "final_difficulty"
    );

    $checked = true;
}

function require_decimal_column(mysqli $conn, string $table, string $column): void {
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

    if (!$isDecimal) {
        throw new Exception("Falta aplicar migracion de dificultad adaptativa para {$table}.{$column}");
    }
}
?>
