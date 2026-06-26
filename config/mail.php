<?php
require_once __DIR__ . '/../app/bootstrap.php';

return [
    "app_url" => env_value("APP_URL", "https://juego.example.dev/colesterol_game"),
    "host" => env_value("MAIL_HOST", "smtp.example.dev"),
    "port" => env_int("MAIL_PORT", 587),
    "encryption" => env_value("MAIL_ENCRYPTION", "tls"),
    "username" => env_value("MAIL_USERNAME", "demo@example.dev"),
    "password" => env_value("MAIL_PASSWORD", "change_me_app_password"),
    "from_email" => env_value("MAIL_FROM_EMAIL", env_value("APP_SUPPORT_EMAIL", "support@example.dev")),
    "from_name" => env_value("MAIL_FROM_NAME", "Colesterol Game"),
    "reply_to" => env_value("MAIL_REPLY_TO", env_value("APP_SUPPORT_EMAIL", "support@example.dev"))
];
