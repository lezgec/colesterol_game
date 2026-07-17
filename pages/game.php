<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui_icons.php';

require_login();

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$soundVersion = filemtime(__DIR__ . '/../assets/js/sound_fx.js');
$appVersion = filemtime(__DIR__ . '/../assets/js/app.js');
$uiIconsJsVersion = filemtime(__DIR__ . '/../assets/js/ui_icons.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

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

    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>

<body>

<div class="game-container game-page-container">

    <div class="top-actions">

        <div class="language-pill" id="language-selector">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">

            <a href="<?php echo app_path('pages/history.php'); ?>"
               class="logout-btn secondary-btn">
                <?php echo t("history"); ?>
            </a>

            <a href="<?php echo app_path('pages/ranking.php'); ?>"
               class="logout-btn secondary-btn">
                <?php echo t("ranking"); ?>
            </a>

            <a href="<?php echo app_path('pages/dashboard.php'); ?>"
               class="logout-btn secondary-btn">
                <?php echo t("dashboard"); ?>
            </a>

            <?php if (in_array(current_user_role(), ["teacher", "super_admin"], true)): ?>

                <a href="<?php echo app_path('pages/admin_questions.php'); ?>"
                   class="logout-btn"
                   style="margin-left:10px; background:#0d6efd;">

                    <?php echo t("admin_questions"); ?>

                </a>

            <?php endif; ?>

            <a href="<?php echo app_path('pages/rooms/index.php'); ?>"
               class="logout-btn secondary-btn">
                <?php echo t("rooms_title"); ?>
            </a>

            <a href="<?php echo app_path('pages/logout.php'); ?>"
               class="logout-btn">
                <?php echo t("logout"); ?>
            </a>

        </div>

    </div>

    <h1><?php echo t("app_title"); ?></h1>

    <div id="game-screen">

        <div class="hud">

            <div class="hud-stat">
                <span><?php echo t("score"); ?></span>
                <strong id="score">0</strong>
            </div>

            <div class="hud-stat">
                <span><?php echo t("lives"); ?></span>
                <strong id="lives" class="lives-icons" aria-label="<?php echo t("lives"); ?>"></strong>
            </div>

            <div class="hud-stat">
                <span><?php echo t("difficulty"); ?></span>
                <strong id="selected-difficulty">1 / 5</strong>
            </div>

            <div class="hud-stat">
                <span><?php echo t("time_limit"); ?></span>
                <strong id="question-timer">20s</strong>
            </div>

        </div>

        <div class="game-progress-panel">
            <div class="progress-meta">
                <span><?php echo t("question_progress"); ?></span>
                <strong id="progress">
                    <?php echo t("loading_questions"); ?>
                </strong>
            </div>

            <div class="progress-track"
                 role="progressbar"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-valuenow="0">
                <div id="game-progress-fill"
                     class="progress-fill"></div>
            </div>
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
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";

const CURRENT_LANG = "<?php echo current_lang(); ?>";
const USER_ID = <?php echo (int)$_SESSION["user_id"]; ?>;
const PLAYER_NAME = "<?php echo htmlspecialchars($_SESSION["user_name"] ?? ""); ?>";

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

    noQuestions: "<?php echo t('no_questions_available'); ?>",
    noQuestionsTitle: "<?php echo t('no_questions_for_language_title'); ?>",

    gameFinished: "<?php echo t('game_finished'); ?>",

    question: "<?php echo t('question'); ?>",

    of: "<?php echo t('of'); ?>",

    newDifficulty: "<?php echo t('new_difficulty'); ?>",

    finalDifficulty: "<?php echo t('final_difficulty'); ?>",

    correctAnswer: "<?php echo t('correct_answer'); ?>",

    selectedAnswer: "<?php echo t('selected_answer'); ?>",
    feedback: "<?php echo t('feedback'); ?>",
    continue: "<?php echo t('continue'); ?>",
    submitAnswer: "<?php echo t('submit_answer'); ?>",
    chooseAnswer: "<?php echo t('choose_answer'); ?>",
    timeOut: "<?php echo t('time_out'); ?>",
    playAgain: "<?php echo t('play_again'); ?>",
    viewGameStats: "<?php echo t('view_game_stats'); ?>",
    newBadgeUnlocked: "<?php echo current_lang() === "en" ? "New achievement unlocked" : "Nuevo logro desbloqueado"; ?>",
    close: "<?php echo t('close'); ?>"
};
</script>

<script src="<?php echo asset_path('js/ui_icons.js'); ?>?m=<?php echo $uiIconsJsVersion; ?>"></script>
<script src="<?php echo asset_path('js/http.js'); ?>?m=<?php echo filemtime(__DIR__ . '/../assets/js/http.js'); ?>"></script>
<script src="<?php echo asset_path('js/sound_fx.js'); ?>?m=<?php echo $soundVersion; ?>"></script>
<script src="<?php echo asset_path('js/app.js'); ?>?m=<?php echo $appVersion; ?>"></script>

<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
