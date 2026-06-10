let questions = [];
let usedQuestionIds = [];

let currentQuestion = null;
let current = 0;
let score = 0;
let lives = 3;
let correctAnswers = 0;

let currentDifficulty = 1.0;

let questionStartTime = null;
let feedbackTimeout = null;
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

    if (feedbackTimeout) {
        clearTimeout(feedbackTimeout);
        feedbackTimeout = null;
    }

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

    livesText.textContent =
        "❤️".repeat(lives) +
        "🖤".repeat(3 - lives);

    selectedDifficultyText.textContent =
        `${currentDifficulty.toFixed(1)} / 5`;

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
                parseFloat(a.difficulty_level || 1.0) -
                currentDifficulty
            );

        const diffB =
            Math.abs(
                parseFloat(b.difficulty_level || 1.0) -
                currentDifficulty
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

        endGame("💀 " + I18N.gameOver);

        return;
    }

    if (current >= questions.length) {

        endGame("🎉 " + I18N.gameCompleted);

        return;
    }

    currentQuestion =
        selectQuestionByDifficulty();

    if (!currentQuestion) {

        endGame("🎉 " + I18N.gameCompleted);

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

function renderFeedbackCard({
    isCorrect,
    selectedOption,
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
        isTimeout ? "⏱️" : isCorrect ? "✅" : "❌";

    const selectedText =
        selectedOption
            ? `<p><strong>${I18N.selectedAnswer}:</strong> ${selectedOption}</p>`
            : "";

    const correctText =
        !isCorrect
            ? `<p><strong>${I18N.correctAnswer}:</strong> ${currentQuestion.correct_option}</p>`
            : "";

    optionsContainer.innerHTML = `
        <div class="feedback-card ${isCorrect ? "correct" : "incorrect"}">
            <div class="feedback-card-header">
                <span class="feedback-status-icon">${statusIcon}</span>
                <div>
                    <span class="feedback-eyebrow">${I18N.feedback}</span>
                    <h3>${statusText}</h3>
                </div>
            </div>

            ${selectedText}
            ${correctText}

            <p><strong>⏱️</strong> ${responseTime}s</p>

            <p>${currentQuestion.explanation}</p>

            <p>
                <strong>${I18N.newDifficulty}:</strong>
                ${currentDifficulty.toFixed(1)} / 5
            </p>

            <div class="feedback-actions">
                <span id="feedback-countdown">
                    ${I18N.nextQuestionIn} 12s
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

    startFeedbackCountdown(12);
}

function startFeedbackCountdown(seconds) {

    let secondsLeft = seconds;

    const countdown =
        document.getElementById("feedback-countdown");

    feedbackCountdownInterval = setInterval(() => {

        secondsLeft--;

        if (countdown) {
            countdown.textContent =
                `${I18N.nextQuestionIn} ${Math.max(secondsLeft, 0)}s`;
        }

        if (secondsLeft <= 0) {
            advanceAfterFeedback();
        }

    }, 1000);

    feedbackTimeout = setTimeout(
        advanceAfterFeedback,
        seconds * 1000
    );
}

function advanceAfterFeedback() {

    if (feedbackAdvanceLocked) {
        return;
    }

    feedbackAdvanceLocked = true;
    clearFeedbackTimers();

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

    if (isCorrect) {

        if (responseTime <= 3) {

            currentDifficulty += 0.50;

        } else if (responseTime <= 6) {

            currentDifficulty += 0.25;

        } else {

            currentDifficulty += 0.10;
        }

    } else {

        currentDifficulty -= 0.25;
    }

    if (currentDifficulty < 1.0) {
        currentDifficulty = 1.0;
    }

    if (currentDifficulty > 5.0) {
        currentDifficulty = 5.0;
    }

    currentDifficulty =
        Math.round(currentDifficulty * 10) / 10;
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
        return;
    }

    answerSubmitted = true;
    clearQuestionTimers();
    disableOptions();

    const responseTime =
        isTimeout ? SOLO_TIME_LIMIT : getResponseTime();

    const isCorrect =
        !isTimeout && index === currentQuestion.correct;

    const selectedOption =
        isTimeout ? null : getOptionLabel(index);

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
        earnedPoints,
        responseTime,
        isTimeout
    });

    saveAnswer({
        question_id: currentQuestion.id,

        selected_option:
            selectedOption || "",

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
                    <strong>${currentDifficulty.toFixed(1)} / 5</strong>
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
            fetchQuestions(1.0);
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
                "✅ " + I18N.resultSaved;

        } else {

            saveStatus.className =
                "save-status-pill is-error";

            saveStatus.textContent =
                "❌ " + I18N.resultNotSaved;

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
                "❌ " + I18N.resultNotSaved;
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
    difficultyLevel = 1.0
) {

    try {

        clearFeedbackTimers();
        feedbackAdvanceLocked = false;
        setLanguageSwitchDisabled(true);

        currentDifficulty =
            parseFloat(
                difficultyLevel || 1.0
            );

        questionText.textContent =
            I18N.loadingQuestions;

        feedback.textContent = "";

        optionsContainer.innerHTML = "";

        const response = await fetch(
            `/colesterol_game/backend/questions/get_questions.php?difficulty_level=${currentDifficulty}&lang=${CURRENT_LANG}&limit=${SOLO_QUESTION_LIMIT}`
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
    const popup = document.createElement("div");

    popup.classList.add("badge-popup");

    popup.innerHTML = `
        <div class="badge-popup-content">
            <h2>🏆 Nuevo logro desbloqueado</h2>

            ${badges.map(badge => `
                <div class="badge-popup-item">
                    <strong>${badge.badge_name}</strong>
                    <p>${badge.badge_description}</p>
                </div>
            `).join("")}

            <button class="primary-btn" id="close-badge-popup">
                OK
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

fetchQuestions(1.0);
