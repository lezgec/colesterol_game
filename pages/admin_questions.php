<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../assets/includes/auth.php';
require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: /colesterol_game/pages/login.php");
    exit;
}
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
        <div class="language-switch">
            <a href="?lang=es">ES</a> |
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("back_to_admin"); ?>
            </a>
            <a href="/colesterol_game/logout.php" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        </div>
    </div>

    <h1><?php echo t("admin_title"); ?></h1>
    <p><?php echo t("admin_description"); ?></p>

    <section class="admin-section">
        <h2><?php echo t("create_question"); ?></h2>

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
                    <label for="difficulty"><?php echo t("difficulty"); ?></label>
                    <select id="difficulty" required>
                        <option value="easy"><?php echo t("easy"); ?></option>
                        <option value="medium"><?php echo t("medium"); ?></option>
                        <option value="hard"><?php echo t("hard"); ?></option>
                    </select>
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
                    <input type="text" id="category" placeholder="<?php echo t("category_placeholder"); ?>" required>
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
    </section>

    <section class="admin-section">
        <h2><?php echo t("generator"); ?></h2>
        <p><?php echo t("generator_description"); ?></p>

        <form id="generator-form" class="admin-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="generator_topic"><?php echo t("topic"); ?></label>
                    <input type="text" id="generator_topic" placeholder="<?php echo t("topic_placeholder"); ?>" required>
                </div>

                <div class="form-group">
                    <label for="generator_difficulty"><?php echo t("difficulty"); ?></label>
                    <select id="generator_difficulty">
                        <option value="easy"><?php echo t("easy"); ?></option>
                        <option value="medium"><?php echo t("medium"); ?></option>
                        <option value="hard"><?php echo t("hard"); ?></option>
                    </select>
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
    </section>

    <section class="admin-section">
    <h2><?php echo t("mass_generator"); ?></h2>
    <p><?php echo t("mass_generator_description"); ?></p>

        <form id="mass-generator-form" class="admin-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="mass_topic"><?php echo t("topic"); ?></label>
                    <input type="text" id="mass_topic" placeholder="<?php echo t("topic_placeholder"); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mass_quantity"><?php echo t("quantity"); ?></label>
                    <input type="number" id="mass_quantity" min="1" max="20" value="5" required>
                </div>

                <div class="form-group">
                    <label for="mass_difficulty"><?php echo t("difficulty"); ?></label>
                    <select id="mass_difficulty">
                        <option value="easy"><?php echo t("easy"); ?></option>
                        <option value="medium"><?php echo t("medium"); ?></option>
                        <option value="hard"><?php echo t("hard"); ?></option>
                    </select>
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
    </section>

    <section class="admin-section">
        <h2><?php echo t("import_csv"); ?></h2>
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
    </section>

    <section class="admin-section">
        <h2><?php echo t("registered_questions"); ?></h2>

        <div style="overflow-x:auto;">
            <table id="questionsTable" class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo t("question"); ?></th>
                        <th><?php echo t("correct_option"); ?></th>
                        <th><?php echo t("difficulty"); ?></th>
                        <th><?php echo t("language"); ?></th>
                        <th><?php echo t("category"); ?></th>
                        <th><?php echo t("actions"); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
    massGeneratedSuccess: "<?php echo t('mass_generated_success'); ?>"
};
</script>

<script src="/colesterol_game/assets/js/admin/admin_questions.js"></script>
</body>
</html>