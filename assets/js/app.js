let questions = [];
let current = 0;
let score = 0;
let lives = 3;
let correctAnswers = 0;
let selectedDifficulty = "";

const difficultyScreen = document.getElementById("difficulty-screen");
const gameScreen = document.getElementById("game-screen");
const difficultyButtons = document.querySelectorAll(".difficulty-btn");

const questionText = document.getElementById("question-text");
const optionsContainer = document.getElementById("options-container");
const scoreText = document.getElementById("score");
const livesText = document.getElementById("lives");
const progressText = document.getElementById("progress");
const feedback = document.getElementById("feedback");
const selectedDifficultyText = document.getElementById("selected-difficulty");

function formatDifficultyLabel(value) {
  if (value === "easy") return I18N.easy;
  if (value === "medium") return I18N.medium;
  if (value === "hard") return I18N.hard;
  return value;
}

function updateHUD() {
  scoreText.textContent = score;
  livesText.textContent = "❤️".repeat(lives) + "🖤".repeat(3 - lives);
  selectedDifficultyText.textContent = formatDifficultyLabel(selectedDifficulty);

  progressText.textContent = questions.length > 0
    ? `${I18N.question} ${current + 1} ${I18N.of} ${questions.length}`
    : I18N.loadingQuestions;
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

  const q = questions[current];
  questionText.textContent = q.question;
  optionsContainer.innerHTML = "";
  feedback.textContent = "";

  q.options.forEach((opt, i) => {
    const btn = document.createElement("button");
    btn.textContent = opt;
    btn.classList.add("option-btn");
    btn.onclick = () => checkAnswer(i);
    optionsContainer.appendChild(btn);
  });

  updateHUD();
}

function checkAnswer(index) {
  const q = questions[current];
  const buttons = document.querySelectorAll(".option-btn");

  buttons.forEach(btn => btn.disabled = true);

  if (index === q.correct) {
    score += 10;
    correctAnswers++;
    feedback.textContent = `✅ ${I18N.correct}. ${q.explanation}`;
  } else {
    lives--;
    feedback.textContent = `❌ ${I18N.incorrect}. ${q.explanation}`;
  }

  updateHUD();

  setTimeout(() => {
    current++;
    loadQuestion();
  }, 1400);
}

async function endGame(message) {
  questionText.textContent = message;
  optionsContainer.innerHTML = "";
  progressText.textContent = I18N.gameFinished;

  feedback.innerHTML = `
    ${I18N.finalScore}: ${score}<br>
    ${I18N.correctAnswers}: ${correctAnswers} ${I18N.of} ${questions.length}<br>
    ${I18N.remainingLives}: ${lives}<br>
    <span id="save-status">${I18N.savingResult}</span>
  `;

  try {
    const response = await fetch("/colesterol_game/backend/save_result.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        score: score,
        correct_answers: correctAnswers,
        total_questions: questions.length,
        lives_remaining: lives,
        difficulty: selectedDifficulty
      })
    });

    const result = await response.json();
    console.log("Respuesta save_result:", result);

    const saveStatus = document.getElementById("save-status");

    if (result.success) {
      saveStatus.textContent = "✅ " + I18N.resultSaved;
    } else {
      saveStatus.textContent = "❌ " + I18N.resultNotSaved;
      console.error(result);
    }
  } catch (error) {
    const saveStatus = document.getElementById("save-status");
    if (saveStatus) {
      saveStatus.textContent = "❌ " + I18N.resultNotSaved;
    }
    console.error("Error guardando resultado:", error);
  }
}

async function fetchQuestions(difficulty) {
  try {
    selectedDifficulty = difficulty;
    questionText.textContent = I18N.loadingQuestions;
    feedback.textContent = "";
    optionsContainer.innerHTML = "";

    const response = await fetch(
      `/colesterol_game/backend/get_questions.php?difficulty=${difficulty}&lang=${CURRENT_LANG}`
    );

    const data = await response.json();

    if (!Array.isArray(data)) {
      console.error("Respuesta inesperada:", data);
      questionText.textContent = I18N.resultNotSaved;
      feedback.textContent = data.message || "Error";
      return;
    }

    if (data.length === 0) {
      questionText.textContent = I18N.noQuestions;
      feedback.textContent = `${I18N.noQuestions}: ${formatDifficultyLabel(difficulty)}`;
      return;
    }

    questions = data;
    current = 0;
    score = 0;
    lives = 3;
    correctAnswers = 0;

    difficultyScreen.style.display = "none";
    gameScreen.style.display = "block";

    loadQuestion();
  } catch (error) {
    console.error(error);
    questionText.textContent = I18N.resultNotSaved;
    feedback.textContent = "Error";
  }
}

difficultyButtons.forEach(button => {
  button.addEventListener("click", () => {
    fetchQuestions(button.dataset.difficulty);
  });
});