let questions = [];
let usedQuestionIds = [];
let currentQuestion = null;
let currentQuestionNumber = -1;
let score = 0;
let correctAnswers = 0;
let alreadySaved = false;
let timeLimit = 20;
let timeLeft = 20;
let currentDifficulty = 1.0;
let answeredCurrentQuestion = false;
let syncInterval = null;
let rankingInterval = null;
let transitionInProgress = false;
let questionStartTime = null;
let selectedOptionIndex = null;
let currentAnswerSavePromise = null;
let continueInProgress = false;
let localQuestionTimer = null;

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
            `/colesterol_game/backend/rooms/get_questions_by_room.php?code=${encodeURIComponent(ROOM_CODE)}`
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
        currentDifficulty = parseFloat(data.room.initial_difficulty || 1.0);
        startSync();
    } catch (e) {
        console.error(e);
        document.getElementById("question-text").textContent =
            ROOM_I18N.loadingError;
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
            `/colesterol_game/backend/rooms/get_room_game_state.php?code=${encodeURIComponent(ROOM_CODE)}`
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

        if (state.status === "waiting") {
            showRoomStatus(ROOM_I18N.waitingRoom);
            updateTimerUI();
            return;
        }
        if (state.status === "paused") {
            document.getElementById("feedback").innerHTML =
                `⏸️ ${ROOM_I18N.roomPaused}`;
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

function handleRoomTimeout() {
    if (answeredCurrentQuestion || !currentQuestion) return;

    clearLocalQuestionTimer();
    answeredCurrentQuestion = true;
    disableOptions();

    const responseTime = timeLimit;
    updateDifficulty(false, responseTime);

    renderRoomFeedbackCard({
        isCorrect: false,
        selectedOption: null,
        earnedPoints: 0,
        responseTime,
        isTimeout: true
    });

    currentAnswerSavePromise = saveAnswer({
        question_id: currentQuestion.id,
        selected_option: "",
        correct_option: currentQuestion.correct_option,
        is_correct: 0,
        response_time: responseTime,
        difficulty_level: currentDifficulty,
        score_earned: 0
    });

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
            parseFloat(a.difficulty_level || 1.0) - currentDifficulty
        );
        const diffB = Math.abs(
            parseFloat(b.difficulty_level || 1.0) - currentDifficulty
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
        const difficulty = parseFloat(currentQuestion.difficulty_level || 1.0);
        meta.textContent =
            `${currentQuestion.category} - ${ROOM_I18N.difficulty || "Dificultad"} ${difficulty.toFixed(1)} / 5`;
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
function renderRoomFeedbackCard({
    isCorrect,
    selectedOption,
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
        ? "⏱️"
        : isCorrect
            ? "✅"
            : "❌";

    const selectedText = selectedOption
        ? `<p><strong>${ROOM_I18N.selectedAnswer || "Tu respuesta"}:</strong> ${selectedOption}</p>`
        : "";

    const correctText = !isCorrect && currentQuestion
        ? `<p><strong>${ROOM_I18N.correctAnswer || "Respuesta correcta"}:</strong> ${currentQuestion.correct_option}</p>`
        : "";

    const continueAction = `
            <div class="feedback-actions">
                <span id="room-continue-status">
                    ${ROOM_I18N.continueWhenReady || "Continúa cuando termines de leer"}
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
                <span class="feedback-status-icon">${statusIcon}</span>
                <div>
                    <span class="feedback-eyebrow">${ROOM_I18N.feedback || "Retroalimentación"}</span>
                    <h3>${statusText}</h3>
                </div>
            </div>

            ${selectedText}
            ${correctText}

            <p><strong>⏱️</strong> ${responseTime}s</p>

            ${currentQuestion && currentQuestion.explanation
                ? `<p>${currentQuestion.explanation}</p>`
                : ""}

            <p>
                <strong>${ROOM_I18N.newDifficulty || "Nueva dificultad"}:</strong>
                ${currentDifficulty.toFixed(1)} / 5
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
    if (currentDifficulty < 1.0) currentDifficulty = 1.0;
    if (currentDifficulty > 5.0) currentDifficulty = 5.0;
    currentDifficulty = Math.round(currentDifficulty * 10) / 10;
}
async function submitRoomAnswer(index) {
    if (answeredCurrentQuestion || !currentQuestion) return;

    if (index === null || typeof index === "undefined") {
        document.getElementById("feedback").textContent =
            ROOM_I18N.chooseAnswer || "Selecciona una respuesta antes de enviar";
        return;
    }

    answeredCurrentQuestion = true;
    clearLocalQuestionTimer();
    disableOptions();
    const responseTime = getResponseTime();
    const isCorrect = index === currentQuestion.correct;
    const earnedPoints = calculateAdaptivePoints(isCorrect, responseTime);
    if (isCorrect) {
        score += earnedPoints;
        correctAnswers++;
        updateDifficulty(true, responseTime);
    } else {
        updateDifficulty(false, responseTime);
    }
    renderRoomFeedbackCard({
        isCorrect,
        selectedOption: ["A", "B", "C", "D"][index],
        earnedPoints,
        responseTime
    });
    currentAnswerSavePromise = saveAnswer({
        question_id: currentQuestion.id,
        selected_option: ["A", "B", "C", "D"][index],
        correct_option: currentQuestion.correct_option,
        is_correct: isCorrect ? 1 : 0,
        response_time: responseTime,
        difficulty_level: currentDifficulty,
        score_earned: earnedPoints
    });
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
        goToNextLocalQuestion();
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

function goToNextLocalQuestion() {
    clearLocalQuestionTimer();

    const nextIndex = currentQuestionNumber + 1;

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
        difficultyEl.textContent = `${currentDifficulty.toFixed(1)} / 5`;
    }
}
async function saveProgress() {
    try {
        await fetch("/colesterol_game/backend/rooms/save_room_progress.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
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
        await fetch("/colesterol_game/backend/game/save_answer.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                user_id: typeof USER_ID !== "undefined" ? USER_ID : null,
                room_id: typeof ROOM_ID !== "undefined" ? ROOM_ID : null,
                player_name: typeof PLAYER_NAME !== "undefined" ? PLAYER_NAME : null,
                game_mode: "room",
                ...answerData
            })
        });
    } catch (error) {
        console.error("Save answer error:", error);
    }
}
async function showInterQuestionLeaderboard() {
    transitionInProgress = false;
    const box = document.getElementById("live-ranking-box");
    const list = document.getElementById("live-ranking-list");
    if (!box || !list) return;
    try {
        const res = await fetch(
            `/colesterol_game/backend/rooms/get_room_ranking.php?code=${encodeURIComponent(ROOM_CODE)}`
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
    clearLocalQuestionTimer();
    document.getElementById("question-text").textContent = message;
    document.getElementById("options-container").innerHTML = "";
    document.getElementById("progress").textContent = ROOM_I18N.gameFinished;
    document.getElementById("feedback").innerHTML = `
        ${ROOM_I18N.finalScore}: ${score}<br>
        ${ROOM_I18N.correctAnswers}: ${correctAnswers} ${ROOM_I18N.of} ${questions.length}<br>
        ${ROOM_I18N.finalDifficulty || "Dificultad final"}: ${currentDifficulty.toFixed(1)} / 5<br>
        ${ROOM_I18N.savingResult}
    `;
    await saveProgress();
    setTimeout(() => {
        window.location.href =
            `/colesterol_game/pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}&name=${encodeURIComponent(PLAYER_NAME)}`;
    }, 1500);
}
fetchQuestions();
