<?php

function json_response($payload, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function app_error_detail($error = null) {
    if (!env_bool('APP_DEBUG', false)) {
        return null;
    }

    if ($error instanceof Throwable) {
        return $error->getMessage();
    }

    return $error === null ? null : (string)$error;
}

function request_json() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        json_response([
            'success' => false,
            'message' => 'JSON inválido'
        ], 400);
    }

    return $data;
}

function csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf_token() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        json_response([
            'success' => false,
            'message' => 'Token CSRF inválido'
        ], 403);
    }
}

function app_base_path() {
    return rtrim(env_value('APP_BASE_PATH', '/colesterol_game'), '/');
}

function app_path($path = '') {
    $base = app_base_path();
    $path = ltrim((string)$path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset_path($path) {
    return app_path('assets/' . ltrim((string)$path, '/'));
}

function script_path($path) {
    return app_path(ltrim((string)$path, '/'));
}

function app_url_base() {
    return rtrim(env_value('APP_URL', 'http://localhost/colesterol_game'), '/');
}

function app_absolute_url($path = '') {
    return app_url_base() . '/' . ltrim($path, '/');
}
