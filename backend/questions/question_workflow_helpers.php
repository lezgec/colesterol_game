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

    $requiredColumns = [
        "created_by_user_id",
        "visibility",
        "global_request_status",
        "global_requested_at",
        "global_reviewed_by",
        "global_reviewed_at"
    ];

    foreach ($requiredColumns as $column) {
        if (!isset($columns[$column])) {
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
    $result = $conn->query("SHOW TABLES LIKE 'app_notifications'");
    return $result && $result->num_rows > 0;
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
