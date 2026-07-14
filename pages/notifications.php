<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/user_menu.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/questions/question_workflow_helpers.php';

require_role(["teacher", "super_admin"]);
ensure_app_notifications_table($conn);

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

$role = current_user_role();
$userId = current_user_id();
$noticeMessage = $_SESSION["notifications_notice"] ?? "";
unset($_SESSION["notifications_notice"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "clear_read_notifications") {
    if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
        $_SESSION["notifications_notice"] = t("invalid_request");
        header("Location: " . app_path("pages/notifications.php"));
        exit;
    }

    $deleteStmt = $conn->prepare("
        DELETE FROM app_notifications
        WHERE read_at IS NOT NULL
          AND (target_role = ? OR user_id = ?)
    ");

    if ($deleteStmt) {
        $deleteStmt->bind_param("si", $role, $userId);
        $deleteStmt->execute();
        $deletedCount = $deleteStmt->affected_rows;
        $deleteStmt->close();
        $_SESSION["notifications_notice"] = $deletedCount > 0
            ? t("read_notifications_deleted")
            : t("no_read_notifications_to_delete");
    }

    header("Location: " . app_path("pages/notifications.php"));
    exit;
}

if (isset($_GET["read"])) {
    $notificationId = (int)$_GET["read"];

    if ($notificationId > 0) {
        $stmt = $conn->prepare("
            SELECT id, related_url
            FROM app_notifications
            WHERE id = ?
              AND (target_role = ? OR user_id = ?)
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("isi", $notificationId, $role, $userId);
            $stmt->execute();
            $notification = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($notification) {
                $updateStmt = $conn->prepare("
                    UPDATE app_notifications
                    SET read_at = COALESCE(read_at, NOW())
                    WHERE id = ?
                      AND (target_role = ? OR user_id = ?)
                ");

                if ($updateStmt) {
                    $updateStmt->bind_param("isi", $notificationId, $role, $userId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                $relatedUrl = trim($notification["related_url"] ?? "");
                $basePath = app_base_path() ?: "/";
                $isLocalUrl = $relatedUrl !== ""
                    && (
                        str_starts_with($relatedUrl, $basePath . "/")
                        || str_starts_with($relatedUrl, app_path(""))
                    );

                header("Location: " . ($isLocalUrl ? $relatedUrl : app_path("pages/notifications.php")));
                exit;
            }
        }
    }

    header("Location: " . app_path("pages/notifications.php"));
    exit;
}

$stmt = $conn->prepare("
    SELECT id, type, title, message, related_url, read_at, created_at
    FROM app_notifications
    WHERE target_role = ?
       OR user_id = ?
    ORDER BY created_at DESC
    LIMIT 100
");

$stmt->bind_param("si", $role, $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();

$readNotificationCount = 0;

foreach ($notifications as $notification) {
    if (!empty($notification["read_at"])) {
        $readNotificationCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("notifications"); ?></title>
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

    <h1><?php echo t("notifications"); ?></h1>
    <p class="page-intro"><?php echo t("notifications_description"); ?></p>

    <section class="admin-section">
        <?php if ($noticeMessage): ?>
            <p class="modal-message success"><?php echo htmlspecialchars($noticeMessage); ?></p>
        <?php endif; ?>

        <?php if ($readNotificationCount > 0): ?>
            <div class="notification-toolbar">
                <p>
                    <?php echo htmlspecialchars(sprintf(t("read_notifications_count"), $readNotificationCount)); ?>
                </p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="clear_read_notifications">
                    <button type="submit" class="secondary-form-btn">
                        <?php echo t("clear_read_notifications"); ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="notification-list">
            <?php if (!$notifications): ?>
                <p><?php echo t("no_notifications"); ?></p>
            <?php endif; ?>

            <?php foreach ($notifications as $notification): ?>
                <?php $isUnread = empty($notification["read_at"]); ?>
                <article class="notification-card <?php echo $isUnread ? "notification-unread" : "notification-read"; ?>">
                    <div>
                        <div class="notification-card-heading">
                            <h2><?php echo htmlspecialchars($notification["title"]); ?></h2>
                            <span class="notification-status-pill">
                                <?php echo $isUnread ? t("notification_unread") : t("notification_read"); ?>
                            </span>
                        </div>
                        <p><?php echo htmlspecialchars($notification["message"]); ?></p>
                        <small>
                            <?php echo htmlspecialchars($notification["created_at"]); ?>
                            <?php if (!$isUnread): ?>
                                &middot; <?php echo t("notification_opened_at"); ?> <?php echo htmlspecialchars($notification["read_at"]); ?>
                            <?php endif; ?>
                        </small>
                    </div>

                    <?php if (!empty($notification["related_url"])): ?>
                        <a class="table-btn edit-btn" href="<?php echo app_path('pages/notifications.php?read=' . urlencode((string)$notification["id"])); ?>">
                            <?php echo t("review"); ?>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
