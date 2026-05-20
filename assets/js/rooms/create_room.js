const form = document.getElementById("create-room-form");
const modeSelect = document.getElementById("question_mode");
const selectedSection = document.getElementById("selected-questions-section");
const loadQuestionsBtn = document.getElementById("load-questions-btn");
const questionsList = document.getElementById("questions-selection-list");
const message = document.getElementById("create-room-message");

modeSelect.addEventListener("change", () => {
    selectedSection.style.display = modeSelect.value === "selected" ? "block" : "none";
});

loadQuestionsBtn.addEventListener("click", async () => {
    const difficulty = document.getElementById("room_difficulty").value;
    const language = document.getElementById("room_language").value;

    questionsList.innerHTML = CREATE_ROOM_I18N.loading;

    try {
        const res = await fetch(`/colesterol_game/backend/rooms/get_questions_for_room_setup.php?difficulty=${difficulty}&lang=${language}`);
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            questionsList.innerHTML = CREATE_ROOM_I18N.error;
            return;
        }

        questionsList.innerHTML = "";

        data.forEach(q => {
            const item = document.createElement("label");
            item.classList.add("question-select-item");

            item.innerHTML = `
                <input type="checkbox" class="room-question-checkbox" value="${q.id}">
                <strong>#${q.id}</strong> ${q.question}
            `;

            questionsList.appendChild(item);
        });
    } catch (error) {
        console.error(error);
        questionsList.innerHTML = CREATE_ROOM_I18N.error;
    }
});

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = document.getElementById("room_name").value.trim();
    const difficulty = document.getElementById("room_difficulty").value;
    const language = document.getElementById("room_language").value;
    const question_count = parseInt(document.getElementById("question_count").value, 10);
    const time_limit = parseInt(document.getElementById("time_limit").value, 10);
    const question_mode = document.getElementById("question_mode").value;

    let selected_questions = [];

    if (question_mode === "selected") {
        selected_questions = Array.from(document.querySelectorAll(".room-question-checkbox:checked"))
            .map(cb => parseInt(cb.value, 10));

        if (selected_questions.length === 0) {
            message.textContent = CREATE_ROOM_I18N.selectAtLeastOne;
            return;
        }
    }

    try {
        const res = await fetch("/colesterol_game/backend/rooms/create_room.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                name,
                difficulty,
                language,
                question_count,
                time_limit,
                question_mode,
                selected_questions
            })
        });

        const result = await res.json();

        if (result.success) {
            window.location.href = `/colesterol_game/pages/rooms/lobby_admin.php?code=${encodeURIComponent(result.room_code)}`;
        } else {
            message.textContent = result.message || CREATE_ROOM_I18N.error;
        }
    } catch (error) {
        console.error(error);
        message.textContent = CREATE_ROOM_I18N.error;
    }
});