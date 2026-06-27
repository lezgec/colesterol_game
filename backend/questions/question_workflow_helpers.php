<?php

require_once __DIR__ . '/../../includes/auth.php';

function ensure_question_workflow_columns($conn) {
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM questions");

    if (!$result) {
        return false;
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row["Field"]] = true;
    }

    $alterations = [
        "created_by_user_id" => "ALTER TABLE questions ADD COLUMN created_by_user_id INT NULL AFTER is_active",
        "visibility" => "ALTER TABLE questions ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'global' AFTER created_by_user_id",
        "global_request_status" => "ALTER TABLE questions ADD COLUMN global_request_status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER visibility",
        "global_requested_at" => "ALTER TABLE questions ADD COLUMN global_requested_at DATETIME NULL AFTER global_request_status",
        "global_reviewed_by" => "ALTER TABLE questions ADD COLUMN global_reviewed_by INT NULL AFTER global_requested_at",
        "global_reviewed_at" => "ALTER TABLE questions ADD COLUMN global_reviewed_at DATETIME NULL AFTER global_reviewed_by"
    ];

    foreach ($alterations as $column => $sql) {
        if (!isset($columns[$column]) && !$conn->query($sql)) {
            return false;
        }
    }

    $conn->query("
        UPDATE questions
        SET visibility = 'global',
            global_request_status = CASE
                WHEN status = 'pending' THEN 'pending'
                WHEN status = 'rejected' THEN 'rejected'
                ELSE 'approved'
            END
        WHERE visibility IS NULL OR visibility = ''
    ");

    return true;
}

function requested_question_scope($data) {
    $scope = trim($data["question_scope"] ?? $data["visibility"] ?? "private");

    if (is_super_admin()) {
        return $scope === "private" ? "private" : "global";
    }

    return $scope === "global" || $scope === "global_request"
        ? "global_request"
        : "private";
}

function question_workflow_for_create($data, $requestedStatus, $requestedActive) {
    $scope = requested_question_scope($data);
    $now = date("Y-m-d H:i:s");
    $requiresReview = !empty($data["requires_review"]);

    if (is_super_admin()) {
        if ($requiresReview) {
            return [
                "visibility" => $scope === "private" ? "private" : "global",
                "global_request_status" => $scope === "private" ? "none" : "pending",
                "status" => "pending",
                "is_active" => 0,
                "global_requested_at" => $scope === "private" ? null : $now
            ];
        }

        return [
            "visibility" => $scope === "private" ? "private" : "global",
            "global_request_status" => $scope === "private" ? "none" : "approved",
            "status" => $requestedStatus,
            "is_active" => $requestedActive,
            "global_requested_at" => null
        ];
    }

    if ($scope === "global_request") {
        return [
            "visibility" => "global",
            "global_request_status" => "pending",
            "status" => "pending",
            "is_active" => 0,
            "global_requested_at" => $now
        ];
    }

    if ($requiresReview) {
        return [
            "visibility" => "private",
            "global_request_status" => "none",
            "status" => "pending",
            "is_active" => 0,
            "global_requested_at" => null
        ];
    }

    return [
        "visibility" => "private",
        "global_request_status" => "none",
        "status" => "verified",
        "is_active" => 1,
        "global_requested_at" => null
    ];
}

function question_access_sql($alias = "q") {
    $prefix = $alias ? "{$alias}." : "";

    if (is_super_admin()) {
        return "1 = 1";
    }

    $userId = current_user_id();

    return "(
        ({$prefix}visibility = 'global' AND {$prefix}global_request_status = 'approved')
        OR {$prefix}created_by_user_id = {$userId}
    )";
}

function playable_question_access_sql($alias = "q") {
    $prefix = $alias ? "{$alias}." : "";
    $userId = current_user_id();

    if (is_super_admin()) {
        return "(
            {$prefix}visibility = 'global'
            OR ({$prefix}visibility = 'private' AND {$prefix}created_by_user_id = {$userId})
        )";
    }

    return "(
        ({$prefix}visibility = 'global' AND {$prefix}global_request_status = 'approved')
        OR ({$prefix}visibility = 'private' AND {$prefix}created_by_user_id = {$userId})
    )";
}

function ensure_app_notifications_table($conn) {
    return $conn->query("
        CREATE TABLE IF NOT EXISTS app_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            target_role VARCHAR(40) NOT NULL,
            user_id INT NULL,
            type VARCHAR(80) NOT NULL,
            title VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            related_url VARCHAR(255) NULL,
            read_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_role (target_role),
            INDEX idx_notifications_user (user_id),
            INDEX idx_notifications_type (type),
            INDEX idx_notifications_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function create_role_notification($conn, $targetRole, $type, $title, $message, $relatedUrl = null) {
    if (!ensure_app_notifications_table($conn)) {
        return false;
    }

    $stmt = $conn->prepare("
        INSERT INTO app_notifications
            (target_role, type, title, message, related_url)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("sssss", $targetRole, $type, $title, $message, $relatedUrl);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function notify_super_admins_about_global_question_request($conn, $questionId, $teacherName, $questionText = "") {
    require_once __DIR__ . '/../../includes/mail_helpers.php';

    $relatedUrl = app_base_path() . "/pages/admin_questions.php?filter_status=pending#question-bank-section";
    $title = "Solicitud de pregunta global";
    $message = "{$teacherName} envió preguntas para revisar en el banco global.";

    create_role_notification(
        $conn,
        "super_admin",
        "question_global_request",
        $title,
        $message,
        $relatedUrl
    );

    $result = $conn->query("
        SELECT name, email
        FROM users
        WHERE role = 'super_admin'
          AND status = 'active'
    ");

    if (!$result) {
        return;
    }

    while ($admin = $result->fetch_assoc()) {
        try {
            send_global_question_request_email(
                $admin["email"],
                $admin["name"],
                $teacherName,
                app_absolute_url("pages/admin_questions.php?filter_status=pending#question-bank-section")
            );
        } catch (RuntimeException $exception) {
            error_log("Global question request email failed: " . $exception->getMessage());
        }
    }
}
