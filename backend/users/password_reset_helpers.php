<?php

require_once __DIR__ . "/../../includes/mail_helpers.php";

function ensure_password_resets_table($conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_resets_user_id (user_id),
            INDEX idx_password_resets_token_hash (token_hash),
            CONSTRAINT fk_password_resets_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return $conn->query($sql);
}
