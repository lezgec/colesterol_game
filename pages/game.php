<?php
require_once __DIR__ . '/../lang/translate.php';


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"])) {
    header("Location: /colesterol_game/pages/register.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("app_title"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-switch">
            <a href="?lang=es">ES</a> |
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="/colesterol_game/pages/history.php" class="logout-btn secondary-btn">
                <?php echo t("history"); ?>
            </a>
            <a href="/colesterol_game/pages/ranking.php" class="logout-btn secondary-btn">
                <?php echo t("ranking"); ?>
            </a>
            <a href="/colesterol_game/pages/dashboard.php" class="logout-btn secondary-btn">
                <?php echo t("dashboard"); ?>
            </a>
            <a href="/colesterol_game/pages/admin_questions.php" class="logout-btn" style="margin-left:10px; background:#0d6efd;">
                <?php echo t("admin_questions"); ?>
            </a>
            <a href="/colesterol_game/pages/rooms/index.php" class="logout-btn secondary-btn">
                <?php echo t("rooms_title"); ?>
            </a>
            <a href="/colesterol_game//pages/logout.php" class="logout-btn">
                <?php echo t("logout"); ?>
            </a>
        </div>
    </div>

    <h1><?php echo t("app_title"); ?></h1>

    <div id="difficulty-screen">
        <h2><?php echo t("select_difficulty"); ?></h2>
        <p><?php echo t("difficulty_description"); ?></p>

        <div class="difficulty-buttons">
            <button class="difficulty-btn" data-difficulty="easy"><?php echo t("easy"); ?></button>
            <button class="difficulty-btn" data-difficulty="medium"><?php echo t("medium"); ?></button>
            <button class="difficulty-btn" data-difficulty="hard"><?php echo t("hard"); ?></button>
        </div>
    </div>

    <div id="game-screen" style="display: none;">
        <div class="hud">
            <p><strong><?php echo t("score"); ?>:</strong> <span id="score">0</span></p>
            <p><strong><?php echo t("lives"); ?>:</strong> <span id="lives">❤️❤️❤️</span></p>
            <p><strong><?php echo t("difficulty"); ?>:</strong> <span id="selected-difficulty">-</span></p>
            <p id="progress"><?php echo t("loading_questions"); ?></p>
        </div>

        <div class="question-box">
            <h2 id="question-text"><?php echo t("loading_questions"); ?></h2>
            <div id="options-container"></div>
            <p id="feedback"></p>
        </div>
    </div>
</div>

<script>
const CURRENT_LANG = "<?php echo current_lang(); ?>";

const I18N = {
    easy: "<?php echo t('easy'); ?>",
    medium: "<?php echo t('medium'); ?>",
    hard: "<?php echo t('hard'); ?>",
    loadingQuestions: "<?php echo t('loading_questions'); ?>",
    gameOver: "<?php echo t('game_over'); ?>",
    gameCompleted: "<?php echo t('game_completed'); ?>",
    correct: "<?php echo t('correct'); ?>",
    incorrect: "<?php echo t('incorrect'); ?>",
    finalScore: "<?php echo t('final_score'); ?>",
    correctAnswers: "<?php echo t('correct_answers'); ?>",
    remainingLives: "<?php echo t('remaining_lives'); ?>",
    savingResult: "<?php echo t('saving_result'); ?>",
    resultSaved: "<?php echo t('result_saved'); ?>",
    resultNotSaved: "<?php echo t('result_not_saved'); ?>",
    noQuestions: "<?php echo current_lang() === 'en' ? 'No questions available' : 'No hay preguntas disponibles'; ?>",
    gameFinished: "<?php echo current_lang() === 'en' ? 'Game finished' : 'Juego terminado'; ?>",
    question: "<?php echo current_lang() === 'en' ? 'Question' : 'Pregunta'; ?>",
    of: "<?php echo current_lang() === 'en' ? 'of' : 'de'; ?>"
};
</script>

<script src="/colesterol_game/assets/js/app.js"></script>
</body>
</html>