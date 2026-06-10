<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/question_categories.php';

require_role(["teacher", "super_admin"]);

function room_categories_for_language(mysqli $conn, string $language): array {
    $categories = question_categories($language);
    $stmt = $conn->prepare("
        SELECT DISTINCT category
        FROM questions
        WHERE language = ?
          AND category <> ''
        ORDER BY category ASC
    ");

    if ($stmt) {
        $stmt->bind_param("s", $language);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $category = normalize_question_category((string)$row["category"], $language);

            if ($category === "" || ctype_digit($category)) {
                continue;
            }

            if (!in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }

        $stmt->close();
    }

    return $categories;
}

$categoriesByLanguage = [
    "es" => room_categories_for_language($conn, "es"),
    "en" => room_categories_for_language($conn, "en")
];

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
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
                <option value="configured"><?php echo t("configured_questions"); ?></option>
                <option value="random"><?php echo t("random_questions"); ?></option>
                <option value="selected"><?php echo t("selected_questions"); ?></option>
            </select>
        </div>

        <section id="configured-questions-section" class="admin-section">
            <h2><?php echo t("question_blocks"); ?></h2>

            <div class="form-grid">
                <div class="form-group">
                    <label for="block_category"><?php echo t("category"); ?></label>
                    <select id="block_category"></select>
                </div>

                <div class="form-group" id="custom-category-group" style="display:none;">
                    <label for="custom_category"><?php echo t("new_category"); ?></label>
                    <input type="text" id="custom_category" maxlength="80" placeholder="<?php echo t("new_category_placeholder"); ?>">
                </div>

                <div class="form-group">
                    <label for="block_difficulty"><?php echo t("difficulty"); ?></label>
                    <select id="block_difficulty">
                        <option value="1">1 / 5</option>
                        <option value="2">2 / 5</option>
                        <option value="3">3 / 5</option>
                        <option value="4">4 / 5</option>
                        <option value="5">5 / 5</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="block_quantity"><?php echo t("question_count"); ?></label>
                    <input type="number" id="block_quantity" min="1" max="50" value="5">
                </div>
            </div>

            <button type="button" id="add-question-block-btn" class="primary-btn">
                <?php echo t("add_question_block"); ?>
            </button>

            <div id="question-blocks-summary" class="room-config-summary"></div>

            <div id="ai-generation-progress" class="ai-generation-progress" hidden>
                <div class="progress-meta">
                    <span id="ai-generation-status"><?php echo t("preparing_ai"); ?></span>
                    <strong id="ai-generation-percent">0%</strong>
                </div>
                <div class="progress-track">
                    <div id="ai-generation-fill" class="progress-fill"></div>
                </div>
            </div>
        </section>

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

        <p id="create-room-message" class="room-status-message" aria-live="polite"></p>
    </form>

</div>

<script>
const ROOM_CATEGORIES = <?php echo json_encode($categoriesByLanguage, JSON_UNESCAPED_UNICODE); ?>;

const CREATE_ROOM_I18N = {
    loading: "<?php echo t('loading'); ?>",
    error: "<?php echo t('error'); ?>",
    selectAtLeastOne: "<?php echo t('select_at_least_one_question'); ?>",
    noQuestionBlocks: "<?php echo t('no_question_blocks'); ?>",
    notEnoughQuestions: "<?php echo t('not_enough_questions'); ?>",
    generateMissingQuestions: "<?php echo t('create_missing_questions_in_admin'); ?>",
    generatingMissingQuestions: "<?php echo t('generating_missing_questions'); ?>",
    generatedMissingQuestions: "<?php echo t('generated_missing_questions'); ?>",
    addQuestionBlock: "<?php echo t('add_question_block'); ?>",
    remove: "<?php echo t('remove'); ?>",
    available: "<?php echo t('available'); ?>",
    requested: "<?php echo t('requested'); ?>",
    total: "<?php echo t('total'); ?>",
    category: "<?php echo t('category'); ?>",
    createNewCategory: "<?php echo t('create_new_category'); ?>",
    newCategoryRequired: "<?php echo t('new_category_required'); ?>",
    difficulty: "<?php echo t('difficulty'); ?>",
    aiProgressMessages: [
        "<?php echo t('preparing_ai'); ?>",
        "<?php echo t('preparing_engines'); ?>",
        "<?php echo t('assigning_categories'); ?>",
        "<?php echo t('calibrating_difficulty'); ?>",
        "<?php echo t('creating_questions'); ?>",
        "<?php echo t('reviewing_answers'); ?>",
        "<?php echo t('saving_questions'); ?>"
    ]
};
</script>

<script src="/colesterol_game/assets/js/rooms/create_room.js"></script>
</body>
</html>
