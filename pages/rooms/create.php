<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$styleVersion = filemtime(__DIR__ . '/../../assets/css/style.css');
$createRoomJsVersion = filemtime(__DIR__ . '/../../assets/js/rooms/create_room.js');
$themeVersion = filemtime(__DIR__ . '/../../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("create_room"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?v=<?php echo $styleVersion; ?>">
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

    </div>

    <h1><?php echo t("create_room"); ?></h1>

    <p>
        <?php echo current_lang() === "en"
            ? "Create the room now. You can configure questions and time from the lobby before starting."
            : "Crea la sala ahora. En el lobby configuras preguntas y tiempo antes de iniciar."; ?>
    </p>

    <form id="create-room-form" class="admin-form">

        <div class="form-group">
            <label for="room_name"><?php echo t("room_name"); ?></label>
            <input type="text" id="room_name" required>
        </div>

        <div class="form-group">
            <label for="room_language"><?php echo t("language"); ?></label>
            <select id="room_language">
                <option value="es"><?php echo t("spanish"); ?></option>
                <option value="en"><?php echo t("english"); ?></option>
            </select>
        </div>

        <button type="submit" class="primary-btn">
            <?php echo t("create_room"); ?>
        </button>

        <p id="create-room-message" class="room-status-message" aria-live="polite"></p>
    </form>

    <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>" class="secondary-link room-back-link">
        <?php echo t("back_to_admin"); ?>
    </a>

</div>

<script>
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const CREATE_ROOM_I18N = {
    loading: "<?php echo t('loading'); ?>",
    error: "<?php echo t('error'); ?>"
};
</script>

<script src="<?php echo asset_path('js/http.js'); ?>?v=<?php echo filemtime(__DIR__ . '/../../assets/js/http.js'); ?>"></script>
<script src="<?php echo asset_path('js/rooms/create_room.js'); ?>?v=<?php echo $createRoomJsVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?v=<?php echo $themeVersion; ?>"></script>
</body>
</html>
