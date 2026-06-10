<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../config/question_categories.php';

require_role(["teacher", "super_admin"]);

$returnRoomCode = strtoupper(trim($_GET["return_room"] ?? ""));
$returnRoomHref = $returnRoomCode !== ""
    ? "/colesterol_game/pages/rooms/lobby_admin.php?code=" . urlencode($returnRoomCode)
    : "";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("admin_title"); ?></title>

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

        <div class="top-links">
            <?php if ($returnRoomHref !== ""): ?>
                <a href="<?php echo htmlspecialchars($returnRoomHref); ?>" class="logout-btn secondary-btn">
                    <?php echo t("back_to_room_lobby"); ?>
                </a>
            <?php endif; ?>

            <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_admin"); ?>
            </a>

            <a href="/colesterol_game/pages/logout.php" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        </div>
    </div>

    <h1><?php echo t("admin_title"); ?></h1>
    <p><?php echo t("admin_description"); ?></p>

    <nav class="admin-question-nav" aria-label="<?php echo t("admin_questions"); ?>">
        <a href="#manual-question-section"><?php echo t("question_admin_manual_tab"); ?></a>
        <a href="#single-generator"><?php echo t("question_admin_generator_tab"); ?></a>
        <a href="#mass-generator"><?php echo t("question_admin_bulk_tab"); ?></a>
        <a href="#csv-import-section"><?php echo t("question_admin_import_tab"); ?></a>
        <a href="#question-bank-section"><?php echo t("question_admin_bank_tab"); ?></a>
    </nav>

    <section class="admin-section is-collapsed" id="manual-question-section" data-collapsible-section>
        <button
            type="button"
            class="admin-section-heading"
            aria-expanded="false"
            aria-controls="manual-question-panel"
        >
            <span><?php echo t("question_admin_manual_tab"); ?></span>
            <h2><?php echo t("create_question"); ?></h2>
            <span class="accordion-icon" aria-hidden="true"></span>
        </button>

        <div class="admin-section-body" id="manual-question-panel" hidden>
            <form id="manual-question-form" class="admin-form">
                <input type="hidden" id="edit-id">

                <div class="form-group">
                    <label for="question"><?php echo t("question"); ?></label>
                    <textarea id="question" required></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="option_a"><?php echo t("option_a"); ?></label>
                        <input type="text" id="option_a" required>
                    </div>

                    <div class="form-group">
                        <label for="option_b"><?php echo t("option_b"); ?></label>
                        <input type="text" id="option_b" required>
                    </div>

                    <div class="form-group">
                        <label for="option_c"><?php echo t("option_c"); ?></label>
                        <input type="text" id="option_c" required>
                    </div>

                    <div class="form-group">
                        <label for="option_d"><?php echo t("option_d"); ?></label>
                        <input type="text" id="option_d" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="correct_option"><?php echo t("correct_option"); ?></label>
                        <select id="correct_option" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="difficulty_level"><?php echo t("difficulty_level"); ?></label>
                        <input
                            type="number"
                            id="difficulty_level"
                            min="1"
                            max="5"
                            step="0.1"
                            value="1.0"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="language"><?php echo t("language"); ?></label>
                        <select id="language" required>
                            <option value="es"><?php echo t("spanish"); ?></option>
                            <option value="en"><?php echo t("english"); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="category"><?php echo t("category"); ?></label>
                        <select id="category" data-custom-input="category_custom" required></select>
                        <input
                            type="text"
                            id="category_custom"
                            class="custom-category-input"
                            placeholder="<?php echo t("custom_category_placeholder"); ?>"
                            aria-label="<?php echo t("custom_category"); ?>"
                        >
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="status"><?php echo t("status"); ?></label>
                        <select id="status" required>
                            <option value="verified"><?php echo t("verified"); ?></option>
                            <option value="pending"><?php echo t("pending"); ?></option>
                            <option value="rejected"><?php echo t("rejected"); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="origin"><?php echo t("origin"); ?></label>
                        <select id="origin" required>
                            <option value="manual"><?php echo t("manual"); ?></option>
                            <option value="ai"><?php echo t("ai"); ?></option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="is_active"><?php echo t("active_status"); ?></label>
                        <select id="is_active" required>
                            <option value="1"><?php echo t("active"); ?></option>
                            <option value="0"><?php echo t("inactive"); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="explanation"><?php echo t("explanation"); ?></label>
                    <textarea id="explanation" required></textarea>
                </div>

                <button type="submit" class="primary-btn" id="save-question-btn">
                    <?php echo t("save_question"); ?>
                </button>

                <button type="button" class="secondary-form-btn" id="cancel-edit-btn" style="display:none;">
                    <?php echo t("cancel_edit"); ?>
                </button>

                <p id="manual-message"></p>
            </form>
        </div>
    </section>

    <section class="admin-section is-collapsed" id="single-generator" data-collapsible-section>
        <button
            type="button"
            class="admin-section-heading"
            aria-expanded="false"
            aria-controls="single-generator-panel"
        >
            <span><?php echo t("question_admin_generator_tab"); ?></span>
            <h2><?php echo t("generator"); ?></h2>
            <span class="accordion-icon" aria-hidden="true"></span>
        </button>
        <div class="admin-section-body" id="single-generator-panel" hidden>
            <p><?php echo t("generator_description"); ?></p>

            <form id="generator-form" class="admin-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="generator_category"><?php echo t("category"); ?></label>
                        <select id="generator_category" data-custom-input="generator_category_custom" required></select>
                        <input
                            type="text"
                            id="generator_category_custom"
                            class="custom-category-input"
                            placeholder="<?php echo t("custom_category_placeholder"); ?>"
                            aria-label="<?php echo t("custom_category"); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="generator_topic"><?php echo t("topic_optional"); ?></label>
                        <input type="text" id="generator_topic" placeholder="<?php echo t("topic_optional_placeholder"); ?>">
                    </div>

                    <div class="form-group">
                        <label for="generator_difficulty_level"><?php echo t("difficulty_level"); ?></label>
                        <input
                            type="number"
                            id="generator_difficulty_level"
                            min="1"
                            max="5"
                            step="0.1"
                            value="1.0"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="generator_language"><?php echo t("language"); ?></label>
                        <select id="generator_language">
                            <option value="es"><?php echo t("spanish"); ?></option>
                            <option value="en"><?php echo t("english"); ?></option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="primary-btn">
                    <?php echo t("generate_question"); ?>
                </button>

                <p id="generator-message"></p>
            </form>
        </div>
    </section>

    <section class="admin-section is-collapsed" id="mass-generator" data-collapsible-section>
        <button
            type="button"
            class="admin-section-heading"
            aria-expanded="false"
            aria-controls="mass-generator-panel"
        >
            <span><?php echo t("question_admin_bulk_tab"); ?></span>
            <h2><?php echo t("mass_generator"); ?></h2>
            <span class="accordion-icon" aria-hidden="true"></span>
        </button>
        <div class="admin-section-body" id="mass-generator-panel" hidden>
            <p><?php echo t("mass_generator_description"); ?></p>

            <form id="mass-generator-form" class="admin-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="mass_category"><?php echo t("category"); ?></label>
                        <select id="mass_category" data-custom-input="mass_category_custom" required></select>
                        <input
                            type="text"
                            id="mass_category_custom"
                            class="custom-category-input"
                            placeholder="<?php echo t("custom_category_placeholder"); ?>"
                            aria-label="<?php echo t("custom_category"); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="mass_topic"><?php echo t("topic_optional"); ?></label>
                        <input type="text" id="mass_topic" placeholder="<?php echo t("topic_optional_placeholder"); ?>">
                    </div>

                    <div class="form-group">
                        <label for="mass_quantity"><?php echo t("quantity"); ?></label>
                        <input type="number" id="mass_quantity" min="1" max="20" value="5" required>
                    </div>

                    <div class="form-group">
                        <label for="mass_difficulty_level"><?php echo t("difficulty_level"); ?></label>
                        <input
                            type="number"
                            id="mass_difficulty_level"
                            min="1"
                            max="5"
                            step="0.1"
                            value="1.0"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="mass_language"><?php echo t("language"); ?></label>
                        <select id="mass_language">
                            <option value="es"><?php echo t("spanish"); ?></option>
                            <option value="en"><?php echo t("english"); ?></option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="primary-btn">
                    <?php echo t("generate_and_insert"); ?>
                </button>

                <p id="mass-generator-message"></p>
            </form>
        </div>
    </section>

    <section class="admin-section is-collapsed" id="csv-import-section" data-collapsible-section>
        <button
            type="button"
            class="admin-section-heading"
            aria-expanded="false"
            aria-controls="csv-import-panel"
        >
            <span><?php echo t("question_admin_import_tab"); ?></span>
            <h2><?php echo t("import_csv"); ?></h2>
            <span class="accordion-icon" aria-hidden="true"></span>
        </button>
        <div class="admin-section-body" id="csv-import-panel" hidden>
            <p><?php echo t("csv_description"); ?></p>

            <form id="csv-form" class="admin-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file"><?php echo t("csv_file"); ?></label>
                    <input type="file" id="csv_file" accept=".csv" required>
                </div>

                <button type="submit" class="primary-btn">
                    <?php echo t("import_csv"); ?>
                </button>

                <p id="csv-message"></p>
            </form>
        </div>
    </section>

    <section class="admin-section is-collapsed" id="question-bank-section" data-collapsible-section>
        <button
            type="button"
            class="admin-section-heading"
            aria-expanded="false"
            aria-controls="question-bank-panel"
        >
            <span><?php echo t("question_admin_bank_tab"); ?></span>
            <h2><?php echo t("registered_questions"); ?></h2>
            <span class="accordion-icon" aria-hidden="true"></span>
        </button>

        <div class="admin-section-body" id="question-bank-panel" hidden>
            <div style="overflow-x:auto;">
                <table id="questionsTable" class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo t("question"); ?></th>
                            <th><?php echo t("correct_option"); ?></th>
                            <th><?php echo t("difficulty_level"); ?></th>
                            <th><?php echo t("language"); ?></th>
                            <th><?php echo t("category"); ?></th>
                            <th><?php echo t("status"); ?></th>
                            <th><?php echo t("origin"); ?></th>
                            <th><?php echo t("active_status"); ?></th>
                            <th><?php echo t("actions"); ?></th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<script>
const ADMIN_I18N = {
    edit: "<?php echo t('edit'); ?>",
    delete: "<?php echo t('delete'); ?>",
    confirmDelete: "<?php echo t('confirm_delete_question'); ?>",
    loading: "<?php echo t('loading'); ?>",
    saved: "<?php echo t('question_saved'); ?>",
    updated: "<?php echo t('question_updated'); ?>",
    deleted: "<?php echo t('question_deleted'); ?>",
    error: "<?php echo t('error'); ?>",
    saveQuestion: "<?php echo t('save_question'); ?>",
    updateQuestion: "<?php echo t('update_question'); ?>",
    cancelEdit: "<?php echo t('cancel_edit'); ?>",
    generatedReady: "<?php echo t('generated_ready'); ?>",
    massGeneratedSuccess: "<?php echo t('mass_generated_success'); ?>",
    generatedQuestionsNeedReview: "<?php echo t('generated_questions_need_review'); ?>",
    verified: "<?php echo t('verified'); ?>",
    pending: "<?php echo t('pending'); ?>",
    rejected: "<?php echo t('rejected'); ?>",
    active: "<?php echo t('active'); ?>",
    inactive: "<?php echo t('inactive'); ?>",
    noQuestionsRegistered: "<?php echo t('no_questions_registered'); ?>",
    otherCategory: "<?php echo t('other_category'); ?>"
};

const QUESTION_CATEGORIES = <?php echo json_encode([
    "es" => question_categories("es"),
    "en" => question_categories("en")
], JSON_UNESCAPED_UNICODE); ?>;
</script>

<script src="/colesterol_game/assets/js/admin/admin_questions.js"></script>
</body>
</html>
