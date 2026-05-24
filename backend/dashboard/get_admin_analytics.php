<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
if (
     !has_role(["teacher", "super_admin"])
) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ]);
    exit;
}

$data = [];

/*
|--------------------------------------------------------------------------
| Precisión general
|--------------------------------------------------------------------------
*/

$sqlAccuracy = "
    SELECT 
        SUM(correct_answers) AS total_correct,
        SUM(total_questions) AS total_answered
    FROM game_results
";

$resAccuracy = $conn->query($sqlAccuracy);

$accuracy = [
    "total_correct" => 0,
    "total_answered" => 0,
    "percentage" => 0
];

if ($resAccuracy && $row = $resAccuracy->fetch_assoc()) {

    $totalCorrect = (int)($row["total_correct"] ?? 0);
    $totalAnswered = (int)($row["total_answered"] ?? 0);

    $percentage = 0;

    if ($totalAnswered > 0) {
        $percentage = round(($totalCorrect / $totalAnswered) * 100, 2);
    }

    $accuracy = [
        "total_correct" => $totalCorrect,
        "total_answered" => $totalAnswered,
        "percentage" => $percentage
    ];
}

$data["accuracy"] = $accuracy;

/*
|--------------------------------------------------------------------------
| Rendimiento por dificultad
|--------------------------------------------------------------------------
*/

$sqlDifficulty = "
    SELECT 
        difficulty,
        SUM(correct_answers) AS total_correct,
        SUM(total_questions) AS total_questions
    FROM game_results
    GROUP BY difficulty
";

$resDifficulty = $conn->query($sqlDifficulty);

$difficultyStats = [];

if ($resDifficulty) {

    while ($row = $resDifficulty->fetch_assoc()) {

        $correct = (int)$row["total_correct"];
        $questions = (int)$row["total_questions"];

        $percentage = 0;

        if ($questions > 0) {
            $percentage = round(($correct / $questions) * 100, 2);
        }

        $difficultyStats[] = [
            "difficulty" => $row["difficulty"],
            "correct" => $correct,
            "questions" => $questions,
            "percentage" => $percentage
        ];
    }
}

$data["difficulty_stats"] = $difficultyStats;

/*
|--------------------------------------------------------------------------
| Top jugadores
|--------------------------------------------------------------------------
*/

$sqlTopPlayers = "
    SELECT 
        player_name,
        MAX(score) AS best_score,
        SUM(correct_answers) AS total_correct
    FROM game_results
    GROUP BY player_name
    ORDER BY best_score DESC
    LIMIT 10
";

$resPlayers = $conn->query($sqlTopPlayers);

$topPlayers = [];

if ($resPlayers) {

    while ($row = $resPlayers->fetch_assoc()) {

        $topPlayers[] = [
            "player_name" => $row["player_name"],
            "best_score" => (int)$row["best_score"],
            "total_correct" => (int)$row["total_correct"]
        ];
    }
}

$data["top_players"] = $topPlayers;

/*
|--------------------------------------------------------------------------
| Salas más activas
|--------------------------------------------------------------------------
*/

$sqlRooms = "
    SELECT 
        gr.name,
        gr.room_code,
        COUNT(gres.id) AS total_results
    FROM game_rooms gr
    LEFT JOIN game_results gres ON gr.id = gres.room_id
    GROUP BY gr.id
    ORDER BY total_results DESC
    LIMIT 10
";

$resRooms = $conn->query($sqlRooms);

$topRooms = [];

if ($resRooms) {

    while ($row = $resRooms->fetch_assoc()) {

        $topRooms[] = [
            "name" => $row["name"],
            "room_code" => $row["room_code"],
            "total_results" => (int)$row["total_results"]
        ];
    }
}

$data["top_rooms"] = $topRooms;

echo json_encode([
    "success" => true,
    "data" => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>