<?php

function ensure_user_session_columns($conn) {
    $columns = ["session_token", "session_updated_at"];

    foreach ($columns as $column) {
        $columnName = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '{$columnName}'");

        if ($result && $result->num_rows > 0) {
            continue;
        }

        return false;
    }

    return true;
}

function create_user_session_token() {
    return bin2hex(random_bytes(32));
}

function store_user_session_token($conn, $userId, $sessionToken) {
    if (!ensure_user_session_columns($conn)) {
        return false;
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET session_token = ?, session_updated_at = NOW()
        WHERE id = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("si", $sessionToken, $userId);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function clear_user_session_token($conn, $userId, $sessionToken = null) {
    if (!ensure_user_session_columns($conn)) {
        return false;
    }

    if ($sessionToken !== null) {
        $stmt = $conn->prepare("
            UPDATE users
            SET session_token = NULL, session_updated_at = NULL
            WHERE id = ?
              AND session_token = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("is", $userId, $sessionToken);
    } else {
        $stmt = $conn->prepare("
            UPDATE users
            SET session_token = NULL, session_updated_at = NULL
            WHERE id = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $userId);
    }

    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function active_session_matches($conn, $userId, $sessionToken) {
    if (!ensure_user_session_columns($conn)) {
        return false;
    }

    if ($userId <= 0 || $sessionToken === "") {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT session_token
        FROM users
        WHERE id = ?
          AND status = 'active'
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user && hash_equals((string)$user["session_token"], (string)$sessionToken);
}
