let questions = [];
let current = -1;
let score = 0;
let correctAnswers = 0;
let alreadySaved = false;

let timeLimit = 20;
let timeLeft = 20;
let answeredCurrentQuestion = false;
let syncInterval = null;
let transitionInProgress = false;

async function fetchQuestions() {
    try {
        const res = await fetch(`/colesterol_game/backend/rooms/get_questions_by_room.php?code=${encodeURIComponent(ROOM_CODE)}`);
        const data = await res.json();

        if (!data.success) {
            document.getElementById("question-text").textContent = data.message || ROOM_I18N.loadingError;
            return;
        }

        if (!Array.isArray(data.questions) || data.questions.length === 0) {
            document.getElementById("question-text").textContent = ROOM_I18N.noQuestions;
            return;
        }

        questions = data.questions;
        timeLimit = parseInt(data.room.time_limit || 20, 10);

        startSync();
    } catch (e) {
        console.error(e);
        document.getElementById("question-text").textContent = ROOM_I18N.loadingError;
    }
}

function startSync() {
    syncRoomState();
    syncInterval = setInterval(syncRoomState, 1000);
}

async function syncRoomState() {
    if (transitionInProgress) return;

    try {
        const res = await fetch(`/colesterol_game/backend/rooms/get_room_game_state.php?code=${encodeURIComponent(ROOM_CODE)}`);
        const state = await res.json();

        if (!state.success) {
            document.getElementById("question-text").textContent = state.message || ROOM_I18N.loadingError;
            return;
        }

        if (state.finished) {
            clearInterval(syncInterval);
            endGame(ROOM_I18N.gameCompleted);
            return;
        }

        const serverIndex = parseInt(state.current_question_index, 10);
        timeLeft = parseInt(state.time_left, 10);

        if (serverIndex !== current) {
            current = serverIndex;
            answeredCurrentQuestion = false;
            renderQuestion();
        }

        updateHUD();
        updateTimerUI();

        if (timeLeft <= 1 && !answeredCurrentQuestion) {
            answeredCurrentQuestion = true;
            disableOptions();
            document.getElementById("feedback").textContent = ROOM_I18N.timeOut;
            saveProgress();
            showInterQuestionLeaderboard();
        }

    } catch (error) {
        console.error("Sync error:", error);
    }
}

function renderQuestion() {
    if (current < 0 || current >= questions.length) return;

    transitionInProgress = false;

    const q = questions[current];

    document.getElementById("question-text").textContent = q.question;

    const container = document.getElementById("options-container");
    container.innerHTML = "";

    document.getElementById("feedback").textContent = "";
    hideLiveRanking();

    q.options.forEach((opt, i) => {
        const btn = document.createElement("button");
        btn.innerHTML = `<span class="option-radio"></span> <span>${opt}</span>`;
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

function calculateSpeedPoints() {
    const maxPoints = 1000;
    const minPoints = 500;
    const ratio = timeLeft / timeLimit;
    return Math.round(minPoints + (maxPoints - minPoints) * ratio);
}

function checkAnswer(index) {
    if (answeredCurrentQuestion) return;

    answeredCurrentQuestion = true;
    disableOptions();

    const q = questions[current];

    if (index === q.correct) {
        const earnedPoints = calculateSpeedPoints();
        score += earnedPoints;
        correctAnswers++;

        document.getElementById("feedback").textContent =
            `✅ ${ROOM_I18N.correct}. +${earnedPoints}`;
    } else {
        document.getElementById("feedback").textContent =
            `❌ ${ROOM_I18N.incorrect}. ${q.explanation}`;
    }

    updateHUD();
    saveProgress();
    showInterQuestionLeaderboard();
}

function updateHUD() {
    document.getElementById("score").textContent = score;

    const safeCurrent = current >= 0 ? current + 1 : 1;

    document.getElementById("progress").textContent =
        `${ROOM_I18N.question} ${safeCurrent} ${ROOM_I18N.of} ${questions.length}`;
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
                player_name: PLAYER_NAME
            })
        });
    } catch (error) {
        console.error("Error saving progress:", error);
    }
}

async function showInterQuestionLeaderboard() {
    transitionInProgress = true;

    const box = document.getElementById("live-ranking-box");
    const list = document.getElementById("live-ranking-list");

    if (!box || !list) return;

    try {
        const res = await fetch(`/colesterol_game/backend/rooms/get_room_ranking.php?code=${encodeURIComponent(ROOM_CODE)}`);
        const data = await res.json();

        box.style.display = "block";
        list.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
            list.innerHTML = `<p>${ROOM_I18N.noResults || "No hay resultados todavía"}</p>`;
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
            transitionInProgress = false;
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
        ${ROOM_I18N.savingResult}
    `;

    await saveProgress();

    setTimeout(() => {
        window.location.href = `/colesterol_game/pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}&name=${encodeURIComponent(PLAYER_NAME)}`;
    }, 1500);
}

fetchQuestions();