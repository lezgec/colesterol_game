<?php
require_once __DIR__ . '/../app/bootstrap.php';

$host = env_value("DB_HOST", "localhost");
$port = env_int("DB_PORT", 3306);
$user = env_value("DB_USERNAME", "root");
$password = env_value("DB_PASSWORD", "");
$dbname = env_value("DB_DATABASE", "colesterol_game_db");

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
