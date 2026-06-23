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
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("notifications"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">
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
            <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("back_dashboard"); ?>
            </a>
            <?php render_user_menu(); ?>
        </div>
    </div>

    <h1><?php echo t("notifications"); ?></h1>
    <p class="page-intro"><?php echo t("notifications_description"); ?></p>

    <section class="admin-section">
        <div class="notification-list">
            <?php if (!$notifications): ?>
                <p><?php echo t("no_notifications"); ?></p>
            <?php endif; ?>

            <?php foreach ($notifications as $notification): ?>
                <article class="notification-card">
                    <div>
                        <h2><?php echo htmlspecialchars($notification["title"]); ?></h2>
                        <p><?php echo htmlspecialchars($notification["message"]); ?></p>
                        <small><?php echo htmlspecialchars($notification["created_at"]); ?></small>
                    </div>

                    <?php if (!empty($notification["related_url"])): ?>
                        <a class="table-btn edit-btn" href="<?php echo htmlspecialchars($notification["related_url"]); ?>">
                            <?php echo t("review"); ?>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script src="/colesterol_game/assets/js/theme.js?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
