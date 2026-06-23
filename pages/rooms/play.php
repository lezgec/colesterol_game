<?php
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../../config/db.php';

$roomCode = strtoupper(trim($_GET["code"] ?? ""));
$playerName = trim($_GET["name"] ?? "");

if ($roomCode === "" || $playerName === "") {
    header("Location: /colesterol_game/pages/rooms/join.php");
    exit;
}

$roomId = 0;
$stmtRoom = $conn->prepare("
    SELECT id
    FROM game_rooms
    WHERE room_code = ?
    LIMIT 1
");

if ($stmtRoom) {
    $stmtRoom->bind_param("s", $roomCode);
    $stmtRoom->execute();
    $roomResult = $stmtRoom->get_result();

    if ($roomResult->num_rows > 0) {
        $roomId = (int)$roomResult->fetch_assoc()["id"];
    }

    $stmtRoom->close();
}

if ($roomId <= 0) {
    header("Location: /colesterol_game/pages/rooms/join.php");
    exit;
}

$styleVersion = filemtime(__DIR__ . '/../../assets/css/style.css');
$soundVersion = filemtime(__DIR__ . '/../../assets/js/sound_fx.js');
$roomGameVersion = filemtime(__DIR__ . '/../../assets/js/rooms/room_game.js');
$uiIconsJsVersion = filemtime(__DIR__ . '/../../assets/js/ui_icons.js');
$themeVersion = filemtime(__DIR__ . '/../../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("game"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">

</head>
<body>

<div class="game-container room-play-container">

    <div class="top-actions">
        <div>
            <strong><?php echo t("room_code"); ?>:</strong>
            <?php echo htmlspecialchars($roomCode); ?>
            <br>

            <strong><?php echo t("player_name"); ?>:</strong>
            <?php echo htmlspecialchars($playerName); ?>
        </div>

        <a href="/colesterol_game/pages/rooms/index.php" class="logout-btn">
            <?php echo t("back_to_rooms"); ?>
        </a>
    </div>

    <h1><?php echo t("game"); ?></h1>

    <div id="game-screen">

        <div class="hud">
            <p>
                <strong><?php echo t("score"); ?>:</strong>
                <span id="score">0</span>
            </p>

            <p>
                <strong><?php echo t("time"); ?>:</strong>
                <span id="timer">--</span>
            </p>

            <p>
                <strong><?php echo t("adaptive_difficulty"); ?>:</strong>
                <span id="adaptive-difficulty">1 / 5</span>
            </p>

            <p id="progress"></p>
        </div>

        <div class="room-play-layout">
            <div class="question-box">
                <p id="room-question-meta" class="question-meta"></p>
                <h2 id="question-text"><?php echo t("loading"); ?></h2>

                <div id="options-container"></div>

                <p id="feedback"></p>
            </div>

            <aside id="live-ranking-box" class="live-ranking-panel admin-section">
                <h2><?php echo t("live_ranking"); ?></h2>
                <div id="live-ranking-list"></div>
            </aside>
        </div>

    </div>

</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const PLAYER_NAME = "<?php echo htmlspecialchars($playerName); ?>";
const ROOM_ID = <?php echo $roomId; ?>;

const ROOM_I18N = {
    loading: "<?php echo t('loading'); ?>",
    score: "<?php echo t('score'); ?>",
    finalScore: "<?php echo t('final_score'); ?>",
    correctAnswers: "<?php echo t('correct_answers'); ?>",
    correct: "<?php echo t('correct'); ?>",
    incorrect: "<?php echo t('incorrect'); ?>",
    question: "<?php echo t('question'); ?>",
    of: "<?php echo t('of'); ?>",
    gameCompleted: "<?php echo t('game_completed'); ?>",
    gameFinished: "<?php echo t('game_finished'); ?>",
    noQuestions: "<?php echo t('no_questions_available'); ?>",
    loadingError: "<?php echo t('loading_error'); ?>",
    timeOut: "<?php echo t('time_out'); ?>",
    savingResult: "<?php echo t('saving_result'); ?>",
    noResults: "<?php echo t('no_results_yet'); ?>",
    difficulty: "<?php echo t('difficulty'); ?>",
    newDifficulty: "<?php echo t('new_difficulty'); ?>",
    finalDifficulty: "<?php echo t('final_difficulty'); ?>",
    correctAnswer: "<?php echo t('correct_answer'); ?>",
    selectedAnswer: "<?php echo t('selected_answer'); ?>",
    feedback: "<?php echo t('feedback'); ?>",
    continue: "<?php echo t('continue'); ?>",
    continueWhenReady: "<?php echo t('continue_when_ready'); ?>",
    submitAnswer: "<?php echo t('submit_answer'); ?>",
    chooseAnswer: "<?php echo t('choose_answer'); ?>",
    nextQuestionIn: "<?php echo t('next_question_in'); ?>",
    savingAnswer: "<?php echo t('saving_answer'); ?>",
    waitingRoom: "<?php echo t('waiting_room'); ?>",
    roomPaused: "<?php echo t('room_paused'); ?>",
    unknownStatus: "<?php echo t('room_status_unknown'); ?>",
    statuses: {
        waiting: "<?php echo t('room_status_waiting'); ?>",
        started: "<?php echo t('room_status_started'); ?>",
        paused: "<?php echo t('room_status_paused'); ?>",
        finished: "<?php echo t('room_status_finished'); ?>"
    }
};
</script>

<script src="/colesterol_game/assets/js/ui_icons.js?m=<?php echo $uiIconsJsVersion; ?>"></script>
<script src="/colesterol_game/assets/js/sound_fx.js?m=<?php echo $soundVersion; ?>"></script>
<script src="/colesterol_game/assets/js/rooms/room_game.js?m=<?php echo $roomGameVersion; ?>"></script>
<script src="/colesterol_game/assets/js/theme.js?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
