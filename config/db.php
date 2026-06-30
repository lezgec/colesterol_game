<?php
require_once __DIR__ . '/../app/bootstrap.php';

$host = env_value("DB_HOST", "localhost");
$port = env_int("DB_PORT", 3306);
$user = env_value("DB_USERNAME", "root");
$password = env_value("DB_PASSWORD", "");
$dbname = env_value("DB_DATABASE", "colesterol_game_db");

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    error_log("DB connection error: " . $conn->connect_error);

    if (env_bool("APP_DEBUG", false)) {
        die("Error de conexión: " . $conn->connect_error);
    }

    die("No se pudo conectar con la base de datos.");
}

$conn->set_charset("utf8mb4");
