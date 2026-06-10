<?php

function grantBadge(
    mysqli $conn,
    int $userId,
    string $badgeKey,
    string $badgeName,
    string $badgeDescription
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
            badge_description
        )
        VALUES (?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($insertSql);

    $stmt->bind_param(
        "isss",
        $userId,
        $badgeKey,
        $badgeName,
        $badgeDescription
    );

    $stmt->execute();
    $stmt->close();

    return [
        "badge_key" => $badgeKey,
        "badge_name" => $badgeName,
        "badge_description" => $badgeDescription
    ];
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
            "🎮 First Game",
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
            "📚 10 Answers",
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
            "🔥 100 Answers",
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
            "🎯 Precision Master",
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
            "🏆 Elite Precision",
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
            "⚡ Fast Thinker",
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
            "📈 Advanced Player",
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
            "🔥 Difficulty Master",
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
                "🧠 Category Expert",
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
?>