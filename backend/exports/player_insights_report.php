<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$userId = is_super_admin() && $requestedUserId > 0
    ? $requestedUserId
    : (int)$_SESSION["user_id"];

$insights = [];

try {

    $categorySql = "
        SELECT
            q.category,
            COUNT(*) AS total_answers,
            COALESCE(SUM(ga.is_correct), 0) AS correct_answers,
            COALESCE(ROUND((SUM(ga.is_correct) / COUNT(*)) * 100, 2), 0) AS precision,
            COALESCE(ROUND(AVG(ga.response_time), 2), 0) AS avg_time
        FROM game_answers ga
        INNER JOIN questions q ON ga.question_id = q.id
        WHERE ga.user_id = ?
        GROUP BY q.category
        HAVING total_answers > 0
    ";

    $stmt = $conn->prepare($categorySql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $categories = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    if (!empty($categories)) {
        usort($categories, function ($a, $b) {
            return (float)$a["precision"] <=> (float)$b["precision"];
        });

        $weakest = $categories[0];
        $strongest = end($categories);

        $insights[] = [
            "type" => "weak_category",
            "title" => "Categoría más débil",
            "message" =>
                "Tu categoría más débil es " .
                $weakest["category"] .
                " con " .
                $weakest["precision"] .
                "% de precisión."
        ];

        $insights[] = [
            "type" => "strong_category",
            "title" => "Categoría más fuerte",
            "message" =>
                "Tu mejor categoría es " .
                $strongest["category"] .
                " con " .
                $strongest["precision"] .
                "% de precisión."
        ];
    }

    $timeSql = "
        SELECT
            COALESCE(ROUND(AVG(response_time), 2), 0) AS avg_time
        FROM game_answers
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($timeSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $avgTime = (float)(
        $stmt->get_result()->fetch_assoc()["avg_time"] ?? 0
    );

    $stmt->close();

    if ($avgTime > 0) {
        if ($avgTime <= 4) {
            $insights[] = [
                "type" => "fast_player",
                "title" => "Respuesta rápida",
                "message" =>
                    "Respondes muy rápido, con un tiempo promedio de {$avgTime} segundos."
            ];
        } elseif ($avgTime >= 10) {
            $insights[] = [
                "type" => "slow_player",
                "title" => "Tiempo de respuesta alto",
                "message" =>
                    "Tu tiempo promedio de respuesta es {$avgTime} segundos. Puedes mejorar con más práctica."
            ];
        }
    }

    $difficultySql = "
        SELECT
            COALESCE(ROUND(AVG(difficulty_level), 2), 0) AS avg_difficulty,
            COALESCE(MAX(difficulty_level), 0) AS max_difficulty
        FROM game_answers
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($difficultySql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $difficulty = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    $avgDifficulty = (float)($difficulty["avg_difficulty"] ?? 0);
    $maxDifficulty = (float)($difficulty["max_difficulty"] ?? 0);

    if ($avgDifficulty >= 3.5) {
        $insights[] = [
            "type" => "advanced_player",
            "title" => "Nivel avanzado",
            "message" =>
                "Te mantienes frecuentemente en niveles altos de dificultad adaptativa."
        ];
    }

    if ($maxDifficulty >= 4.5) {
        $insights[] = [
            "type" => "difficulty_master",
            "title" => "Dominio de dificultad",
            "message" =>
                "Alcanzaste una dificultad máxima de {$maxDifficulty}/5."
        ];
    }

    $precisionSql = "
        SELECT
            COUNT(*) AS total_answers,
            COALESCE(SUM(is_correct), 0) AS correct_answers
        FROM game_answers
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($precisionSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $precisionData = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    $totalAnswers = (int)($precisionData["total_answers"] ?? 0);
    $correctAnswers = (int)($precisionData["correct_answers"] ?? 0);

    $precision = $totalAnswers > 0
        ? round(($correctAnswers / $totalAnswers) * 100, 2)
        : 0;

    if ($precision >= 80) {
        $insights[] = [
            "type" => "excellent_precision",
            "title" => "Excelente precisión",
            "message" =>
                "Mantienes una precisión excelente de {$precision}%."
        ];
    } elseif ($precision <= 50 && $totalAnswers >= 10) {
        $insights[] = [
            "type" => "needs_practice",
            "title" => "Necesitas más práctica",
            "message" =>
                "Tu precisión es de {$precision}%. Se recomienda practicar más."
        ];
    }

    if (empty($insights)) {
        $insights[] = [
            "type" => "not_enough_data",
            "title" => "Datos insuficientes",
            "message" => "Juega más partidas para generar recomendaciones personalizadas."
        ];
    }

    echo json_encode([
        "success" => true,
        "insights" => $insights
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
