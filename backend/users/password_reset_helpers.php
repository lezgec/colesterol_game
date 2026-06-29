<?php

require_once __DIR__ . "/../../includes/mail_helpers.php";

function ensure_password_resets_table($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'password_resets'");
    return $result && $result->num_rows > 0;
}
