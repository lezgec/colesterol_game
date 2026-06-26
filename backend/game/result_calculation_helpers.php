<?php

function normalize_game_difficulty($difficulty) {
    $difficulty = round((float)$difficulty, 1);

    if ($difficulty < 1) {
        return 1.0;
    }

    if ($difficulty > 5) {
        return 5.0;
    }

    return $difficulty;
}

function calculate_answer_points($isCorrect, $responseTime) {
    if (!$isCorrect) {
        return 0;
    }

    $responseTime = max(0, (int)$responseTime);

    if ($responseTime <= 3) {
        return 20;
    }

    if ($responseTime <= 6) {
        return 15;
    }

    return 10;
}

function calculate_result_from_answers(mysqli $conn, $filters) {
    $conditions = [];
    $params = [];
    $types = '';

    if (isset($filters['user_id'])) {
        $conditions[] = 'user_id = ?';
        $params[] = (int)$filters['user_id'];
        $types .= 'i';
    }

    if (array_key_exists('room_id', $filters)) {
        if ($filters['room_id'] === null) {
            $conditions[] = 'room_id IS NULL';
        } else {
            $conditions[] = 'room_id = ?';
            $params[] = (int)$filters['room_id'];
            $types .= 'i';
        }
    }

    if (!empty($filters['player_name'])) {
        $conditions[] = 'player_name = ?';
        $params[] = (string)$filters['player_name'];
        $types .= 's';
    }

    if (empty($conditions)) {
        return [
            'score' => 0,
            'correct_answers' => 0,
            'total_questions' => 0,
            'final_difficulty' => 1.0
        ];
    }

    $sql = "
        SELECT
            COALESCE(SUM(score_earned), 0) AS score,
            COALESCE(SUM(is_correct), 0) AS correct_answers,
            COUNT(*) AS total_questions,
            COALESCE(MAX(difficulty_level), 1) AS final_difficulty
        FROM game_answers
        WHERE " . implode(' AND ', $conditions);

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'score' => 0,
            'correct_answers' => 0,
            'total_questions' => 0,
            'final_difficulty' => 1.0
        ];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        'score' => (int)($row['score'] ?? 0),
        'correct_answers' => (int)($row['correct_answers'] ?? 0),
        'total_questions' => (int)($row['total_questions'] ?? 0),
        'final_difficulty' => normalize_game_difficulty($row['final_difficulty'] ?? 1)
    ];
}

function calculate_recent_result_from_answers(mysqli $conn, $filters, $limit) {
    $limit = max(1, (int)$limit);
    $conditions = [];
    $params = [];
    $types = '';

    if (isset($filters['user_id'])) {
        $conditions[] = 'user_id = ?';
        $params[] = (int)$filters['user_id'];
        $types .= 'i';
    }

    if (array_key_exists('room_id', $filters)) {
        if ($filters['room_id'] === null) {
            $conditions[] = 'room_id IS NULL';
        } else {
            $conditions[] = 'room_id = ?';
            $params[] = (int)$filters['room_id'];
            $types .= 'i';
        }
    }

    if (!empty($filters['player_name'])) {
        $conditions[] = 'player_name = ?';
        $params[] = (string)$filters['player_name'];
        $types .= 's';
    }

    if (empty($conditions)) {
        return [
            'score' => 0,
            'correct_answers' => 0,
            'total_questions' => 0,
            'final_difficulty' => 1.0
        ];
    }

    $sql = "
        SELECT
            COALESCE(SUM(score_earned), 0) AS score,
            COALESCE(SUM(is_correct), 0) AS correct_answers,
            COUNT(*) AS total_questions,
            COALESCE(MAX(difficulty_level), 1) AS final_difficulty
        FROM (
            SELECT score_earned, is_correct, difficulty_level
            FROM game_answers
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY id DESC
            LIMIT ?
        ) recent_answers
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'score' => 0,
            'correct_answers' => 0,
            'total_questions' => 0,
            'final_difficulty' => 1.0
        ];
    }

    $params[] = $limit;
    $types .= 'i';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return [
        'score' => (int)($row['score'] ?? 0),
        'correct_answers' => (int)($row['correct_answers'] ?? 0),
        'total_questions' => (int)($row['total_questions'] ?? 0),
        'final_difficulty' => normalize_game_difficulty($row['final_difficulty'] ?? 1)
    ];
}
