<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/user_menu.php';
require_once __DIR__ . '/../includes/mail_helpers.php';
require_once __DIR__ . '/../config/db.php';

require_role(["super_admin"]);
ensure_email_logs_table($conn);

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

$search = trim($_GET["search"] ?? "");
$type = trim($_GET["type"] ?? "");
$status = trim($_GET["status"] ?? "");

$allowedTypes = ["", "welcome", "password_reset", "role_changed", "question_global_request", "general"];
$allowedStatuses = ["", "sent", "failed"];

if (!in_array($type, $allowedTypes, true)) {
    $type = "";
}

if (!in_array($status, $allowedStatuses, true)) {
    $status = "";
}

$conditions = [];
$params = [];
$types = "";

if ($search !== "") {
    $conditions[] = "(recipient_email LIKE ? OR recipient_name LIKE ? OR subject LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if ($type !== "") {
    $conditions[] = "email_type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($status !== "") {
    $conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
$sql = "
    SELECT id, email_type, recipient_email, recipient_name, subject, status, error_message, sent_at, created_at
    FROM email_logs
    {$whereSql}
    ORDER BY created_at DESC
    LIMIT 300
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$stmt->close();

function email_log_type_label($type) {
    $map = [
        "welcome" => t("email_type_welcome"),
        "password_reset" => t("email_type_password_reset"),
        "role_changed" => t("email_type_role_changed"),
        "question_global_request" => t("email_type_question_global_request"),
        "general" => t("email_type_general")
    ];

    return $map[$type] ?? $type;
}

function email_log_status_label($status) {
    return $status === "sent" ? t("email_status_sent") : t("email_status_failed");
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("email_logs"); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">
</head>
<body>
<div class="game-container admin-container">
    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>" class="logout-btn secondary-btn">
                <?php echo t("back_dashboard"); ?>
            </a>
            <?php render_user_menu(); ?>
        </div>
    </div>

    <h1><?php echo t("email_logs"); ?></h1>
    <p class="page-intro"><?php echo t("email_logs_description"); ?></p>

    <section class="admin-section">
        <form method="get" class="form-grid email-log-filters">
            <div class="form-group">
                <label for="search"><?php echo t("search"); ?></label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="<?php echo t("email_logs_search_placeholder"); ?>"
                >
            </div>

            <div class="form-group">
                <label for="type"><?php echo t("email_type"); ?></label>
                <select id="type" name="type">
                    <option value=""><?php echo t("all"); ?></option>
                    <option value="welcome" <?php echo $type === "welcome" ? "selected" : ""; ?>><?php echo t("email_type_welcome"); ?></option>
                    <option value="password_reset" <?php echo $type === "password_reset" ? "selected" : ""; ?>><?php echo t("email_type_password_reset"); ?></option>
                    <option value="role_changed" <?php echo $type === "role_changed" ? "selected" : ""; ?>><?php echo t("email_type_role_changed"); ?></option>
                    <option value="question_global_request" <?php echo $type === "question_global_request" ? "selected" : ""; ?>><?php echo t("email_type_question_global_request"); ?></option>
                    <option value="general" <?php echo $type === "general" ? "selected" : ""; ?>><?php echo t("email_type_general"); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="status"><?php echo t("status"); ?></label>
                <select id="status" name="status">
                    <option value=""><?php echo t("all"); ?></option>
                    <option value="sent" <?php echo $status === "sent" ? "selected" : ""; ?>><?php echo t("email_status_sent"); ?></option>
                    <option value="failed" <?php echo $status === "failed" ? "selected" : ""; ?>><?php echo t("email_status_failed"); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="primary-btn"><?php echo t("search"); ?></button>
            </div>
        </form>
    </section>

    <section class="admin-section">
        <table class="admin-table responsive-card-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("email_type"); ?></th>
                    <th><?php echo t("recipient"); ?></th>
                    <th><?php echo t("subject"); ?></th>
                    <th><?php echo t("status"); ?></th>
                    <th><?php echo t("sent_at"); ?></th>
                    <th><?php echo t("error_detail"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                    <tr>
                        <td colspan="7"><?php echo t("no_email_logs"); ?></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td data-label="ID"><?php echo (int)$log["id"]; ?></td>
                        <td data-label="<?php echo t("email_type"); ?>"><?php echo htmlspecialchars(email_log_type_label($log["email_type"])); ?></td>
                        <td data-label="<?php echo t("recipient"); ?>">
                            <strong><?php echo htmlspecialchars($log["recipient_email"]); ?></strong><br>
                            <span><?php echo htmlspecialchars($log["recipient_name"] ?? "-"); ?></span>
                        </td>
                        <td data-label="<?php echo t("subject"); ?>"><?php echo htmlspecialchars($log["subject"]); ?></td>
                        <td data-label="<?php echo t("status"); ?>">
                            <span class="status-chip email-status-<?php echo htmlspecialchars($log["status"]); ?>">
                                <?php echo htmlspecialchars(email_log_status_label($log["status"])); ?>
                            </span>
                        </td>
                        <td data-label="<?php echo t("sent_at"); ?>">
                            <?php echo htmlspecialchars($log["sent_at"] ?: $log["created_at"]); ?>
                        </td>
                        <td data-label="<?php echo t("error_detail"); ?>">
                            <?php echo htmlspecialchars($log["error_message"] ?: "-"); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
