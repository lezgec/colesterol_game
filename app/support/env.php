<?php

function load_env_file($path) {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && (
            ($value[0] === '"' && substr($value, -1) === '"') ||
            ($value[0] === "'" && substr($value, -1) === "'")
        )) {
            $value = substr($value, 1, -1);
        }

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env_value($key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return $value;
}

function env_int($key, $default) {
    $value = env_value($key, null);

    return $value === null || $value === '' ? $default : (int)$value;
}

function env_bool($key, $default = false) {
    $value = strtolower((string)env_value($key, $default ? 'true' : 'false'));

    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}
