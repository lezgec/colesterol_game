<?php

require_once __DIR__ . '/../game/streak_helpers.php';
require_once __DIR__ . '/badge_translations.php';

function grantBadge(
    mysqli $conn,
    int $userId,
    string $badgeKey,
    string $badgeName,
    string $badgeDescription,
    ?string $badgeIcon = null
) {

    $checkSql = "
        SELECT id
        FROM user_badges
        WHERE user_id = ?
          AND badge_key = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("is", $userId, $badgeKey);
    $stmt->execute();

    $exists =
        $stmt
            ->get_result()
            ->num_rows > 0;

    $stmt->close();

    if ($exists) {
        return null;
    }

    $insertSql = "
        INSERT INTO user_badges (
            user_id,
            badge_key,
            badge_name,
            badge_description,
            badge_icon
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($insertSql);

    $stmt->bind_param(
        "issss",
        $userId,
        $badgeKey,
        $badgeName,
        $badgeDescription,
        $badgeIcon
    );

    $stmt->execute();
    $stmt->close();

    return translate_badge_payload([
        "badge_key" => $badgeKey,
        "badge_name" => $badgeName,
        "badge_description" => $badgeDescription,
        "badge_icon" => $badgeIcon
    ]);
}

function getTeacherMetric(mysqli $conn, string $sql, int $teacherId): array {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $teacherId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return $row;
}

function evaluateTeacherBadges(mysqli $conn, int $teacherId) {

    $newBadges = [];

    $rooms = getTeacherMetric(
        $conn,
        "
            SELECT
                COUNT(*) AS total_rooms,
                SUM(CASE WHEN status IN ('waiting', 'started', 'paused') THEN 1 ELSE 0 END) AS open_rooms,
                SUM(CASE WHEN status IN ('started', 'paused', 'finished') OR started_at IS NOT NULL THEN 1 ELSE 0 END) AS launched_rooms,
                SUM(CASE WHEN status = 'finished' OR finished_at IS NOT NULL THEN 1 ELSE 0 END) AS finished_rooms,
                SUM(CASE WHEN question_mode IN ('configured', 'selected') THEN 1 ELSE 0 END) AS curated_rooms,
                SUM(CASE WHEN question_count >= 20 THEN 1 ELSE 0 END) AS extended_rooms
            FROM game_rooms
            WHERE created_by = ?
        ",
        $teacherId
    );

    $totalRooms = (int)($rooms["total_rooms"] ?? 0);
    $openRooms = (int)($rooms["open_rooms"] ?? 0);
    $launchedRooms = (int)($rooms["launched_rooms"] ?? 0);
    $finishedRooms = (int)($rooms["finished_rooms"] ?? 0);
    $curatedRooms = (int)($rooms["curated_rooms"] ?? 0);
    $extendedRooms = (int)($rooms["extended_rooms"] ?? 0);

    $participants = getTeacherMetric(
        $conn,
        "
            SELECT COUNT(*) AS total_participants
            FROM room_players rp
            INNER JOIN game_rooms gr ON rp.room_id = gr.id
            WHERE gr.created_by = ?
        ",
        $teacherId
    );

    $totalParticipants = (int)($participants["total_participants"] ?? 0);

    $answers = getTeacherMetric(
        $conn,
        "
            SELECT
                COUNT(ga.id) AS total_answers,
                COALESCE(SUM(ga.is_correct), 0) AS correct_answers
            FROM game_answers ga
            INNER JOIN game_rooms gr ON ga.room_id = gr.id
            WHERE gr.created_by = ?
        ",
        $teacherId
    );

    $totalAnswers = (int)($answers["total_answers"] ?? 0);
    $correctAnswers = (int)($answers["correct_answers"] ?? 0);
    $accuracy = $totalAnswers > 0
        ? round(($correctAnswers / $totalAnswers) * 100, 2)
        : 0;

    $highAccuracyRooms = getTeacherMetric(
        $conn,
        "
            SELECT COUNT(*) AS total_rooms
            FROM (
                SELECT gr.id
                FROM game_rooms gr
                INNER JOIN game_answers ga ON ga.room_id = gr.id
                WHERE gr.created_by = ?
                GROUP BY gr.id
                HAVING COUNT(ga.id) >= 10
                   AND (SUM(ga.is_correct) / COUNT(ga.id)) >= 0.8
            ) room_scores
        ",
        $teacherId
    );

    $totalHighAccuracyRooms = (int)($highAccuracyRooms["total_rooms"] ?? 0);

    $rules = [
        [
            "condition" => $totalRooms >= 1,
            "key" => "teacher_first_room",
            "name" => "Primera sala docente",
            "description" => "Creo su primera sala de aprendizaje.",
            "icon" => "school"
        ],
        [
            "condition" => $totalRooms >= 5,
            "key" => "teacher_room_builder_5",
            "name" => "Constructor de aulas",
            "description" => "Creo 5 salas para sus estudiantes.",
            "icon" => "home"
        ],
        [
            "condition" => $openRooms >= 1,
            "key" => "teacher_open_room",
            "name" => "Aula disponible",
            "description" => "Tiene una sala abierta, iniciada o pausada.",
            "icon" => "home"
        ],
        [
            "condition" => $launchedRooms >= 1,
            "key" => "teacher_launched_room",
            "name" => "Clase en marcha",
            "description" => "Inicio una sala con estudiantes.",
            "icon" => "rocket"
        ],
        [
            "condition" => $finishedRooms >= 1,
            "key" => "teacher_finished_room",
            "name" => "Cierre completo",
            "description" => "Finalizo una sala y dejo resultados listos para analizar.",
            "icon" => "check"
        ],
        [
            "condition" => $curatedRooms >= 1,
            "key" => "teacher_curated_room",
            "name" => "Curador de retos",
            "description" => "Diseno una sala con preguntas configuradas o seleccionadas.",
            "icon" => "target"
        ],
        [
            "condition" => $extendedRooms >= 1,
            "key" => "teacher_extended_room",
            "name" => "Sesion profunda",
            "description" => "Creo una sala de 20 preguntas o mas.",
            "icon" => "file"
        ],
        [
            "condition" => $totalParticipants >= 10,
            "key" => "teacher_engagement_10",
            "name" => "Comunidad activa",
            "description" => "Reunio 10 participaciones en sus salas.",
            "icon" => "users"
        ],
        [
            "condition" => $totalAnswers >= 50,
            "key" => "teacher_answers_50",
            "name" => "Aula participativa",
            "description" => "Sus salas acumularon 50 respuestas.",
            "icon" => "analytics"
        ],
        [
            "condition" => $accuracy >= 80 && $totalAnswers >= 20,
            "key" => "teacher_accuracy_80",
            "name" => "Guia de precision",
            "description" => "Sus estudiantes alcanzaron 80% de precision global con al menos 20 respuestas.",
            "icon" => "target"
        ],
        [
            "condition" => $totalHighAccuracyRooms >= 1,
            "key" => "teacher_high_accuracy_room",
            "name" => "Sala destacada",
            "description" => "Una de sus salas alcanzo 80% de precision con al menos 10 respuestas.",
            "icon" => "star"
        ]
    ];

    foreach ($rules as $rule) {
        if (!$rule["condition"]) {
            continue;
        }

        $badge = grantBadge(
            $conn,
            $teacherId,
            $rule["key"],
            $rule["name"],
            $rule["description"],
            $rule["icon"]
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    return $newBadges;
}

function evaluateBadges(mysqli $conn, int $userId) {

    $newBadges = [];

    /*
    |--------------------------------------------------------------------------
    | GLOBAL STATS
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            COUNT(*) AS total_answers,

            COALESCE(SUM(is_correct), 0)
                AS correct_answers,

            COALESCE(
                ROUND(
                    (SUM(is_correct) / COUNT(*)) * 100,
                    2
                ),
                0
            ) AS accuracy,

            COALESCE(
                ROUND(AVG(response_time), 2),
                0
            ) AS avg_response_time,

            COALESCE(
                ROUND(AVG(difficulty_level), 2),
                0
            ) AS avg_difficulty,

            COALESCE(
                MAX(difficulty_level),
                0
            ) AS max_difficulty

        FROM game_answers

        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $stats =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    $totalAnswers =
        (int)$stats["total_answers"];

    $accuracy =
        (float)$stats["accuracy"];

    $avgTime =
        (float)$stats["avg_response_time"];

    $avgDifficulty =
        (float)$stats["avg_difficulty"];

    $maxDifficulty =
        (float)$stats["max_difficulty"];

    $streaks = get_player_streak_summary($conn, $userId);

    /*
    |--------------------------------------------------------------------------
    | STREAK BADGES
    |--------------------------------------------------------------------------
    */

    $streakRules = [
        [
            "condition" => $streaks["best_correct_streak"] >= 3,
            "key" => "correct_streak_3",
            "name" => "Racha x3",
            "description" => "Answered 3 questions correctly in a row.",
            "icon" => "zap"
        ],
        [
            "condition" => $streaks["best_correct_streak"] >= 5,
            "key" => "correct_streak_5",
            "name" => "Racha x5",
            "description" => "Answered 5 questions correctly in a row.",
            "icon" => "zap"
        ],
        [
            "condition" => $streaks["best_daily_streak"] >= 3,
            "key" => "daily_streak_3",
            "name" => "Constancia x3",
            "description" => "Played on 3 consecutive days.",
            "icon" => "calendar"
        ],
        [
            "condition" => $streaks["best_daily_streak"] >= 7,
            "key" => "daily_streak_7",
            "name" => "Semana activa",
            "description" => "Played on 7 consecutive days.",
            "icon" => "medal"
        ]
    ];

    foreach ($streakRules as $rule) {
        if (!$rule["condition"]) {
            continue;
        }

        $badge = grantBadge(
            $conn,
            $userId,
            $rule["key"],
            $rule["name"],
            $rule["description"],
            $rule["icon"]
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FIRST GAME
    |--------------------------------------------------------------------------
    */

    if ($totalAnswers >= 1) {

        $badge = grantBadge(
            $conn,
            $userId,
            "first_game",
            "First Game",
            "Completed the first game."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY BADGES
    |--------------------------------------------------------------------------
    */

    if ($totalAnswers >= 10) {

        $badge = grantBadge(
            $conn,
            $userId,
            "ten_answers",
            "10 Answers",
            "Answered 10 questions."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    if ($totalAnswers >= 100) {

        $badge = grantBadge(
            $conn,
            $userId,
            "hundred_answers",
            "100 Answers",
            "Answered 100 questions."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRECISION BADGES
    |--------------------------------------------------------------------------
    */

    if ($accuracy >= 80) {

        $badge = grantBadge(
            $conn,
            $userId,
            "precision_80",
            "Precision Master",
            "Reached 80% precision."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    if ($accuracy >= 90) {

        $badge = grantBadge(
            $conn,
            $userId,
            "precision_90",
            "Elite Precision",
            "Reached 90% precision."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SPEED BADGES
    |--------------------------------------------------------------------------
    */

    if ($avgTime > 0 && $avgTime <= 4) {

        $badge = grantBadge(
            $conn,
            $userId,
            "fast_responder",
            "Fast Thinker",
            "Average response time below 4 seconds."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DIFFICULTY BADGES
    |--------------------------------------------------------------------------
    */

    if ($avgDifficulty >= 3.5) {

        $badge = grantBadge(
            $conn,
            $userId,
            "advanced_player",
            "Advanced Player",
            "Maintained high adaptive difficulty."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    if ($maxDifficulty >= 4.5) {

        $badge = grantBadge(
            $conn,
            $userId,
            "difficulty_master",
            "Difficulty Master",
            "Reached adaptive difficulty 4.5."
        );

        if ($badge) {
            $newBadges[] = $badge;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY EXPERT
    |--------------------------------------------------------------------------
    */

    $categorySql = "
        SELECT
            q.category,

            ROUND(
                (SUM(ga.is_correct) / COUNT(*)) * 100,
                2
            ) AS accuracy,

            COUNT(*) AS total_answers

        FROM game_answers ga

        INNER JOIN questions q
            ON ga.question_id = q.id

        WHERE ga.user_id = ?

        GROUP BY q.category
    ";

    $stmt = $conn->prepare($categorySql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $categories =
        $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    foreach ($categories as $category) {

        $catAccuracy =
            (float)$category["accuracy"];

        $catAnswers =
            (int)$category["total_answers"];

        if (
            $catAccuracy >= 90 &&
            $catAnswers >= 10
        ) {

            $badgeKey =
                "expert_" .
                strtolower(
                    preg_replace(
                        '/[^a-zA-Z0-9]/',
                        '_',
                        $category["category"]
                    )
                );

            $badge = grantBadge(
                $conn,
                $userId,
                $badgeKey,
                "Category Expert",
                "Mastered category: " .
                $category["category"]
            );

            if ($badge) {
                $newBadges[] = $badge;
            }
        }
    }

    return $newBadges;
}
