const form = document.getElementById("create-room-form");

const modeSelect = document.getElementById("question_mode");
const selectedSection = document.getElementById("selected-questions-section");

const loadQuestionsBtn = document.getElementById("load-questions-btn");
const questionsList = document.getElementById("questions-selection-list");

const message = document.getElementById("create-room-message");

modeSelect.addEventListener("change", () => {
    selectedSection.style.display =
        modeSelect.value === "selected" ? "block" : "none";
});

loadQuestionsBtn.addEventListener("click", async () => {
    const language = document.getElementById("room_language").value;

    questionsList.innerHTML = CREATE_ROOM_I18N.loading;

    try {
        const res = await fetch(
            `/colesterol_game/backend/rooms/get_questions_for_room_setup.php?lang=${language}`
        );

        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            questionsList.innerHTML = CREATE_ROOM_I18N.error;
            return;
        }

        questionsList.innerHTML = "";

        data.forEach(q => {
            const item = document.createElement("label");
            item.classList.add("question-select-item");

            const difficultyLevel = parseFloat(q.difficulty_level || 1.0);

            item.innerHTML = `
                <input 
                    type="checkbox" 
                    class="room-question-checkbox" 
                    value="${q.id}"
                >

                <div>
                    <strong>#${q.id}</strong>
                    ${q.question}
                    <br>
                    <small>
                        ${q.category}
                        • Difficulty ${difficultyLevel.toFixed(1)}/5
                    </small>
                </div>
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
    const language = document.getElementById("room_language").value;

    const question_count = parseInt(
        document.getElementById("question_count").value,
        10
    );

    const time_limit = parseInt(
        document.getElementById("time_limit").value,
        10
    );

    const question_mode = document.getElementById("question_mode").value;

    let selected_questions = [];

    if (question_mode === "selected") {
        selected_questions = Array
            .from(document.querySelectorAll(".room-question-checkbox:checked"))
            .map(cb => parseInt(cb.value, 10));

        if (selected_questions.length === 0) {
            message.textContent = CREATE_ROOM_I18N.selectAtLeastOne;
            return;
        }
    }

    message.textContent = CREATE_ROOM_I18N.loading;

    try {
        const res = await fetch("/colesterol_game/backend/rooms/create_room.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name,
                language,
                question_count,
                time_limit,
                question_mode,
                selected_questions
            })
        });

        const text = await res.text();

        console.log("CREATE ROOM RAW RESPONSE:", text);

        let result;

        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error("JSON parse error:", jsonError);
            message.textContent = "El servidor devolvió una respuesta inválida. Revisa la consola.";
            return;
        }

        if (result.success) {
            window.location.href =
                `/colesterol_game/pages/rooms/lobby_admin.php?code=${encodeURIComponent(result.room_code)}`;
        } else {
            console.error("Create room backend error:", result);
            message.textContent =
                result.error || result.message || CREATE_ROOM_I18N.error;
        }

    } catch (error) {
        console.error("Create room request error:", error);
        message.textContent = "Error del servidor. Revisa la consola.";
    }
});