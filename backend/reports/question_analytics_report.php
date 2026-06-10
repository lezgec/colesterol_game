<?php
session_start();

ini_set('display_errors', 1);
ini_set("precision", "10");
ini_set("serialize_precision", "-1");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["teacher", "super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "
    SELECT
        q.id,
        q.question,
        q.category,
        q.difficulty_level,

        COUNT(ga.id) AS total_answers,

        COALESCE(SUM(ga.is_correct), 0) AS correct_answers,

        (
            COUNT(ga.id) -
            COALESCE(SUM(ga.is_correct), 0)
        ) AS incorrect_answers,

        COALESCE(
            AVG(ga.response_time),
            0
        ) AS avg_response_time,

        COALESCE(
            AVG(ga.difficulty_level),
            0
        ) AS avg_adaptive_difficulty,

        COALESCE(
            SUM(ga.score_earned),
            0
        ) AS total_points

    FROM questions q

    LEFT JOIN game_answers ga
        ON ga.question_id = q.id

    GROUP BY
        q.id,
        q.question,
        q.category,
        q.difficulty_level

    ORDER BY total_answers DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error obteniendo analítica",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$questions = [];

while ($row = $result->fetch_assoc()) {

    $totalAnswers =
        (int)$row["total_answers"];

    $correctAnswers =
        (int)$row["correct_answers"];

    $incorrectAnswers =
        (int)$row["incorrect_answers"];

    $precision =
        $totalAnswers > 0
            ? round(
                ($correctAnswers / $totalAnswers) * 100,
                2
            )
            : 0;

    $failureRate =
        $totalAnswers > 0
            ? round(
                ($incorrectAnswers / $totalAnswers) * 100,
                2
            )
            : 0;

    $questions[] = [

        "question_id" =>
            (int)$row["id"],

        "question" =>
            $row["question"],

        "category" =>
            $row["category"],

        "base_difficulty" =>
            round(
                (float)$row["difficulty_level"],
                1
            ),

        "total_answers" =>
            $totalAnswers,

        "correct_answers" =>
            $correctAnswers,

        "incorrect_answers" =>
            $incorrectAnswers,

        "precision" =>
            $precision,

        "failure_rate" =>
            $failureRate,

        "avg_response_time" =>
            round(
                (float)$row["avg_response_time"],
                2
            ),

        "avg_adaptive_difficulty" =>
            round(
                (float)$row["avg_adaptive_difficulty"],
                1
            ),

        "total_points" =>
            (int)$row["total_points"]
    ];
}
$answeredQuestions = array_filter($questions, function($q) {
    return $q["total_answers"] > 0;
});
$mostFailed = $answeredQuestions;
usort($mostFailed, function($a, $b) {
    return $b["failure_rate"] <=> $a["failure_rate"];
});

$hardestQuestions = $answeredQuestions;
usort($hardestQuestions, function($a, $b) {
    return $b["avg_adaptive_difficulty"] <=> $a["avg_adaptive_difficulty"];
});

$slowestQuestions = $answeredQuestions;
usort($slowestQuestions, function($a, $b) {
    return $b["avg_response_time"] <=> $a["avg_response_time"];
});

$bestPrecision = $answeredQuestions;
usort($bestPrecision, function($a, $b) {
    return $b["precision"] <=> $a["precision"];
});

$summarySql = "
    SELECT
        COUNT(DISTINCT question_id) AS total_questions,
        COUNT(*) AS total_answers,
        COALESCE(SUM(is_correct), 0) AS correct_answers,
        COALESCE(AVG(response_time), 0) AS avg_response_time,
        COALESCE(AVG(difficulty_level), 0) AS avg_difficulty
    FROM game_answers
";

$summaryResult = $conn->query($summarySql);

$summary =
    $summaryResult->fetch_assoc();

$totalAnswers =
    (int)$summary["total_answers"];

$correctAnswers =
    (int)$summary["correct_answers"];

echo json_encode([

    "success" => true,

    "summary" => [

        "total_questions" =>
            (int)$summary["total_questions"],

        "total_answers" =>
            $totalAnswers,

        "correct_answers" =>
            $correctAnswers,

        "precision" =>
            $totalAnswers > 0
                ? round(
                    ($correctAnswers / $totalAnswers) * 100,
                    2
                )
                : 0,

        "avg_response_time" =>
            round(
                (float)$summary["avg_response_time"],
                2
            ),

        "avg_difficulty" =>
            round(
                (float)$summary["avg_difficulty"],
                1
            )
    ],

    "all_questions" =>
        $questions,

    "most_failed_questions" =>
        array_slice($mostFailed, 0, 10),

    "hardest_questions" =>
        array_slice($hardestQuestions, 0, 10),

    "slowest_questions" =>
        array_slice($slowestQuestions, 0, 10),

    "best_precision_questions" =>
        array_slice($bestPrecision, 0, 10)

], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
