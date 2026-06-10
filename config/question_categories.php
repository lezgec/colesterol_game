<?php
function question_categories(string $language = "es"): array {
    $categories = [
        "es" => [
            "Colesterol",
            "Alimentación",
            "Actividad física",
            "Factores de riesgo",
            "Prevención cardiovascular",
            "Aterosclerosis",
            "Lípidos y metabolismo",
            "Medicamentos",
            "Guías clínicas",
            "Salud pública"
        ],
        "en" => [
            "Cholesterol",
            "Diet",
            "Physical activity",
            "Risk factors",
            "Cardiovascular prevention",
            "Atherosclerosis",
            "Lipids and metabolism",
            "Medications",
            "Clinical guidelines",
            "Public health"
        ]
    ];

    return $categories[$language] ?? $categories["es"];
}

function normalize_question_category(string $category, string $language = "es"): string {
    $category = trim($category);
    $allowed = question_categories($language);

    foreach ($allowed as $allowedCategory) {
        if (strcasecmp($category, $allowedCategory) === 0) {
            return $allowedCategory;
        }
    }

    $category = preg_replace('/\s+/', ' ', $category);
    $category = function_exists("mb_substr")
        ? mb_substr($category, 0, 80, "UTF-8")
        : substr($category, 0, 80);

    if ($category === "" || ctype_digit($category)) {
        return $allowed[0];
    }

    return $category;
}
?>
