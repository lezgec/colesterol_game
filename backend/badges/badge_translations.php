<?php

function badge_language(?string $language = null): string {
    if ($language === "en" || $language === "es") {
        return $language;
    }

    if (function_exists("current_lang")) {
        $current = current_lang();
        return $current === "en" ? "en" : "es";
    }

    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION["lang"])) {
        return $_SESSION["lang"] === "en" ? "en" : "es";
    }

    return "es";
}

function badge_translation_catalog(): array {
    return [
        "correct_streak_3" => [
            "es" => ["Racha x3", "Respondió 3 preguntas correctas seguidas."],
            "en" => ["Streak x3", "Answered 3 questions correctly in a row."]
        ],
        "correct_streak_5" => [
            "es" => ["Racha x5", "Respondió 5 preguntas correctas seguidas."],
            "en" => ["Streak x5", "Answered 5 questions correctly in a row."]
        ],
        "daily_streak_3" => [
            "es" => ["Constancia x3", "Jugó durante 3 días consecutivos."],
            "en" => ["Consistency x3", "Played on 3 consecutive days."]
        ],
        "daily_streak_7" => [
            "es" => ["Semana activa", "Jugó durante 7 días consecutivos."],
            "en" => ["Active Week", "Played on 7 consecutive days."]
        ],
        "first_game" => [
            "es" => ["Primera partida", "Completó su primera partida."],
            "en" => ["First Game", "Completed the first game."]
        ],
        "ten_answers" => [
            "es" => ["10 respuestas", "Respondió 10 preguntas."],
            "en" => ["10 Answers", "Answered 10 questions."]
        ],
        "hundred_answers" => [
            "es" => ["100 respuestas", "Respondió 100 preguntas."],
            "en" => ["100 Answers", "Answered 100 questions."]
        ],
        "precision_80" => [
            "es" => ["Maestro de precisión", "Alcanzó 80% de precisión."],
            "en" => ["Precision Master", "Reached 80% precision."]
        ],
        "precision_90" => [
            "es" => ["Precisión élite", "Alcanzó 90% de precisión."],
            "en" => ["Elite Precision", "Reached 90% precision."]
        ],
        "fast_responder" => [
            "es" => ["Pensador veloz", "Mantuvo un tiempo promedio menor a 4 segundos."],
            "en" => ["Fast Thinker", "Average response time below 4 seconds."]
        ],
        "advanced_player" => [
            "es" => ["Jugador avanzado", "Mantuvo una dificultad adaptativa alta."],
            "en" => ["Advanced Player", "Maintained high adaptive difficulty."]
        ],
        "difficulty_master" => [
            "es" => ["Maestro de dificultad", "Alcanzó dificultad adaptativa 4.5."],
            "en" => ["Difficulty Master", "Reached adaptive difficulty 4.5."]
        ],
        "teacher_first_room" => [
            "es" => ["Primera sala docente", "Creó su primera sala de aprendizaje."],
            "en" => ["First Teaching Room", "Created the first learning room."]
        ],
        "teacher_room_builder_5" => [
            "es" => ["Constructor de aulas", "Creó 5 salas para sus estudiantes."],
            "en" => ["Room Builder", "Created 5 rooms for students."]
        ],
        "teacher_open_room" => [
            "es" => ["Aula disponible", "Tiene una sala abierta, iniciada o pausada."],
            "en" => ["Open Classroom", "Has a waiting, started, or paused room."]
        ],
        "teacher_launched_room" => [
            "es" => ["Clase en marcha", "Inició una sala con estudiantes."],
            "en" => ["Class in Motion", "Started a room with students."]
        ],
        "teacher_finished_room" => [
            "es" => ["Cierre completo", "Finalizó una sala y dejó resultados listos para analizar."],
            "en" => ["Complete Closure", "Finished a room with results ready to analyze."]
        ],
        "teacher_curated_room" => [
            "es" => ["Curador de retos", "Diseñó una sala con preguntas configuradas o seleccionadas."],
            "en" => ["Challenge Curator", "Designed a room with configured or selected questions."]
        ],
        "teacher_extended_room" => [
            "es" => ["Sesión profunda", "Creó una sala de 20 preguntas o más."],
            "en" => ["Deep Session", "Created a room with 20 or more questions."]
        ],
        "teacher_engagement_10" => [
            "es" => ["Comunidad activa", "Reunió 10 participaciones en sus salas."],
            "en" => ["Active Community", "Reached 10 participations in teaching rooms."]
        ],
        "teacher_answers_50" => [
            "es" => ["Aula participativa", "Sus salas acumularon 50 respuestas."],
            "en" => ["Participative Classroom", "Teaching rooms accumulated 50 answers."]
        ],
        "teacher_accuracy_80" => [
            "es" => ["Guía de precisión", "Sus estudiantes alcanzaron 80% de precisión global con al menos 20 respuestas."],
            "en" => ["Accuracy Guide", "Students reached 80% overall accuracy with at least 20 answers."]
        ],
        "teacher_high_accuracy_room" => [
            "es" => ["Sala destacada", "Una de sus salas alcanzó 80% de precisión con al menos 10 respuestas."],
            "en" => ["Outstanding Room", "One room reached 80% accuracy with at least 10 answers."]
        ]
    ];
}

function translate_badge_payload(array $badge, ?string $language = null): array {
    $language = badge_language($language);
    $key = (string)($badge["badge_key"] ?? "");
    $catalog = badge_translation_catalog();

    if (isset($catalog[$key][$language])) {
        [$name, $description] = $catalog[$key][$language];
        $badge["badge_name"] = $name;
        $badge["badge_description"] = $description;
        return $badge;
    }

    if (str_starts_with($key, "expert_")) {
        $storedDescription = (string)($badge["badge_description"] ?? "");
        $category = trim(preg_replace('/^Mastered category:\s*/i', '', $storedDescription));
        $category = $category !== "" ? $category : trim(str_replace("_", " ", substr($key, 7)));

        $badge["badge_name"] = $language === "en" ? "Category Expert" : "Experto por categoría";
        $badge["badge_description"] = $language === "en"
            ? "Mastered category: {$category}"
            : "Dominó la categoría: {$category}";
    }

    return $badge;
}
