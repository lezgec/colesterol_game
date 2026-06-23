<?php
require_once __DIR__ . '/../../config/countries.php';

function ensure_user_profile_columns(mysqli $conn): void {
    $columns = [
        "avatar_key" => "ALTER TABLE users ADD COLUMN avatar_key VARCHAR(40) NULL AFTER session_updated_at",
        "custom_avatar_path" => "ALTER TABLE users ADD COLUMN custom_avatar_path VARCHAR(255) NULL AFTER avatar_key",
        "country" => "ALTER TABLE users ADD COLUMN country VARCHAR(8) NULL AFTER custom_avatar_path",
        "city" => "ALTER TABLE users ADD COLUMN city VARCHAR(80) NULL AFTER country",
        "institution" => "ALTER TABLE users ADD COLUMN institution VARCHAR(140) NULL AFTER city",
        "occupation" => "ALTER TABLE users ADD COLUMN occupation VARCHAR(120) NULL AFTER institution",
        "age" => "ALTER TABLE users ADD COLUMN age TINYINT UNSIGNED NULL AFTER occupation",
        "career" => "ALTER TABLE users ADD COLUMN career VARCHAR(140) NULL AFTER age",
        "education_level" => "ALTER TABLE users ADD COLUMN education_level VARCHAR(80) NULL AFTER career",
        "bio" => "ALTER TABLE users ADD COLUMN bio VARCHAR(500) NULL AFTER education_level"
    ];

    $existing = [];
    $result = $conn->query("SHOW COLUMNS FROM users");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[$row["Field"]] = true;
        }
    }

    foreach ($columns as $column => $sql) {
        if (!isset($existing[$column])) {
            $conn->query($sql);
        }
    }
}

function profile_avatar_options(): array {
    return [
        "pulse" => ["icon" => "heart", "emoji" => "", "label" => "Pulso"],
        "brain" => ["icon" => "brain", "emoji" => "", "label" => "Cerebro"],
        "trophy" => ["icon" => "trophy", "emoji" => "", "label" => "Logro"],
        "rocket" => ["icon" => "rocket", "emoji" => "", "label" => "Impulso"],
        "leaf" => ["icon" => "leaf", "emoji" => "", "label" => "Salud"],
        "star" => ["icon" => "star", "emoji" => "", "label" => "Estrella"]
    ];
}

function normalize_avatar_key(?string $avatarKey): string {
    $avatarKey = trim((string)$avatarKey);

    if ($avatarKey === "custom") {
        return "custom";
    }

    return array_key_exists($avatarKey, profile_avatar_options()) ? $avatarKey : "pulse";
}

function profile_avatar_payload(array $user): array {
    $avatarKey = normalize_avatar_key($user["avatar_key"] ?? "");
    $customPath = trim((string)($user["custom_avatar_path"] ?? ""));

    if ($avatarKey === "custom" && $customPath !== "") {
        return [
            "type" => "custom",
            "key" => "custom",
            "emoji" => "",
            "url" => $customPath
        ];
    }

    $avatarKey = $avatarKey === "custom" ? "pulse" : $avatarKey;
    $avatar = profile_avatar_options()[$avatarKey] ?? profile_avatar_options()["pulse"];

    return [
        "type" => "preset",
        "key" => $avatarKey,
        "emoji" => $avatar["emoji"],
        "url" => ""
    ];
}

function sanitize_profile_text(?string $value, int $maxLength): string {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/', ' ', $value);

    if (function_exists("mb_substr")) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function sanitize_profile_age($value): ?int {
    if ($value === null || $value === "") {
        return null;
    }

    $age = filter_var($value, FILTER_VALIDATE_INT);

    if ($age === false || $age < 5 || $age > 120) {
        return null;
    }

    return (int)$age;
}
