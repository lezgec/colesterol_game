let questions = [];
let usedQuestionIds = [];

let currentQuestion = null;
let current = 0;
let score = 0;
let lives = 3;
let correctAnswers = 0;

let currentDifficulty = 1;

let questionStartTime = null;
let feedbackCountdownInterval = null;
let feedbackAdvanceLocked = false;
let selectedOptionIndex = null;
let answerSubmitted = false;
let questionTimerInterval = null;
let questionTimeout = null;
let questionTimeLeft = 20;

const SOLO_QUESTION_LIMIT = 12;
const SOLO_TIME_LIMIT = 20;

const gameScreen = document.getElementById("game-screen");

const questionText =
    document.getElementById("question-text");

const optionsContainer =
    document.getElementById("options-container");

const scoreText =
    document.getElementById("score");

const livesText =
    document.getElementById("lives");

const progressText =
    document.getElementById("progress");

const progressFill =
    document.getElementById("game-progress-fill");

const feedback =
    document.getElementById("feedback");

const selectedDifficultyText =
    document.getElementById("selected-difficulty");

const questionTimerText =
    document.getElementById("question-timer");

const languageSelector =
    document.getElementById("language-selector");

function normalizeAdaptiveDifficulty(value) {
    const parsed = Number(value) || 1;
    return Number(Math.min(5, Math.max(1, parsed)).toFixed(1));
}

function getTargetQuestionLevel(value = currentDifficulty) {
    const adaptive = normalizeAdaptiveDifficulty(value);

    if (adaptive < 1.5) return 1;
    if (adaptive < 2.5) return 2;
    if (adaptive < 3.5) return 3;
    if (adaptive < 4.5) return 4;

    return 5;
}

function formatDifficulty(value = currentDifficulty) {
    return normalizeAdaptiveDifficulty(value).toFixed(1);
}

function normalizeQuestionDifficultyLevel(value) {
    const parsed = Math.round(Number(value) || 1);
    return Math.min(5, Math.max(1, parsed));
}

function setLanguageSwitchDisabled(isDisabled) {

    if (!languageSelector) {
        return;
    }

    languageSelector.classList.toggle(
        "is-disabled",
        isDisabled
    );

    languageSelector
        .querySelectorAll("a")
        .forEach(link => {
            link.setAttribute(
                "aria-disabled",
                isDisabled ? "true" : "false"
            );
        });
}

function clearFeedbackTimers() {
    if (feedbackCountdownInterval) {
        clearInterval(feedbackCountdownInterval);
        feedbackCountdownInterval = null;
    }
}

function clearQuestionTimers() {
    if (questionTimerInterval) {
        clearInterval(questionTimerInterval);
        questionTimerInterval = null;
    }

    if (questionTimeout) {
        clearTimeout(questionTimeout);
        questionTimeout = null;
    }
}

function updateHUD() {

    scoreText.textContent = score;

    if (livesText) {
        const fullLife = window.uiIcon
            ? window.uiIcon("heart", "ui-icon life-icon is-full")
            : "";
        const emptyLife = window.uiIcon
            ? window.uiIcon("heart", "ui-icon life-icon is-empty")
            : "";

        livesText.innerHTML =
            fullLife.repeat(lives) +
            emptyLife.repeat(3 - lives);
    }

    selectedDifficultyText.textContent =
        `${formatDifficulty()} / 5`;

    progressText.textContent =
        questions.length > 0
            ? `${I18N.question} ${current + 1} ${I18N.of} ${questions.length}`
            : I18N.loadingQuestions;

    if (progressFill) {
        const totalQuestions =
            questions.length || SOLO_QUESTION_LIMIT;

        const progressValue =
            questions.length > 0
                ? Math.min(
                    100,
                    Math.max(
                        0,
                        ((current + 1) / totalQuestions) * 100
                    )
                )
                : 0;

        progressFill.style.width =
            `${progressValue}%`;

        progressFill.classList.toggle(
            "is-active",
            progressValue > 0
        );

        progressFill.setAttribute(
            "aria-valuenow",
            Math.round(progressValue).toString()
        );

        progressFill.parentElement?.setAttribute(
            "aria-valuenow",
            Math.round(progressValue).toString()
        );
    }
}

function startQuestionTimer() {
    questionStartTime = Date.now();
    questionTimeLeft = SOLO_TIME_LIMIT;

    if (questionTimerText) {
        questionTimerText.textContent = `${questionTimeLeft}s`;
    }

    clearQuestionTimers();

    questionTimerInterval = setInterval(() => {
        questionTimeLeft--;

        if (questionTimerText) {
            questionTimerText.textContent = `${Math.max(questionTimeLeft, 0)}s`;
        }

        window.GameSounds?.timerTick(questionTimeLeft, SOLO_TIME_LIMIT);
    }, 1000);

    questionTimeout = setTimeout(() => {
        submitSelectedAnswer(null, true);
    }, SOLO_TIME_LIMIT * 1000);
}

function getResponseTime() {

    if (!questionStartTime) {
        return 0;
    }

    const seconds =
        (Date.now() - questionStartTime) / 1000;

    return parseFloat(seconds.toFixed(2));
}

function selectQuestionByDifficulty() {

    const availableQuestions =
        questions.filter(
            q => !usedQuestionIds.includes(q.id)
        );

    if (availableQuestions.length === 0) {
        return null;
    }

    availableQuestions.sort((a, b) => {

        const diffA =
            Math.abs(
                normalizeQuestionDifficultyLevel(a.difficulty_level) -
                getTargetQuestionLevel()
            );

        const diffB =
            Math.abs(
                normalizeQuestionDifficultyLevel(b.difficulty_level) -
                getTargetQuestionLevel()
            );

        return diffA - diffB;
    });

    return availableQuestions[0];
}

function loadQuestion() {

    clearFeedbackTimers();
    clearQuestionTimers();
    feedbackAdvanceLocked = false;
    selectedOptionIndex = null;
    answerSubmitted = false;

    if (lives <= 0) {

        endGame(I18N.gameOver);

        return;
    }

    if (current >= questions.length) {

        endGame(I18N.gameCompleted);

        return;
    }

    currentQuestion =
        selectQuestionByDifficulty();

    if (!currentQuestion) {

        endGame(I18N.gameCompleted);

        return;
    }

    usedQuestionIds.push(currentQuestion.id);

    questionText.textContent =
        currentQuestion.question;

    optionsContainer.innerHTML = "";

    feedback.textContent = "";

    currentQuestion.options.forEach((opt, i) => {

        const btn =
            document.createElement("button");

        btn.innerHTML = `
            <span class="option-radio"></span>
            <span>${opt}</span>
        `;

        btn.classList.add("option-btn");

        btn.onclick = () => selectAnswer(i);

        optionsContainer.appendChild(btn);
    });

    const submitButton =
        document.createElement("button");

    submitButton.type = "button";
    submitButton.id = "submit-answer-btn";
    submitButton.className = "primary-btn submit-answer-btn";
    submitButton.textContent = I18N.submitAnswer;
    submitButton.disabled = true;
    submitButton.addEventListener("click", () => {
        submitSelectedAnswer(selectedOptionIndex, false);
    });

    optionsContainer.appendChild(submitButton);

    startQuestionTimer();

    updateHUD();
}

function selectAnswer(index) {
    if (answerSubmitted || !currentQuestion) {
        return;
    }

    window.GameSounds?.play("select");

    selectedOptionIndex = index;

    document
        .querySelectorAll(".option-btn")
        .forEach((btn, buttonIndex) => {
            btn.classList.toggle("is-selected", buttonIndex === index);
            btn.setAttribute(
                "aria-pressed",
                buttonIndex === index ? "true" : "false"
            );
        });

    const submitButton =
        document.getElementById("submit-answer-btn");

    if (submitButton) {
        submitButton.disabled = false;
    }

    feedback.textContent = "";
}

function getOptionLabel(index) {
    return ["A", "B", "C", "D"][index] || "";
}

function getOriginalOptionLabel(index) {
    return currentQuestion?.option_letters?.[index] || getOptionLabel(index);
}

function formatDisplayOption(index) {
    if (index === null || index === undefined || index < 0) {
        return "";
    }

    const label = getOptionLabel(index);
    const text = currentQuestion?.options?.[index] || "";

    return text ? `${label}. ${text}` : label;
}

function getCorrectOptionIndex() {
    if (!currentQuestion) {
        return -1;
    }

    if (Number.isInteger(currentQuestion.correct)) {
        return currentQuestion.correct;
    }

    const displayLabel = currentQuestion.display_correct_option;

    if (displayLabel) {
        const displayIndex = ["A", "B", "C", "D"].indexOf(displayLabel);

        if (displayIndex >= 0) {
            return displayIndex;
        }
    }

    const originalLabel = currentQuestion.correct_option;

    if (Array.isArray(currentQuestion.option_letters)) {
        const mappedIndex = currentQuestion.option_letters.indexOf(originalLabel);

        if (mappedIndex >= 0) {
            return mappedIndex;
        }
    }

    return ["A", "B", "C", "D"].indexOf(originalLabel);
}

function renderFeedbackCard({
    isCorrect,
    selectedOption,
    correctOption,
    earnedPoints,
    responseTime,
    isTimeout = false
}) {

    const statusText =
        isTimeout
            ? I18N.timeOut
            : isCorrect
            ? `${I18N.correct}. +${earnedPoints}`
            : I18N.incorrect;

    const statusIcon =
        isTimeout ? "clock" : isCorrect ? "check" : "x";

    const selectedText =
        selectedOption
            ? `<p><strong>${I18N.selectedAnswer}:</strong> ${selectedOption}</p>`
            : "";

    const correctText =
        !isCorrect
            ? `<p><strong>${I18N.correctAnswer}:</strong> ${correctOption}</p>`
            : "";

    optionsContainer.innerHTML = `
        <div class="feedback-card ${isCorrect ? "correct" : "incorrect"}">
            <div class="feedback-card-header">
                <span class="feedback-status-icon">${window.uiIcon ? window.uiIcon(statusIcon, "ui-icon feedback-svg") : ""}</span>
                <div>
                    <span class="feedback-eyebrow">${I18N.feedback}</span>
                    <h3>${statusText}</h3>
                </div>
            </div>

            ${selectedText}
            ${correctText}

            <p><strong>${window.uiIcon ? window.uiIcon("clock", "ui-icon feedback-inline-icon") : ""}</strong> ${responseTime}s</p>

            <p>${currentQuestion.explanation}</p>

            <p>
                <strong>${I18N.newDifficulty}:</strong>
                ${formatDifficulty()} / 5
            </p>

            <div class="feedback-actions">
                <span id="feedback-countdown" class="feedback-reading-note">
                    ${I18N.continue}
                </span>

                <button
                    type="button"
                    id="continue-question-btn"
                    class="primary-btn feedback-continue-btn"
                >
                    ${I18N.continue}
                </button>
            </div>
        </div>
    `;

    feedback.textContent = "";

    const continueButton =
        document.getElementById("continue-question-btn");

    if (continueButton) {
        continueButton.addEventListener(
            "click",
            advanceAfterFeedback
        );
    }
}

function advanceAfterFeedback() {

    if (feedbackAdvanceLocked) {
        return;
    }

    feedbackAdvanceLocked = true;
    clearFeedbackTimers();
    window.GameSounds?.play("continue");

    current++;
    loadQuestion();
}

function calculatePoints(isCorrect, responseTime) {

    if (!isCorrect) {
        return 0;
    }

    if (responseTime <= 3) {
        return 20;
    }

    if (responseTime <= 6) {
        return 15;
    }

    return 10;
}

function updateDifficulty(isCorrect, responseTime) {
    let delta = 0;

    if (isCorrect) {
        if (responseTime <= 4) {
            delta = 0.3;
        } else if (responseTime <= 8) {
            delta = 0.2;
        } else {
            delta = 0.1;
        }
    } else {
        delta = responseTime >= SOLO_TIME_LIMIT ? -0.4 : -0.3;
    }

    currentDifficulty = normalizeAdaptiveDifficulty(currentDifficulty + delta);
}

function disableOptions() {

    document
        .querySelectorAll(".option-btn")
        .forEach(btn => {
            btn.disabled = true;
        });

    const submitButton =
        document.getElementById("submit-answer-btn");

    if (submitButton) {
        submitButton.disabled = true;
    }
}

function submitSelectedAnswer(index, isTimeout = false) {

    if (!currentQuestion || answerSubmitted) {
        return;
    }

    if (index === null && !isTimeout) {
        feedback.textContent = I18N.chooseAnswer;
        window.GameSounds?.play("incorrect");
        return;
    }

    answerSubmitted = true;
    clearQuestionTimers();
    disableOptions();

    const responseTime =
        isTimeout ? SOLO_TIME_LIMIT : getResponseTime();

    const isCorrect =
        !isTimeout && index === currentQuestion.correct;

    window.GameSounds?.play(
        isTimeout ? "timeout" : (isCorrect ? "correct" : "incorrect")
    );

    const selectedOption =
        isTimeout ? null : formatDisplayOption(index);

    const correctOption =
        formatDisplayOption(getCorrectOptionIndex());

    const selectedOriginalOption =
        isTimeout ? "" : getOriginalOptionLabel(index);

    const earnedPoints =
        calculatePoints(
            isCorrect,
            responseTime
        );

    if (isCorrect) {

        score += earnedPoints;

        correctAnswers++;

        updateDifficulty(
            true,
            responseTime
        );

    } else {

        lives--;

        updateDifficulty(
            false,
            responseTime
        );

    }

    renderFeedbackCard({
        isCorrect,
        selectedOption,
        correctOption,
        earnedPoints,
        responseTime,
        isTimeout
    });

    saveAnswer({
        question_id: currentQuestion.id,

        selected_option:
            selectedOriginalOption,

        correct_option:
            currentQuestion.correct_option,

        is_correct:
            isCorrect ? 1 : 0,

        response_time:
            responseTime,

        difficulty_level:
            currentDifficulty,

        score_earned:
            earnedPoints
    });

    updateHUD();

}

async function endGame(message) {

    clearFeedbackTimers();
    clearQuestionTimers();
    feedbackAdvanceLocked = false;
    setLanguageSwitchDisabled(false);

    if (message.includes(I18N.gameCompleted)) {
        window.GameSounds?.play("finish");
        window.GameSounds?.confetti({ count: 120, mode: "side" });
    } else {
        window.GameSounds?.play("gameOver");
    }

    questionText.innerHTML = `
        <span class="game-over-title">
            ${message}
        </span>
    `;

    optionsContainer.innerHTML = "";

    progressText.textContent =
        I18N.gameFinished;

    if (progressFill) {
        progressFill.style.width = "100%";
        progressFill.classList.add("is-active");
        progressFill.setAttribute("aria-valuenow", "100");
        progressFill.parentElement?.setAttribute("aria-valuenow", "100");
    }

    feedback.innerHTML = `
        <div class="game-over-card">
            <div class="game-over-stats">
                <div class="game-over-stat">
                    <span>${I18N.finalScore}</span>
                    <strong>${score}</strong>
                </div>

                <div class="game-over-stat">
                    <span>${I18N.correctAnswers}</span>
                    <strong>${correctAnswers} ${I18N.of} ${questions.length}</strong>
                </div>

                <div class="game-over-stat">
                    <span>${I18N.remainingLives}</span>
                    <strong>${lives}</strong>
                </div>

                <div class="game-over-stat">
                    <span>${I18N.finalDifficulty}</span>
                    <strong>${formatDifficulty()} / 5</strong>
                </div>
            </div>

            <div id="save-status"
                 class="save-status-pill is-saving">
                ${I18N.savingResult}
            </div>
        </div>
    `;

    optionsContainer.innerHTML = `
        <div class="game-over-actions">
            <button
                type="button"
                id="play-again-btn"
                class="primary-btn play-again-btn"
            >
                ${I18N.playAgain}
            </button>
        </div>
    `;

    const playAgainButton =
        document.getElementById("play-again-btn");

    if (playAgainButton) {
        playAgainButton.addEventListener("click", () => {
            fetchQuestions(1);
        });
    }

    try {

        const response = await fetch(
            "/colesterol_game/backend/game/save_result.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    score: score,

                    correct_answers:
                        correctAnswers,

                    total_questions:
                        questions.length,

                    lives_remaining:
                        lives,

                    final_difficulty:
                        currentDifficulty
                })
            }
        );

        const result =
            await response.json();
            if (result.new_badges && result.new_badges.length > 0) {
                showBadgePopup(result.new_badges);
            }

        const saveStatus =
            document.getElementById(
                "save-status"
            );

        if (result.success) {

            saveStatus.className =
                "save-status-pill is-saved";

            saveStatus.textContent =
                I18N.resultSaved;

        } else {

            saveStatus.className =
                "save-status-pill is-error";

            saveStatus.textContent =
                I18N.resultNotSaved;

            console.error(result);
        }

    } catch (error) {

        const saveStatus =
            document.getElementById(
                "save-status"
            );

        if (saveStatus) {

            saveStatus.className =
                "save-status-pill is-error";

            saveStatus.textContent =
                I18N.resultNotSaved;
        }

        console.error(
            "Error guardando resultado:",
            error
        );
    }
}

async function saveAnswer(answerData) {

    try {

        await fetch(
            "/colesterol_game/backend/game/save_answer.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({

                    user_id:
                        typeof USER_ID !== "undefined"
                            ? USER_ID
                            : null,

                    room_id: null,

                    player_name:
                        typeof PLAYER_NAME !== "undefined"
                            ? PLAYER_NAME
                            : null,

                    game_mode: "solo",

                    ...answerData
                })
            }
        );

    } catch (error) {

        console.error(
            "Save answer error:",
            error
        );
    }
}

async function fetchQuestions(
    difficultyLevel = 1
) {

    try {

        clearFeedbackTimers();
        feedbackAdvanceLocked = false;
        setLanguageSwitchDisabled(true);

        currentDifficulty = normalizeAdaptiveDifficulty(difficultyLevel);

        questionText.textContent =
            I18N.loadingQuestions;

        feedback.textContent = "";

        optionsContainer.innerHTML = "";

        const response = await fetch(
            `/colesterol_game/backend/questions/get_questions.php?difficulty_level=${getTargetQuestionLevel()}&lang=${CURRENT_LANG}&limit=${SOLO_QUESTION_LIMIT}`
        );

        const data =
            await response.json();

        if (!Array.isArray(data)) {

            console.error(
                "Respuesta inesperada:",
                data
            );

            questionText.textContent =
                I18N.resultNotSaved;

            feedback.textContent =
                data.message || "Error";

            setLanguageSwitchDisabled(false);

            return;
        }

        if (data.length === 0) {

            questionText.textContent =
                I18N.noQuestions;

            feedback.textContent =
                I18N.noQuestions;

            setLanguageSwitchDisabled(false);

            return;
        }

        questions = data;

        usedQuestionIds = [];

        current = 0;
        score = 0;
        lives = 3;
        correctAnswers = 0;

        gameScreen.style.display =
            "block";

        loadQuestion();

    } catch (error) {

        console.error(error);

        questionText.textContent =
            I18N.resultNotSaved;

        feedback.textContent = "Error";

        setLanguageSwitchDisabled(false);
    }
}
function showBadgePopup(badges) {
    window.GameSounds?.play("badge");
    const popup = document.createElement("div");

    popup.classList.add("badge-popup");

    popup.innerHTML = `
        <div class="badge-popup-content">
            <h2>${window.uiIcon ? window.uiIcon("trophy", "ui-icon badge-popup-icon") : ""} ${I18N.newBadgeUnlocked || "Nuevo logro desbloqueado"}</h2>

            ${badges.map(badge => `
                <div class="badge-popup-item">
                    <strong>${badge.badge_name}</strong>
                    <p>${badge.badge_description}</p>
                </div>
            `).join("")}

            <button class="primary-btn" id="close-badge-popup">
                ${I18N.close || "OK"}
            </button>
        </div>
    `;

    document.body.appendChild(popup);

    document
        .getElementById("close-badge-popup")
        .addEventListener("click", () => {
            popup.remove();
        });
}

fetchQuestions(1);

