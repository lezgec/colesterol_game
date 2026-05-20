<?php
require_once __DIR__ . '/../../lang/translate.php';


$roomCode = strtoupper(trim($_GET["code"] ?? ""));
$playerName = trim($_GET["name"] ?? "");

if ($roomCode === "" || $playerName === "") {
    header("Location: /colesterol_game/pages/rooms/join.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("game"); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div>
            <strong><?php echo t("room_code"); ?>:</strong> <?php echo htmlspecialchars($roomCode); ?><br>
            <strong><?php echo t("player_name"); ?>:</strong> <?php echo htmlspecialchars($playerName); ?>
        </div>

        <a href="/colesterol_game/pages/rooms/index.php" class="logout-btn">
            <?php echo t("back"); ?>
        </a>
    </div>

    <h1><?php echo t("game"); ?></h1>

    <div id="game-screen">
        <div class="hud">
            <p><strong><?php echo t("score"); ?>:</strong> <span id="score">0</span></p>
            <p><strong><?php echo t("time"); ?>:</strong> <span id="timer">--</span></p>
            <p id="progress"></p>
        </div>

        <div class="question-box">
            <h2 id="question-text"><?php echo t("loading"); ?></h2>
            <div id="options-container"></div>
            <p id="feedback"></p>
            <div id="live-ranking-box" class="admin-section" style="display:none;">
                <h2><?php echo t("live_ranking"); ?></h2>
                <div id="live-ranking-list"></div>
            </div>
        </div>
    </div>

</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const PLAYER_NAME = "<?php echo htmlspecialchars($playerName); ?>";

const ROOM_I18N = {
    loading: "<?php echo t('loading'); ?>",
    score: "<?php echo t('score'); ?>",
    finalScore: "<?php echo t('final_score'); ?>",
    correctAnswers: "<?php echo t('correct_answers'); ?>",
    correct: "<?php echo t('correct'); ?>",
    incorrect: "<?php echo t('incorrect'); ?>",
    question: "<?php echo current_lang() === 'en' ? 'Question' : 'Pregunta'; ?>",
    of: "<?php echo current_lang() === 'en' ? 'of' : 'de'; ?>",
    gameCompleted: "<?php echo t('game_completed'); ?>",
    gameFinished: "<?php echo current_lang() === 'en' ? 'Game finished' : 'Juego terminado'; ?>",
    noQuestions: "<?php echo current_lang() === 'en' ? 'No questions available' : 'No hay preguntas disponibles'; ?>",
    loadingError: "<?php echo current_lang() === 'en' ? 'Error loading questions' : 'Error cargando preguntas'; ?>",
    timeOut: "<?php echo t('time_out'); ?>",
    savingResult: "<?php echo t('saving_result'); ?>",
    noResults: "<?php echo current_lang() === 'en' ? 'No results yet' : 'No hay resultados todavía'; ?>",
};
</script>

<script src="/colesterol_game/assets/js/rooms/room_game.js"></script>
</body>
</html>