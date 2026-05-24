<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("app_title"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>

<body>

<div class="game-container">

    <div class="top-actions">

        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">

            <a href="/colesterol_game/pages/history.php"
               class="logout-btn secondary-btn">
                <?php echo t("history"); ?>
            </a>

            <a href="/colesterol_game/pages/ranking.php"
               class="logout-btn secondary-btn">
                <?php echo t("ranking"); ?>
            </a>

            <a href="/colesterol_game/pages/dashboard.php"
               class="logout-btn secondary-btn">
                <?php echo t("dashboard"); ?>
            </a>

            <?php if (in_array(current_user_role(), ["teacher", "super_admin"], true)): ?>

                <a href="/colesterol_game/pages/admin_questions.php"
                   class="logout-btn"
                   style="margin-left:10px; background:#0d6efd;">

                    <?php echo t("admin_questions"); ?>

                </a>

            <?php endif; ?>

            <a href="/colesterol_game/pages/rooms/index.php"
               class="logout-btn secondary-btn">
                <?php echo t("rooms_title"); ?>
            </a>

            <a href="/colesterol_game/pages/logout.php"
               class="logout-btn">
                <?php echo t("logout"); ?>
            </a>

        </div>

    </div>

    <h1><?php echo t("app_title"); ?></h1>

    <div id="game-screen">

        <div class="hud">

            <p>
                <strong><?php echo t("score"); ?>:</strong>
                <span id="score">0</span>
            </p>

            <p>
                <strong><?php echo t("lives"); ?>:</strong>
                <span id="lives">❤️❤️❤️</span>
            </p>

            <p>
                <strong><?php echo t("difficulty"); ?>:</strong>
                <span id="selected-difficulty">1.0 / 5</span>
            </p>

            <p id="progress">
                <?php echo t("loading_questions"); ?>
            </p>

        </div>

        <div class="question-box">

            <h2 id="question-text">
                <?php echo t("loading_questions"); ?>
            </h2>

            <div id="options-container"></div>

            <p id="feedback"></p>

        </div>

    </div>

</div>

<script>
const CURRENT_LANG = "<?php echo current_lang(); ?>";

const I18N = {

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

    noQuestions:
        "<?php echo current_lang() === 'en'
            ? 'No questions available'
            : 'No hay preguntas disponibles'; ?>",

    gameFinished:
        "<?php echo current_lang() === 'en'
            ? 'Game finished'
            : 'Juego terminado'; ?>",

    question:
        "<?php echo current_lang() === 'en'
            ? 'Question'
            : 'Pregunta'; ?>",

    of:
        "<?php echo current_lang() === 'en'
            ? 'of'
            : 'de'; ?>",

    newDifficulty:
        "<?php echo current_lang() === 'en'
            ? 'New difficulty'
            : 'Nueva dificultad'; ?>",

    finalDifficulty:
        "<?php echo current_lang() === 'en'
            ? 'Final difficulty'
            : 'Dificultad final'; ?>",

    correctAnswer:
        "<?php echo current_lang() === 'en'
            ? 'Correct answer'
            : 'Respuesta correcta'; ?>"
};
</script>

<script src="/colesterol_game/assets/js/app.js"></script>

</body>
</html>