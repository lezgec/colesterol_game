<?php

require_once __DIR__ . '/../../app/bootstrap.php';

function api_response(array $payload, int $statusCode = 200): void {
    json_response($payload, $statusCode);
}

function api_error(string $message, int $statusCode = 400, array $extra = []): void {
    api_response(array_merge([
        "success" => false,
        "message" => $message
    ], $extra), $statusCode);
}

function api_success(array $payload = [], int $statusCode = 200): void {
    api_response(array_merge([
        "success" => true
    ], $payload), $statusCode);
}
