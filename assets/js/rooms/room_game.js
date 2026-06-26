let questions = [];
let usedQuestionIds = [];
let currentQuestion = null;
let currentQuestionNumber = -1;
let score = 0;
let correctAnswers = 0;
let alreadySaved = false;
let timeLimit = 20;
let timeLeft = 20;
let currentDifficulty = 1;
let answeredCurrentQuestion = false;
let syncInterval = null;
let rankingInterval = null;
let transitionInProgress = false;
let questionStartTime = null;
let selectedOptionIndex = null;
let currentAnswerSavePromise = null;
let continueInProgress = false;
let localQuestionTimer = null;

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

function clearLocalQuestionTimer() {
    if (localQuestionTimer) {
        clearInterval(localQuestionTimer);
        localQuestionTimer = null;
    }
}

function getRoomStatusLabel(status) {
    return ROOM_I18N.statuses?.[status] || ROOM_I18N.unknownStatus || status || "";
}

async function fetchQuestions() {
    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_questions_by_room.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );
        const data = await res.json();
        if (!data.success) {
            document.getElementById("question-text").textContent =
                data.message || ROOM_I18N.loadingError;
            return;
        }
        if (!Array.isArray(data.questions) || data.questions.length === 0) {
            document.getElementById("question-text").textContent =
                ROOM_I18N.noQuestions;
            return;
        }
        questions = data.questions;
        timeLimit = parseInt(data.room.time_limit || 20, 10);
        currentDifficulty = normalizeAdaptiveDifficulty(data.room.initial_difficulty);
        startSync();
    } catch (e) {
        console.error(e);
        document.getElementById("question-text").textContent =
            ROOM_I18N.loadingError;
    }
}

async function refreshRoomQuestionsIfNeeded(expectedCount = 0) {
    if (!expectedCount || expectedCount <= questions.length) {
        return;
    }

    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_questions_by_room.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );
        const data = await res.json();

        if (!data.success || !Array.isArray(data.questions)) {
            return;
        }

        const knownIds = new Set(questions.map(question => Number(question.id)));
        const newQuestions = data.questions.filter(question => !knownIds.has(Number(question.id)));

        if (newQuestions.length > 0) {
            questions = questions.concat(newQuestions);
        }

        timeLimit = parseInt(data.room?.time_limit || timeLimit || 20, 10);
    } catch (error) {
        console.error("Question refresh error:", error);
    }
}

function startSync() {
    syncRoomState();
    showInterQuestionLeaderboard();
    syncInterval = setInterval(syncRoomState, 1000);
    rankingInterval = setInterval(showInterQuestionLeaderboard, 3000);
}
function startQuestionTimer() {
    clearLocalQuestionTimer();
    questionStartTime = Date.now();
    timeLeft = timeLimit;
    updateTimerUI();

    localQuestionTimer = setInterval(() => {
        if (answeredCurrentQuestion || !currentQuestion) {
            clearLocalQuestionTimer();
            return;
        }

        timeLeft = Math.max(0, timeLeft - 1);
        updateTimerUI();
        window.GameSounds?.timerTick(timeLeft, timeLimit);

        if (timeLeft <= 0) {
            handleRoomTimeout();
        }
    }, 1000);
}
function getResponseTime() {
    if (!questionStartTime) {
        return 0;
    }
    const seconds = (Date.now() - questionStartTime) / 1000;
    return parseFloat(seconds.toFixed(2));
}
async function syncRoomState() {
    if (transitionInProgress) return;
    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_room_game_state.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );
        const state = await res.json();
        if (!state.success) {
            document.getElementById("question-text").textContent =
                state.message || ROOM_I18N.loadingError;
            return;
        }
        if (currentQuestionNumber < 0) {
            timeLeft = parseInt(state.time_left || timeLimit, 10);
        }

        await refreshRoomQuestionsIfNeeded(Number(state.question_count || 0));

        if (state.status === "waiting") {
            showRoomStatus(ROOM_I18N.waitingRoom);
            updateTimerUI();
            return;
        }
        if (state.status === "paused") {
            document.getElementById("feedback").innerHTML =
                `${window.uiIcon ? window.uiIcon("pause", "ui-icon room-status-icon") : ""} ${ROOM_I18N.roomPaused}`;
            updateTimerUI();
            return;
        }
        if (state.status === "finished" || state.finished) {
            clearInterval(syncInterval);
            clearInterval(rankingInterval);
            endGame(ROOM_I18N.gameCompleted);
            return;
        }
        if (state.status !== "started") {
            showRoomStatus(getRoomStatusLabel(state.status));
            return;
        }

        if (currentQuestionNumber < 0) {
            currentQuestionNumber = 0;
            answeredCurrentQuestion = false;
            selectedOptionIndex = null;
            currentAnswerSavePromise = null;
            continueInProgress = false;
            renderAdaptiveQuestion();
        }

        updateHUD();
        updateTimerUI();
    } catch (error) {
        console.error("Sync error:", error);
    }
}

async function handleRoomTimeout() {
    if (answeredCurrentQuestion || !currentQuestion) return;

    clearLocalQuestionTimer();
    answeredCurrentQuestion = true;
    disableOptions();
    window.GameSounds?.play("timeout");

    const responseTime = timeLimit;
    const answerResult = await saveAnswer({
        question_id: currentQuestion.id,
        selected_option: "",
        response_time: responseTime,
        difficulty_level: currentDifficulty
    });

    currentQuestion.explanation = answerResult?.explanation || "";
    updateDifficulty(false, responseTime);

    renderRoomFeedbackCard({
        isCorrect: false,
        selectedOption: null,
        correctOption: answerResult?.correct_answer || "",
        earnedPoints: Number(answerResult?.score_earned || 0),
        responseTime,
        isTimeout: true
    });

    currentAnswerSavePromise = Promise.resolve(answerResult);

    updateHUD();
    saveProgress();
    showInterQuestionLeaderboard();
}
function showRoomStatus(message) {
    const meta = document.getElementById("room-question-meta");
    if (meta) {
        meta.textContent = "";
    }

    document.getElementById("question-text").textContent = message;
    document.getElementById("options-container").innerHTML = "";
    document.getElementById("feedback").textContent = "";
}
function selectQuestionByDifficulty() {
    if (
        currentQuestionNumber >= 0 &&
        currentQuestionNumber < questions.length
    ) {
        return questions[currentQuestionNumber];
    }

    const availableQuestions = questions.filter(q => !usedQuestionIds.includes(q.id));
    if (availableQuestions.length === 0) {
        return null;
    }
    availableQuestions.sort((a, b) => {
        const diffA = Math.abs(
            normalizeQuestionDifficultyLevel(a.difficulty_level) - getTargetQuestionLevel()
        );
        const diffB = Math.abs(
            normalizeQuestionDifficultyLevel(b.difficulty_level) - getTargetQuestionLevel()
        );
        return diffA - diffB;
    });
    return availableQuestions[0];
}
function renderAdaptiveQuestion() {
    currentQuestion = selectQuestionByDifficulty();
    if (!currentQuestion) {
        return;
    }
    usedQuestionIds.push(currentQuestion.id);
    transitionInProgress = false;
    answeredCurrentQuestion = false;
    selectedOptionIndex = null;
    currentAnswerSavePromise = null;
    continueInProgress = false;
    const meta = document.getElementById("room-question-meta");
    if (meta) {
        const difficulty = normalizeQuestionDifficultyLevel(currentQuestion.difficulty_level);
        meta.textContent =
            `${currentQuestion.category} - ${ROOM_I18N.difficulty || "Dificultad"} ${difficulty} / 5`;
    }

    document.getElementById("question-text").textContent =
        currentQuestion.question;
    const container = document.getElementById("options-container");
    container.innerHTML = "";
    document.getElementById("feedback").textContent = "";
    currentQuestion.options.forEach((opt, i) => {
        const btn = document.createElement("button");
        btn.innerHTML = `
            <span class="option-radio"></span>
            <span>${opt}</span>
        `;
        btn.classList.add("option-btn");
        btn.onclick = () => selectAnswer(i);
        container.appendChild(btn);
    });

    const submitButton = document.createElement("button");
    submitButton.type = "button";
    submitButton.id = "submit-room-answer-btn";
    submitButton.className = "primary-btn submit-answer-btn";
    submitButton.textContent = ROOM_I18N.submitAnswer || "Enviar respuesta";
    submitButton.disabled = true;
    submitButton.addEventListener("click", () => {
        submitRoomAnswer(selectedOptionIndex);
    });

    container.appendChild(submitButton);

    startQuestionTimer();
    updateHUD();
}

function selectAnswer(index) {
    if (answeredCurrentQuestion || !currentQuestion) return;

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

    const submitButton = document.getElementById("submit-room-answer-btn");

    if (submitButton) {
        submitButton.disabled = false;
    }

    document.getElementById("feedback").textContent = "";
}

function disableOptions() {
    document.querySelectorAll(".option-btn").forEach(btn => {
        btn.disabled = true;
    });

    const submitButton = document.getElementById("submit-room-answer-btn");

    if (submitButton) {
        submitButton.disabled = true;
    }
}
function updateTimerUI() {
    const timerEl = document.getElementById("timer");
    if (timerEl) {
        timerEl.textContent = `${timeLeft}s`;
    }
}
function getRoomOptionLabel(index) {
    return ["A", "B", "C", "D"][index] || "";
}

function formatRoomDisplayOption(index) {
    if (index === null || index === undefined || index < 0) {
        return "";
    }

    const label = getRoomOptionLabel(index);
    const text = currentQuestion?.options?.[index] || "";

    return text ? `${label}. ${text}` : label;
}

function getRoomCorrectOptionIndex() {
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

function renderRoomFeedbackCard({
    isCorrect,
    selectedOption,
    correctOption,
    earnedPoints,
    responseTime,
    isTimeout = false
}) {
    const statusText = isTimeout
        ? ROOM_I18N.timeOut
        : isCorrect
            ? `${ROOM_I18N.correct}. +${earnedPoints}`
            : ROOM_I18N.incorrect;

    const statusIcon = isTimeout
        ? "clock"
        : isCorrect
            ? "check"
            : "x";

    const selectedText = selectedOption
        ? `<p><strong>${ROOM_I18N.selectedAnswer || "Tu respuesta"}:</strong> ${selectedOption}</p>`
        : "";

    const correctText = !isCorrect && currentQuestion
        ? `<p><strong>${ROOM_I18N.correctAnswer || "Respuesta correcta"}:</strong> ${correctOption}</p>`
        : "";

    const continueAction = `
            <div class="feedback-actions">
                <span id="room-continue-status">
                    ${ROOM_I18N.continueWhenReady || "Continua cuando termines de leer"}
                </span>
                <button
                    type="button"
                    id="room-continue-btn"
                    class="primary-btn feedback-continue-btn"
                >
                    ${ROOM_I18N.continue || "Continuar"}
                </button>
            </div>
        `;

    document.getElementById("options-container").innerHTML = `
        <div class="feedback-card ${isCorrect ? "correct" : "incorrect"}">
            <div class="feedback-card-header">
                <span class="feedback-status-icon">${window.uiIcon ? window.uiIcon(statusIcon, "ui-icon feedback-svg") : ""}</span>
                <div>
                    <span class="feedback-eyebrow">${ROOM_I18N.feedback || "Retroalimentacion"}</span>
                    <h3>${statusText}</h3>
                </div>
            </div>

            ${selectedText}
            ${correctText}

            <p><strong>${window.uiIcon ? window.uiIcon("clock", "ui-icon feedback-inline-icon") : ""}</strong> ${responseTime}s</p>

            ${currentQuestion && currentQuestion.explanation
                ? `<p>${currentQuestion.explanation}</p>`
                : ""}

            <p>
                <strong>${ROOM_I18N.newDifficulty || "Nueva dificultad"}:</strong>
                ${formatDifficulty()} / 5
            </p>

            ${continueAction}
        </div>
    `;

    document.getElementById("feedback").textContent = "";

    const continueButton = document.getElementById("room-continue-btn");

    if (continueButton) {
        continueButton.addEventListener("click", handleRoomContinue);
    }
}
function calculateAdaptivePoints(isCorrect, responseTime) {
    if (!isCorrect) return 0;
    if (responseTime <= 3) return 20;
    if (responseTime <= 6) return 15;
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
        delta = responseTime >= timeLimit ? -0.4 : -0.3;
    }

    currentDifficulty = normalizeAdaptiveDifficulty(currentDifficulty + delta);
}
async function submitRoomAnswer(index) {
    if (answeredCurrentQuestion || !currentQuestion) return;

    if (index === null || typeof index === "undefined") {
        document.getElementById("feedback").textContent =
            ROOM_I18N.chooseAnswer || "Selecciona una respuesta antes de enviar";
        window.GameSounds?.play("incorrect");
        return;
    }

    answeredCurrentQuestion = true;
    clearLocalQuestionTimer();
    disableOptions();
    const responseTime = getResponseTime();
    const selectedOriginalOption =
        currentQuestion.option_letters?.[index] || ["A", "B", "C", "D"][index];

    const answerResult = await saveAnswer({
        question_id: currentQuestion.id,
        selected_option: selectedOriginalOption,
        response_time: responseTime,
        difficulty_level: currentDifficulty
    });

    if (!answerResult || !answerResult.success) {
        document.getElementById("feedback").textContent =
            answerResult?.message || ROOM_I18N.resultNotSaved || "No se pudo guardar la respuesta";
        answeredCurrentQuestion = false;
        return;
    }

    const isCorrect = Boolean(answerResult.is_correct);
    const earnedPoints = Number(answerResult.score_earned || 0);
    currentQuestion.explanation = answerResult.explanation || "";
    window.GameSounds?.play(isCorrect ? "correct" : "incorrect");

    if (isCorrect) {
        score += earnedPoints;
        correctAnswers++;
        updateDifficulty(true, responseTime);
    } else {
        updateDifficulty(false, responseTime);
    }
    renderRoomFeedbackCard({
        isCorrect,
        selectedOption: formatRoomDisplayOption(index),
        correctOption: answerResult.correct_answer || "",
        earnedPoints,
        responseTime
    });
    currentAnswerSavePromise = Promise.resolve(answerResult);
    updateHUD();
    await currentAnswerSavePromise;
    await saveProgress();
    showInterQuestionLeaderboard();
}

async function handleRoomContinue() {
    if (!answeredCurrentQuestion || !currentQuestion || continueInProgress) return;

    const continueButton = document.getElementById("room-continue-btn");
    const continueStatus = document.getElementById("room-continue-status");

    continueInProgress = true;
    window.GameSounds?.play("continue");

    if (continueButton) {
        continueButton.disabled = true;
    }

    if (continueStatus) {
        continueStatus.textContent =
            ROOM_I18N.savingAnswer || "Guardando respuesta...";
    }

    try {
        if (currentAnswerSavePromise) {
            await currentAnswerSavePromise;
        }

        await saveProgress();
        await goToNextLocalQuestion();
    } catch (error) {
        console.error("Continue room error:", error);

        if (continueStatus) {
            continueStatus.textContent =
                ROOM_I18N.loadingError || "Error cargando preguntas";
        }

        if (continueButton) {
            continueButton.disabled = false;
        }

        continueInProgress = false;
    }
}

async function goToNextLocalQuestion() {
    clearLocalQuestionTimer();

    const nextIndex = currentQuestionNumber + 1;

    if (nextIndex >= questions.length) {
        try {
            const res = await fetch(
                appUrl(`backend/rooms/get_room_game_state.php?code=${encodeURIComponent(ROOM_CODE)}`)
            );
            const state = await res.json();
            await refreshRoomQuestionsIfNeeded(Number(state.question_count || 0));
        } catch (error) {
            console.error("Late question refresh error:", error);
        }
    }

    if (nextIndex >= questions.length) {
        endGame(ROOM_I18N.gameCompleted);
        return;
    }

    currentQuestionNumber = nextIndex;
    renderAdaptiveQuestion();
}
function updateHUD() {
    document.getElementById("score").textContent = score;
    const safeCurrent = currentQuestionNumber >= 0
        ? currentQuestionNumber + 1
        : 1;
    document.getElementById("progress").textContent =
        `${ROOM_I18N.question} ${safeCurrent} ${ROOM_I18N.of} ${questions.length}`;
    const difficultyEl = document.getElementById("adaptive-difficulty");
    if (difficultyEl) {
        difficultyEl.textContent = `${formatDifficulty()} / 5`;
    }
}
async function saveProgress() {
    try {
        await fetch(appUrl("backend/rooms/save_room_progress.php"), {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({
                score: score,
                correct_answers: correctAnswers,
                total_questions: questions.length,
                room_code: ROOM_CODE,
                player_name: PLAYER_NAME,
                current_difficulty: currentDifficulty,
                final_difficulty: currentDifficulty
            })
        });
    } catch (error) {
        console.error("Error saving progress:", error);
    }
}
async function saveAnswer(answerData) {
    try {
        const response = await fetch(appUrl("backend/game/save_answer.php"), {
            method: "POST",
            headers: csrfHeaders({
                "Content-Type": "application/json"
            }),
            body: JSON.stringify({
                user_id: typeof USER_ID !== "undefined" ? USER_ID : null,
                room_id: typeof ROOM_ID !== "undefined" ? ROOM_ID : null,
                player_name: typeof PLAYER_NAME !== "undefined" ? PLAYER_NAME : null,
                game_mode: "room",
                ...answerData
            })
        });

        return await response.json();
    } catch (error) {
        console.error("Save answer error:", error);
        return {
            success: false,
            message: "No se pudo guardar la respuesta"
        };
    }
}
async function showInterQuestionLeaderboard() {
    transitionInProgress = false;
    const box = document.getElementById("live-ranking-box");
    const list = document.getElementById("live-ranking-list");
    if (!box || !list) return;
    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_room_ranking.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );
        const data = await res.json();
        box.style.display = "block";
        list.innerHTML = "";
        if (!Array.isArray(data) || data.length === 0) {
            list.innerHTML =
                `<p>${ROOM_I18N.noResults}</p>`;
        } else {
            data.slice(0, 5).forEach((player, index) => {
                const item = document.createElement("div");
                item.classList.add("live-ranking-item");
                if (player.player_name === PLAYER_NAME) {
                    item.classList.add("current-player-rank");
                }
                item.innerHTML = `
                    <strong>#${index + 1}</strong>
                    <span>${player.player_name}</span>
                    <strong>${player.best_score}</strong>
                `;
                list.appendChild(item);
            });
        }
    } catch (error) {
        console.error("Live ranking error:", error);
        transitionInProgress = false;
    }
}
function hideLiveRanking() {
    const box = document.getElementById("live-ranking-box");
    if (box) box.style.display = "block";
}
async function endGame(message) {
    if (alreadySaved) return;
    alreadySaved = true;
    window.GameSounds?.play("finish");
    window.GameSounds?.confetti({ count: 120, mode: "side" });
    clearLocalQuestionTimer();
    document.getElementById("question-text").textContent = message;
    document.getElementById("options-container").innerHTML = "";
    document.getElementById("progress").textContent = ROOM_I18N.gameFinished;
    document.getElementById("feedback").innerHTML = `
        ${ROOM_I18N.finalScore}: ${score}<br>
        ${ROOM_I18N.correctAnswers}: ${correctAnswers} ${ROOM_I18N.of} ${questions.length}<br>
        ${ROOM_I18N.finalDifficulty || "Dificultad final"}: ${formatDifficulty()} / 5<br>
        ${ROOM_I18N.savingResult}
    `;
    await saveProgress();
    setTimeout(() => {
        window.location.href =
            appUrl(`pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}&name=${encodeURIComponent(PLAYER_NAME)}`);
    }, 1500);
}
fetchQuestions();
