<?php

function ensure_user_streak_columns(mysqli $conn): void {
    $columns = [
        "current_correct_streak" => "INT NOT NULL DEFAULT 0",
        "best_correct_streak" => "INT NOT NULL DEFAULT 0",
        "current_daily_streak" => "INT NOT NULL DEFAULT 0",
        "best_daily_streak" => "INT NOT NULL DEFAULT 0",
        "last_played_date" => "DATE NULL"
    ];

    $existing = [];
    $result = $conn->query("SHOW COLUMNS FROM users");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[$row["Field"]] = true;
        }
    }

    $previousColumn = "bio";

    foreach ($columns as $column => $definition) {
        if (!isset($existing[$column])) {
            $afterClause = isset($existing[$previousColumn])
                ? " AFTER {$previousColumn}"
                : "";

            $conn->query("ALTER TABLE users ADD COLUMN {$column} {$definition}{$afterClause}");
            $existing[$column] = true;
        }

        $previousColumn = $column;
    }
}

function register_player_answer_streak(mysqli $conn, ?int $userId, int $isCorrect): void {
    if (!$userId || $userId <= 0) {
        return;
    }

    ensure_user_streak_columns($conn);

    $stmt = $conn->prepare("
        SELECT
            current_correct_streak,
            best_correct_streak,
            current_daily_streak,
            best_daily_streak,
            last_played_date
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return;
    }

    $currentCorrectStreak = $isCorrect === 1
        ? ((int)$user["current_correct_streak"] + 1)
        : 0;

    $bestCorrectStreak = max(
        (int)$user["best_correct_streak"],
        $currentCorrectStreak
    );

    $today = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 day"));
    $lastPlayedDate = $user["last_played_date"] ?? null;
    $currentDailyStreak = (int)$user["current_daily_streak"];

    if ($lastPlayedDate === $today) {
        $currentDailyStreak = max(1, $currentDailyStreak);
    } elseif ($lastPlayedDate === $yesterday) {
        $currentDailyStreak += 1;
    } else {
        $currentDailyStreak = 1;
    }

    $bestDailyStreak = max(
        (int)$user["best_daily_streak"],
        $currentDailyStreak
    );

    $update = $conn->prepare("
        UPDATE users
        SET
            current_correct_streak = ?,
            best_correct_streak = ?,
            current_daily_streak = ?,
            best_daily_streak = ?,
            last_played_date = ?
        WHERE id = ?
    ");

    if (!$update) {
        return;
    }

    $update->bind_param(
        "iiiisi",
        $currentCorrectStreak,
        $bestCorrectStreak,
        $currentDailyStreak,
        $bestDailyStreak,
        $today,
        $userId
    );
    $update->execute();
    $update->close();
}

function get_player_streak_summary(mysqli $conn, int $userId): array {
    ensure_user_streak_columns($conn);
    backfill_player_streaks_from_answers($conn, $userId);

    $stmt = $conn->prepare("
        SELECT
            current_correct_streak,
            best_correct_streak,
            current_daily_streak,
            best_daily_streak,
            last_played_date
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return [
            "current_correct_streak" => 0,
            "best_correct_streak" => 0,
            "current_daily_streak" => 0,
            "best_daily_streak" => 0,
            "last_played_date" => null
        ];
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        "current_correct_streak" => (int)($row["current_correct_streak"] ?? 0),
        "best_correct_streak" => (int)($row["best_correct_streak"] ?? 0),
        "current_daily_streak" => (int)($row["current_daily_streak"] ?? 0),
        "best_daily_streak" => (int)($row["best_daily_streak"] ?? 0),
        "last_played_date" => $row["last_played_date"] ?? null
    ];
}

function backfill_player_streaks_from_answers(mysqli $conn, int $userId): void {
    $check = $conn->prepare("
        SELECT
            current_correct_streak,
            best_correct_streak,
            current_daily_streak,
            best_daily_streak
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if (!$check) {
        return;
    }

    $check->bind_param("i", $userId);
    $check->execute();
    $stored = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$stored) {
        return;
    }

    $hasStoredStreaks =
        (int)$stored["best_correct_streak"] > 0 ||
        (int)$stored["best_daily_streak"] > 0;

    if ($hasStoredStreaks) {
        return;
    }

    $answers = $conn->prepare("
        SELECT is_correct, DATE(answered_at) AS played_date
        FROM game_answers
        WHERE user_id = ?
        ORDER BY answered_at ASC, id ASC
    ");

    if (!$answers) {
        return;
    }

    $answers->bind_param("i", $userId);
    $answers->execute();
    $result = $answers->get_result();

    $currentCorrectStreak = 0;
    $bestCorrectStreak = 0;
    $dates = [];

    while ($row = $result->fetch_assoc()) {
        if ((int)$row["is_correct"] === 1) {
            $currentCorrectStreak++;
            $bestCorrectStreak = max($bestCorrectStreak, $currentCorrectStreak);
        } else {
            $currentCorrectStreak = 0;
        }

        if (!empty($row["played_date"])) {
            $dates[$row["played_date"]] = true;
        }
    }

    $answers->close();

    if ($bestCorrectStreak === 0 && empty($dates)) {
        return;
    }

    $sortedDates = array_keys($dates);
    sort($sortedDates);

    $dailyStreak = 0;
    $bestDailyStreak = 0;
    $previousDate = null;

    foreach ($sortedDates as $date) {
        if ($previousDate && $date === date("Y-m-d", strtotime($previousDate . " +1 day"))) {
            $dailyStreak++;
        } else {
            $dailyStreak = 1;
        }

        $bestDailyStreak = max($bestDailyStreak, $dailyStreak);
        $previousDate = $date;
    }

    $lastPlayedDate = !empty($sortedDates) ? end($sortedDates) : null;
    $today = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 day"));
    $currentDailyStreak = in_array($lastPlayedDate, [$today, $yesterday], true)
        ? $dailyStreak
        : 0;

    $update = $conn->prepare("
        UPDATE users
        SET
            current_correct_streak = ?,
            best_correct_streak = ?,
            current_daily_streak = ?,
            best_daily_streak = ?,
            last_played_date = ?
        WHERE id = ?
    ");

    if (!$update) {
        return;
    }

    $update->bind_param(
        "iiiisi",
        $currentCorrectStreak,
        $bestCorrectStreak,
        $currentDailyStreak,
        $bestDailyStreak,
        $lastPlayedDate,
        $userId
    );
    $update->execute();
    $update->close();
}
