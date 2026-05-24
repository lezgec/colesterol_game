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
let transitionInProgress = false;

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
    syncInterval = setInterval(syncRoomState, 1000);
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

        timeLeft = parseInt(state.time_left || timeLimit, 10);

        if (state.status === "waiting") {
            showRoomStatus(
                ROOM_I18N.waitingRoom || "Esperando que el docente inicie la sala..."
            );
            updateTimerUI();
            return;
        }

        if (state.status === "paused") {
            showRoomStatus(
                `⏸️ ${ROOM_I18N.roomPaused || "La sala está pausada por el docente."}`
            );
            updateTimerUI();
            return;
        }

        if (state.status === "finished" || state.finished) {
            clearInterval(syncInterval);
            endGame(ROOM_I18N.gameCompleted);
            return;
        }

        if (state.status !== "started") {
            showRoomStatus(state.status);
            return;
        }

        const serverIndex = parseInt(state.current_question_index, 10);

        if (
            Number.isNaN(serverIndex) ||
            serverIndex < 0 ||
            serverIndex >= questions.length
        ) {
            return;
        }

        if (serverIndex !== currentQuestionNumber) {
            currentQuestionNumber = serverIndex;
            answeredCurrentQuestion = false;
            renderAdaptiveQuestion();
        }

        updateHUD();
        updateTimerUI();

        if (timeLeft <= 1 && !answeredCurrentQuestion) {
            answeredCurrentQuestion = true;
            disableOptions();

            updateDifficulty(false, timeLimit);

            document.getElementById("feedback").innerHTML = `
                ⏱️ ${ROOM_I18N.timeOut}<br>
                ${ROOM_I18N.newDifficulty || "Nueva dificultad"}: ${currentDifficulty.toFixed(1)} / 5
            `;

            updateHUD();
            saveProgress();
            showInterQuestionLeaderboard();
        }

    } catch (error) {
        console.error("Sync error:", error);
    }
}

function showRoomStatus(message) {
    document.getElementById("question-text").textContent = message;
    document.getElementById("options-container").innerHTML = "";
    document.getElementById("feedback").textContent = "";
}

function selectQuestionByDifficulty() {
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

    document.getElementById("question-text").textContent =
        currentQuestion.question;

    const container = document.getElementById("options-container");
    container.innerHTML = "";

    document.getElementById("feedback").textContent = "";
    hideLiveRanking();

    currentQuestion.options.forEach((opt, i) => {
        const btn = document.createElement("button");

        btn.innerHTML = `
            <span class="option-radio"></span>
            <span>${opt}</span>
        `;

        btn.classList.add("option-btn");
        btn.onclick = () => checkAnswer(i);

        container.appendChild(btn);
    });

    updateHUD();
}

function disableOptions() {
    document.querySelectorAll(".option-btn").forEach(btn => {
        btn.disabled = true;
    });
}

function updateTimerUI() {
    const timerEl = document.getElementById("timer");

    if (timerEl) {
        timerEl.textContent = `${timeLeft}s`;
    }
}

function getResponseTime() {
    return Math.max(0, timeLimit - timeLeft);
}

function calculateAdaptivePoints(isCorrect, responseTime) {
    if (!isCorrect) return 0;

    if (responseTime < 3) return 20;
    if (responseTime <= 6) return 15;

    return 10;
}

function updateDifficulty(isCorrect, responseTime) {
    if (isCorrect) {
        if (responseTime < 3) {
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

function checkAnswer(index) {
    if (answeredCurrentQuestion || !currentQuestion) return;

    answeredCurrentQuestion = true;
    disableOptions();

    const responseTime = getResponseTime();
    const isCorrect = index === currentQuestion.correct;
    const earnedPoints = calculateAdaptivePoints(isCorrect, responseTime);

    if (isCorrect) {
        score += earnedPoints;
        correctAnswers++;

        updateDifficulty(true, responseTime);

        document.getElementById("feedback").innerHTML = `
            ✅ ${ROOM_I18N.correct}. +${earnedPoints}<br>
            ${currentQuestion.explanation}<br>
            ${ROOM_I18N.newDifficulty || "Nueva dificultad"}: ${currentDifficulty.toFixed(1)} / 5
        `;
    } else {
        updateDifficulty(false, responseTime);

        document.getElementById("feedback").innerHTML = `
            ❌ ${ROOM_I18N.incorrect}.<br>
            ${ROOM_I18N.correctAnswer || "Respuesta correcta"}: ${currentQuestion.correct_option}<br>
            ${currentQuestion.explanation}<br>
            ${ROOM_I18N.newDifficulty || "Nueva dificultad"}: ${currentDifficulty.toFixed(1)} / 5
        `;
    }

    updateHUD();
    saveProgress();
    showInterQuestionLeaderboard();
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
                `<p>${ROOM_I18N.noResults || "No hay resultados todavía"}</p>`;
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

        setTimeout(() => {
            hideLiveRanking();
        }, 2500);

    } catch (error) {
        console.error("Live ranking error:", error);
        transitionInProgress = false;
    }
}

function hideLiveRanking() {
    const box = document.getElementById("live-ranking-box");

    if (box) box.style.display = "none";
}

async function endGame(message) {
    if (alreadySaved) return;

    alreadySaved = true;

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