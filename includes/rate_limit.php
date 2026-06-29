<?php

require_once __DIR__ . '/../backend/support/api_response.php';

function ensure_rate_limits_table(mysqli $conn): bool {
    $result = $conn->query("SHOW TABLES LIKE 'rate_limits'");
    return $result && $result->num_rows > 0;
}

function rate_limit_identity(): string {
    $ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";
    $agent = substr($_SERVER["HTTP_USER_AGENT"] ?? "unknown", 0, 160);

    return hash("sha256", $ip . "|" . $agent);
}

function require_rate_limit(mysqli $conn, string $scope, int $maxAttempts, int $windowSeconds): void {
    if ($maxAttempts <= 0 || $windowSeconds <= 0 || !ensure_rate_limits_table($conn)) {
        return;
    }

    $key = hash("sha256", $scope . "|" . rate_limit_identity());
    $cutoff = date("Y-m-d H:i:s", time() - $windowSeconds);

    $cleanup = $conn->prepare("DELETE FROM rate_limits WHERE created_at < ?");
    if ($cleanup) {
        $cleanup->bind_param("s", $cutoff);
        $cleanup->execute();
        $cleanup->close();
    }

    $count = 0;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS attempts
        FROM rate_limits
        WHERE rate_key = ?
          AND created_at >= ?
    ");

    if ($stmt) {
        $stmt->bind_param("ss", $key, $cutoff);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $count = (int)($row["attempts"] ?? 0);
        $stmt->close();
    }

    if ($count >= $maxAttempts) {
        api_error("Demasiados intentos. Espera un momento antes de volver a intentar.", 429);
    }

    $insert = $conn->prepare("INSERT INTO rate_limits (rate_key) VALUES (?)");
    if ($insert) {
        $insert->bind_param("s", $key);
        $insert->execute();
        $insert->close();
    }
}
