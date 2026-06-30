<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';
require_once __DIR__ . '/../config/question_categories.php';

require_role(["teacher", "super_admin"]);

$isSuperAdmin = is_super_admin();

$returnRoomCode = strtoupper(trim($_GET["return_room"] ?? ""));
$returnRoomHref = $returnRoomCode !== ""
    ? app_path("pages/rooms/lobby_admin.php?code=" . urlencode($returnRoomCode))
    : "";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$questionsJsVersion = filemtime(__DIR__ . '/../assets/js/admin/admin_questions.js');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("admin_title"); ?></title>

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
            <?php if ($returnRoomHref !== ""): ?>
                <a href="<?php echo htmlspecialchars($returnRoomHref); ?>" class="logout-btn secondary-btn">
                    <?php echo t("back_to_room_lobby"); ?>
                </a>
            <?php endif; ?>

            <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>" class="logout-btn secondary-btn">
                <?php echo t("back_to_admin"); ?>
            </a>

            <a href="<?php echo app_path('pages/logout.php'); ?>" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        </div>
    </div>

    <h1><?php echo t("admin_title"); ?></h1>
    <p><?php echo t("admin_description"); ?></p>

    <nav class="admin-question-nav" aria-label="<?php echo t("admin_questions"); ?>">
        <a href="#manual-question-section"><?php echo t("question_admin_manual_tab"); ?></a>
        <a href="#ai-generator"><?php echo t("generate_with_ai"); ?></a>
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
            <button type="button" class="primary-btn" id="open-create-question-modal">
                <?php echo t("create_question"); ?>
            </button>

            <form id="manual-question-form" class="admin-form" hidden>
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
                            step="1"
                            value="1"
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

    <section class="admin-section is-collapsed" id="ai-generator" data-collapsible-section>
        <button
            type="button"
            class="admin-section-heading"
            aria-expanded="false"
            aria-controls="ai-generator-panel"
        >
            <span><?php echo t("ai_short"); ?></span>
            <h2><?php echo t("generate_with_ai"); ?></h2>
            <span class="accordion-icon" aria-hidden="true"></span>
        </button>
        <div class="admin-section-body" id="ai-generator-panel" hidden>
            <p><?php echo t("generator_unified_description"); ?></p>

            <form id="generator-form" class="admin-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="generator_mode"><?php echo t("mode"); ?></label>
                        <select id="generator_mode" required>
                            <option value="single"><?php echo t("generator_mode_draft"); ?></option>
                            <option value="bulk"><?php echo t("generator_mode_bulk"); ?></option>
                        </select>
                    </div>

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
                            step="1"
                            value="1"
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

                    <?php if ($isSuperAdmin): ?>
                        <input type="hidden" id="generator_question_scope" value="global">
                    <?php else: ?>
                        <div class="form-group">
                            <label for="generator_question_scope"><?php echo t("question_scope"); ?></label>
                            <select id="generator_question_scope">
                                <option value="private"><?php echo t("question_scope_private"); ?></option>
                                <option value="global_request"><?php echo t("question_scope_global_request"); ?></option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group" id="generator_quantity_group" hidden>
                        <label for="generator_quantity"><?php echo t("quantity"); ?></label>
                        <input type="number" id="generator_quantity" min="1" max="20" value="5">
                    </div>
                </div>

                <button type="submit" class="primary-btn">
                    <?php echo t("generate"); ?>
                </button>

                <p id="generator-message"></p>
                <div id="ai-progress-panel" class="ai-progress-panel" hidden>
                    <div class="ai-progress-track" aria-hidden="true">
                        <span id="ai-progress-bar"></span>
                    </div>
                    <ol class="ai-progress-steps" aria-live="polite">
                        <li data-ai-step="prepare"><?php echo t("ai_progress_prepare"); ?></li>
                        <li data-ai-step="generate"><?php echo t("ai_progress_generate"); ?></li>
                        <li data-ai-step="save"><?php echo t("ai_progress_save"); ?></li>
                        <li data-ai-step="ready"><?php echo t("ai_progress_ready"); ?></li>
                    </ol>
                    <button type="button" id="review-generated-questions-btn" class="secondary-form-btn ai-review-generated-btn" hidden>
                        <?php echo t("review_generated_questions"); ?>
                    </button>
                </div>
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
            <a class="logout-btn secondary-btn csv-template-link" href="<?php echo app_path('backend/questions/download_csv_template.php'); ?>">
                <?php echo t("download_csv_template"); ?>
            </a>

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
            <div class="question-bank-filters" aria-label="<?php echo t("question_filters"); ?>">
                <label class="question-filter-field question-search-field">
                    <span><?php echo t("search"); ?></span>
                    <input
                        type="search"
                        id="question-search"
                        placeholder="<?php echo t("search_question_placeholder"); ?>"
                    >
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("category"); ?></span>
                    <select id="question-filter-category">
                        <option value=""><?php echo t("all_categories"); ?></option>
                    </select>
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("difficulty_level"); ?></span>
                    <select id="question-filter-difficulty">
                        <option value=""><?php echo t("all_difficulties"); ?></option>
                        <option value="1">1 / 5</option>
                        <option value="2">2 / 5</option>
                        <option value="3">3 / 5</option>
                        <option value="4">4 / 5</option>
                        <option value="5">5 / 5</option>
                    </select>
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("language"); ?></span>
                    <select id="question-filter-language">
                        <option value=""><?php echo t("all_languages"); ?></option>
                        <option value="es">ES</option>
                        <option value="en">EN</option>
                    </select>
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("status"); ?></span>
                    <select id="question-filter-status">
                        <option value=""><?php echo t("all_statuses"); ?></option>
                        <option value="verified"><?php echo t("verified"); ?></option>
                        <option value="pending"><?php echo t("pending"); ?></option>
                        <option value="rejected"><?php echo t("rejected"); ?></option>
                    </select>
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("origin"); ?></span>
                    <select id="question-filter-origin">
                        <option value=""><?php echo t("all_origins"); ?></option>
                        <option value="manual"><?php echo t("manual"); ?></option>
                        <option value="ai"><?php echo t("ai"); ?></option>
                        <option value="csv">CSV</option>
                    </select>
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("active_status"); ?></span>
                    <select id="question-filter-active">
                        <option value=""><?php echo t("all_statuses"); ?></option>
                        <option value="1"><?php echo t("active"); ?></option>
                        <option value="0"><?php echo t("inactive"); ?></option>
                    </select>
                </label>

                <button type="button" id="question-clear-filters" class="secondary-form-btn">
                    <?php echo t("clear_filters"); ?>
                </button>
            </div>

            <div>
                <table id="questionsTable" class="admin-table questions-admin-table">
                    <thead>
                        <tr>
                            <th class="col-id">ID</th>
                            <th class="col-question"><?php echo t("question"); ?></th>
                            <th class="col-correct"><?php echo t("correct_option"); ?></th>
                            <th class="col-difficulty"><?php echo t("difficulty_level"); ?></th>
                            <th class="col-language"><?php echo t("language"); ?></th>
                            <th class="col-category"><?php echo t("category"); ?></th>
                            <th class="col-status"><?php echo t("status"); ?></th>
                            <th class="col-origin"><?php echo t("origin"); ?></th>
                            <th class="col-active"><?php echo t("active_status"); ?></th>
                            <th class="col-actions"><?php echo t("actions"); ?></th>
                        </tr>
                    </thead>

                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<div id="app-confirm-modal" class="question-modal-backdrop app-confirm-backdrop" hidden>
    <section class="app-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title">
        <header>
            <h2 id="app-confirm-title"><?php echo t("confirm_action"); ?></h2>
            <button type="button" id="app-confirm-close" class="question-modal-close" aria-label="<?php echo t("close"); ?>">×</button>
        </header>
        <p id="app-confirm-message"></p>
        <footer>
            <button type="button" id="app-confirm-cancel" class="secondary-form-btn">
                <?php echo t("cancel"); ?>
            </button>
            <button type="button" id="app-confirm-accept" class="primary-btn">
                <?php echo t("confirm"); ?>
            </button>
        </footer>
    </section>
</div>

<div id="question-edit-modal" class="question-modal-backdrop" hidden>
    <section class="question-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="question-edit-title">
        <header class="question-edit-header">
            <div>
                <span class="question-edit-icon"><?php echo ui_icon("edit"); ?></span>
                <h2 id="question-edit-title"><?php echo t("edit_question"); ?></h2>
            </div>
            <button type="button" id="question-modal-close" class="question-modal-close" aria-label="<?php echo t("close"); ?>">×</button>
        </header>

        <form id="question-edit-modal-form" class="question-edit-body">
            <input type="hidden" id="modal-question-id">

            <label class="modal-field">
                <span><?php echo t("question"); ?></span>
                <textarea id="modal-question" rows="4" required></textarea>
            </label>

            <div class="question-modal-grid">
                <label class="modal-field">
                    <span><?php echo t("difficulty_level"); ?></span>
                    <select id="modal-difficulty" required>
                        <option value="1">1 - <?php echo t("difficulty_basic"); ?></option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5 - <?php echo t("difficulty_advanced"); ?></option>
                    </select>
                </label>

                <label class="modal-field">
                    <span><?php echo t("category"); ?></span>
                    <select id="modal-category" data-custom-input="modal-custom-category"></select>
                    <input type="text" id="modal-custom-category" class="custom-category-input" placeholder="<?php echo t("other_category"); ?>">
                </label>

                <label class="modal-field">
                    <span><?php echo t("language"); ?></span>
                    <select id="modal-language">
                        <option value="es">ES</option>
                        <option value="en">EN</option>
                    </select>
                </label>

                <label class="modal-field">
                    <span><?php echo t("status"); ?></span>
                    <select id="modal-status">
                        <option value="verified"><?php echo t("verified"); ?></option>
                        <option value="pending"><?php echo t("pending"); ?></option>
                        <option value="rejected"><?php echo t("rejected"); ?></option>
                    </select>
                </label>

                <?php if ($isSuperAdmin): ?>
                    <input type="hidden" id="modal-question-scope" value="global">
                <?php else: ?>
                    <label class="modal-field">
                        <span><?php echo t("question_scope"); ?></span>
                        <select id="modal-question-scope">
                            <option value="private"><?php echo t("question_scope_private"); ?></option>
                            <option value="global_request"><?php echo t("question_scope_global_request"); ?></option>
                        </select>
                    </label>
                <?php endif; ?>
            </div>

            <section class="question-options-editor">
                <div class="question-options-heading">
                    <h3><?php echo t("answer_options"); ?></h3>
                    <p><?php echo t("select_correct_option"); ?></p>
                </div>

                <div class="question-option-grid">
                    <?php foreach (["A", "B", "C", "D"] as $letter): ?>
                        <label class="question-option-card" data-option-card="<?php echo $letter; ?>">
                            <span>
                                <input type="radio" name="modal-correct-option" value="<?php echo $letter; ?>">
                                <?php echo $letter; ?>
                            </span>
                            <input type="text" id="modal-option-<?php echo strtolower($letter); ?>" required>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <label class="modal-field">
                <span><?php echo t("explanation"); ?></span>
                <textarea id="modal-explanation" rows="4" required></textarea>
            </label>

            <div class="question-modal-meta">
                <label class="modal-field">
                    <span><?php echo t("origin"); ?></span>
                    <select id="modal-origin">
                        <option value="manual">Manual</option>
                        <option value="ai">IA</option>
                        <option value="csv">CSV</option>
                    </select>
                </label>
                <label class="modal-field">
                    <span><?php echo t("active_status"); ?></span>
                    <select id="modal-is-active">
                        <option value="1"><?php echo t("active"); ?></option>
                        <option value="0"><?php echo t("inactive"); ?></option>
                    </select>
                </label>
            </div>

            <p id="question-modal-message" class="modal-message"></p>
        </form>

        <footer class="question-edit-footer">
            <button type="button" id="question-modal-cancel" class="secondary-form-btn">
                <?php echo t("cancel"); ?>
            </button>
            <button type="submit" form="question-edit-modal-form" class="primary-btn">
                <?php echo t("save_changes"); ?>
            </button>
        </footer>
    </section>
</div>

<script>
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const ADMIN_I18N = {
    edit: "<?php echo t('edit'); ?>",
    delete: "<?php echo t('delete'); ?>",
    confirmDelete: "<?php echo t('confirm_delete_question'); ?>",
    loading: "<?php echo t('loading'); ?>",
    saved: "<?php echo t('question_saved'); ?>",
    updated: "<?php echo t('question_updated'); ?>",
    mustBeVerifiedToBeAvailable: "<?php echo t('question_must_be_verified_to_be_available'); ?>",
    deleted: "<?php echo t('question_deleted'); ?>",
    error: "<?php echo t('error'); ?>",
    saveQuestion: "<?php echo t('save_question'); ?>",
    updateQuestion: "<?php echo t('update_question'); ?>",
    cancelEdit: "<?php echo t('cancel_edit'); ?>",
    generatedReady: "<?php echo t('generated_ready'); ?>",
    massGeneratedSuccess: "<?php echo t('mass_generated_success'); ?>",
    generatedQuestionsNeedReview: "<?php echo t('generated_questions_need_review'); ?>",
    aiProgressPrepare: "<?php echo t('ai_progress_prepare'); ?>",
    aiProgressGenerate: "<?php echo t('ai_progress_generate'); ?>",
    aiProgressSave: "<?php echo t('ai_progress_save'); ?>",
    aiProgressReady: "<?php echo t('ai_progress_ready'); ?>",
    reviewGeneratedQuestions: "<?php echo t('review_generated_questions'); ?>",
    generatedFilterActive: "<?php echo t('generated_filter_active'); ?>",
    verified: "<?php echo t('verified'); ?>",
    pending: "<?php echo t('pending'); ?>",
    rejected: "<?php echo t('rejected'); ?>",
    active: "<?php echo t('active'); ?>",
    inactive: "<?php echo t('inactive'); ?>",
    noQuestionsRegistered: "<?php echo t('no_questions_registered'); ?>",
    noFilterResults: "<?php echo t('no_filter_results'); ?>",
    otherCategory: "<?php echo t('other_category'); ?>",
    allCategories: "<?php echo t('all_categories'); ?>",
    confirmAction: "<?php echo t('confirm_action'); ?>",
    createQuestion: "<?php echo t('create_question'); ?>",
    editQuestion: "<?php echo t('edit_question'); ?>",
    saveChanges: "<?php echo t('save_changes'); ?>",
    questionSentToGlobalReview: "<?php echo t('question_sent_to_global_review'); ?>"
};

const ADMIN_IS_SUPER_ADMIN = <?php echo $isSuperAdmin ? "true" : "false"; ?>;

const QUESTION_CATEGORIES = <?php echo json_encode([
    "es" => question_categories("es"),
    "en" => question_categories("en")
], JSON_UNESCAPED_UNICODE); ?>;
</script>

<script src="<?php echo asset_path('js/http.js'); ?>?m=<?php echo filemtime(__DIR__ . '/../assets/js/http.js'); ?>"></script>
<script src="<?php echo asset_path('js/admin/admin_questions.js'); ?>?m=<?php echo $questionsJsVersion; ?>"></script>

<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
