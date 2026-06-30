<?php
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["teacher", "super_admin"])) {
    http_response_code(403);
    exit("No autorizado");
}

$filename = "plantilla_preguntas_colesterol.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "question",
    "option_a",
    "option_b",
    "option_c",
    "option_d",
    "correct_option",
    "explanation",
    "category",
    "difficulty_level",
    "language",
    "status",
    "origin",
    "is_active"
]);

fputcsv($output, [
    "¿Cuál lipoproteína se conoce comúnmente como colesterol malo?",
    "Lipoproteína de alta densidad, conocida como HDL",
    "Lipoproteína de baja densidad, conocida como LDL",
    "Triglicéridos",
    "Hemoglobina",
    "B",
    "La lipoproteína de baja densidad, conocida como LDL, puede depositar colesterol en las arterias cuando está elevada.",
    "Factores de riesgo",
    "2.0",
    "es",
    "verified",
    "csv",
    "1"
]);

fclose($output);
?>
