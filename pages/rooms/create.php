<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("create_room"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container admin-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
            <?php echo t("back_to_admin"); ?>
        </a>
    </div>

    <h1><?php echo t("create_room"); ?></h1>

    <p>
        <?php echo t("adaptive_room_description"); ?>
    </p>

    <form id="create-room-form" class="admin-form">

        <div class="form-group">
            <label for="room_name"><?php echo t("room_name"); ?></label>
            <input type="text" id="room_name" required>
        </div>

        <div class="form-grid">

            <div class="form-group">
                <label for="room_language"><?php echo t("language"); ?></label>
                <select id="room_language">
                    <option value="es"><?php echo t("spanish"); ?></option>
                    <option value="en"><?php echo t("english"); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="question_count"><?php echo t("question_count"); ?></label>
                <input type="number" id="question_count" min="1" max="50" value="10" required>
            </div>

            <div class="form-group">
                <label for="time_limit"><?php echo t("time_limit"); ?></label>
                <input type="number" id="time_limit" min="5" max="120" value="20" required>
            </div>

        </div>

        <div class="form-group">
            <label for="question_mode"><?php echo t("question_mode"); ?></label>
            <select id="question_mode">
                <option value="random"><?php echo t("random_questions"); ?></option>
                <option value="selected"><?php echo t("selected_questions"); ?></option>
            </select>
        </div>

        <section id="selected-questions-section" class="admin-section" style="display:none;">
            <h2><?php echo t("select_questions"); ?></h2>
            <p><?php echo t("select_questions_description"); ?></p>

            <button type="button" id="load-questions-btn" class="primary-btn">
                <?php echo t("load_questions"); ?>
            </button>

            <div id="questions-selection-list" style="margin-top:15px;"></div>
        </section>

        <button type="submit" class="primary-btn">
            <?php echo t("create_room"); ?>
        </button>

        <p id="create-room-message"></p>
    </form>

</div>

<script>
const CREATE_ROOM_I18N = {
    loading: "<?php echo t('loading'); ?>",
    error: "<?php echo t('error'); ?>",
    selectAtLeastOne: "<?php echo t('select_at_least_one_question'); ?>"
};
</script>

<script src="/colesterol_game/assets/js/rooms/create_room.js"></script>
</body>
</html>