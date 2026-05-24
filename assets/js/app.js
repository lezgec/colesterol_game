let questions = [];
let usedQuestionIds = [];

let currentQuestion = null;
let current = 0;
let score = 0;
let lives = 3;
let correctAnswers = 0;

let currentDifficulty = 1.0;

const gameScreen = document.getElementById("game-screen");

const questionText = document.getElementById("question-text");
const optionsContainer = document.getElementById("options-container");

const scoreText = document.getElementById("score");
const livesText = document.getElementById("lives");
const progressText = document.getElementById("progress");
const feedback = document.getElementById("feedback");

const selectedDifficultyText =
    document.getElementById("selected-difficulty");

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
}

function selectQuestionByDifficulty() {

    const availableQuestions =
        questions.filter(q => !usedQuestionIds.includes(q.id));

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

    if (lives <= 0) {
        endGame("💀 " + I18N.gameOver);
        return;
    }

    if (current >= questions.length) {
        endGame("🎉 " + I18N.gameCompleted);
        return;
    }

    currentQuestion = selectQuestionByDifficulty();

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

        const btn = document.createElement("button");

        btn.innerHTML = `
            <span class="option-radio"></span>
            <span>${opt}</span>
        `;

        btn.classList.add("option-btn");

        btn.onclick = () => checkAnswer(i);

        optionsContainer.appendChild(btn);
    });

    updateHUD();
}

function calculatePoints(isCorrect) {

    if (!isCorrect) {
        return 0;
    }

    const basePoints = 10;

    const difficultyBonus =
        Math.round(currentDifficulty * 2);

    return basePoints + difficultyBonus;
}

function updateDifficulty(isCorrect) {

    if (isCorrect) {

        currentDifficulty += 0.25;

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
}

function checkAnswer(index) {

    if (!currentQuestion) {
        return;
    }

    disableOptions();

    const isCorrect =
        index === currentQuestion.correct;

    if (isCorrect) {

        const earnedPoints =
            calculatePoints(true);

        score += earnedPoints;

        correctAnswers++;

        updateDifficulty(true);

        feedback.innerHTML = `
            ✅ ${I18N.correct}. +${earnedPoints}<br>
            ${currentQuestion.explanation}<br>
            ${I18N.newDifficulty}: ${currentDifficulty.toFixed(1)} / 5
        `;

    } else {

        lives--;

        updateDifficulty(false);

        feedback.innerHTML = `
            ❌ ${I18N.incorrect}<br>
            ${I18N.correctAnswer}: ${currentQuestion.correct_option}<br>
            ${currentQuestion.explanation}<br>
            ${I18N.newDifficulty}: ${currentDifficulty.toFixed(1)} / 5
        `;
    }

    updateHUD();

    setTimeout(() => {

        current++;

        loadQuestion();

    }, 1800);
}

async function endGame(message) {

    questionText.textContent = message;

    optionsContainer.innerHTML = "";

    progressText.textContent =
        I18N.gameFinished;

    feedback.innerHTML = `
        ${I18N.finalScore}: ${score}<br>
        ${I18N.correctAnswers}: ${correctAnswers} ${I18N.of} ${questions.length}<br>
        ${I18N.remainingLives}: ${lives}<br>
        ${I18N.finalDifficulty}: ${currentDifficulty.toFixed(1)} / 5<br>
        <span id="save-status">${I18N.savingResult}</span>
    `;

    try {

        const response = await fetch(
            "/colesterol_game/backend/game/save_result.php",
            {
                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({
                    score: score,
                    correct_answers: correctAnswers,
                    total_questions: questions.length,
                    lives_remaining: lives,
                    final_difficulty: currentDifficulty
                })
            }
        );

        const result = await response.json();

        const saveStatus =
            document.getElementById("save-status");

        if (result.success) {

            saveStatus.textContent =
                "✅ " + I18N.resultSaved;

        } else {

            saveStatus.textContent =
                "❌ " + I18N.resultNotSaved;

            console.error(result);
        }

    } catch (error) {

        const saveStatus =
            document.getElementById("save-status");

        if (saveStatus) {

            saveStatus.textContent =
                "❌ " + I18N.resultNotSaved;
        }

        console.error(
            "Error guardando resultado:",
            error
        );
    }
}

async function fetchQuestions(
    difficultyLevel = 1.0
) {

    try {

        currentDifficulty =
            parseFloat(difficultyLevel || 1.0);

        questionText.textContent =
            I18N.loadingQuestions;

        feedback.textContent = "";

        optionsContainer.innerHTML = "";

        const response = await fetch(
            `/colesterol_game/backend/questions/get_questions.php?difficulty_level=${currentDifficulty}&lang=${CURRENT_LANG}&limit=15`
        );

        const data = await response.json();

        if (!Array.isArray(data)) {

            console.error(
                "Respuesta inesperada:",
                data
            );

            questionText.textContent =
                I18N.resultNotSaved;

            feedback.textContent =
                data.message || "Error";

            return;
        }

        if (data.length === 0) {

            questionText.textContent =
                I18N.noQuestions;

            feedback.textContent =
                I18N.noQuestions;

            return;
        }

        questions = data;

        usedQuestionIds = [];

        current = 0;
        score = 0;
        lives = 3;
        correctAnswers = 0;

        gameScreen.style.display = "block";

        loadQuestion();

    } catch (error) {

        console.error(error);

        questionText.textContent =
            I18N.resultNotSaved;

        feedback.textContent = "Error";
    }
}

fetchQuestions(1.0);