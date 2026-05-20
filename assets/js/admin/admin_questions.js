const tableBody = document.querySelector("#questionsTable tbody");
const form = document.getElementById("manual-question-form");
const message = document.getElementById("manual-message");

const editIdInput = document.getElementById("edit-id");
const saveBtn = document.getElementById("save-question-btn");
const cancelBtn = document.getElementById("cancel-edit-btn");

// Cargar preguntas
async function loadQuestions() {
    tableBody.innerHTML = `<tr><td colspan="7">${ADMIN_I18N.loading}</td></tr>`;

    try {
        const res = await fetch("/colesterol_game/backend/questions/get_all_questions.php");
        const data = await res.json();

        if (!Array.isArray(data)) {
            tableBody.innerHTML = `<tr><td colspan="7">${data.message || ADMIN_I18N.error}</td></tr>`;
            console.error(data);
            return;
        }

        tableBody.innerHTML = "";

        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7">No hay preguntas registradas</td></tr>`;
            return;
        }

        data.forEach(q => {
            const row = document.createElement("tr");

            row.innerHTML = `
                <td>${q.id}</td>
                <td>${q.question}</td>
                <td>${q.correct_option}</td>
                <td>${q.difficulty}</td>
                <td>${q.language}</td>
                <td>${q.category}</td>
                <td>
                    <button class="table-btn edit-btn" onclick="editQuestion(${q.id})">
                        ${ADMIN_I18N.edit}
                    </button>
                    <button class="table-btn delete-btn" onclick="deleteQuestion(${q.id})">
                        ${ADMIN_I18N.delete}
                    </button>
                </td>
            `;

            tableBody.appendChild(row);
        });
    } catch (error) {
        console.error(error);
        tableBody.innerHTML = `<tr><td colspan="7">${ADMIN_I18N.error}</td></tr>`;
    }
}

// Crear o actualizar
form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        id: editIdInput.value,
        question: document.getElementById("question").value,
        option_a: document.getElementById("option_a").value,
        option_b: document.getElementById("option_b").value,
        option_c: document.getElementById("option_c").value,
        option_d: document.getElementById("option_d").value,
        correct_option: document.getElementById("correct_option").value,
        explanation: document.getElementById("explanation").value,
        category: document.getElementById("category").value,
        difficulty: document.getElementById("difficulty").value,
        language: document.getElementById("language").value
    };

    const url = payload.id
        ? "/colesterol_game/backend/questions/update_question.php"
        : "/colesterol_game/backend/questions/create_question.php";

    message.textContent = ADMIN_I18N.loading;

    const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    });

    const result = await res.json();

    if (result.success) {
        message.textContent = payload.id
            ? ADMIN_I18N.updated
            : ADMIN_I18N.saved;

        form.reset();
        editIdInput.value = "";
        saveBtn.textContent = ADMIN_I18N.saveQuestion;
        cancelBtn.style.display = "none";

        loadQuestions();
    } else {
        message.textContent = ADMIN_I18N.error;
    }
});

// Editar
async function editQuestion(id) {
    const res = await fetch("/colesterol_game/backend/questions/get_all_questions.php");
    const data = await res.json();

    const q = data.find(x => x.id == id);
    if (!q) return;

    document.getElementById("question").value = q.question;
    document.getElementById("option_a").value = q.option_a;
    document.getElementById("option_b").value = q.option_b;
    document.getElementById("option_c").value = q.option_c;
    document.getElementById("option_d").value = q.option_d;
    document.getElementById("correct_option").value = q.correct_option;
    document.getElementById("explanation").value = q.explanation;
    document.getElementById("category").value = q.category;
    document.getElementById("difficulty").value = q.difficulty;
    document.getElementById("language").value = q.language;

    editIdInput.value = q.id;

    saveBtn.textContent = ADMIN_I18N.updateQuestion;
    cancelBtn.style.display = "block";
}

// Cancelar edición
cancelBtn.addEventListener("click", () => {
    form.reset();
    editIdInput.value = "";
    saveBtn.textContent = ADMIN_I18N.saveQuestion;
    cancelBtn.style.display = "none";
});

// Eliminar
async function deleteQuestion(id) {
    if (!confirm(ADMIN_I18N.confirmDelete)) return;

    await fetch("/colesterol_game/backend/questions/delete_question.php?id=" + id);

    loadQuestions();
}

// GENERADOR AUTOMÁTICO (GEMINI)
// GENERADOR AUTOMÁTICO CON GEMINI
document.getElementById("generator-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const generatorMessage = document.getElementById("generator-message");

    const topic = document.getElementById("generator_topic").value.trim();
    const difficulty = document.getElementById("generator_difficulty").value;
    const language = document.getElementById("generator_language").value;

    generatorMessage.textContent = ADMIN_I18N.loading;

    try {
        const res = await fetch("/colesterol_game/backend/questions/generate_question.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ topic, difficulty, language })
        });

        const result = await res.json();

        console.log("Gemini response:", result);

        if (!result.success) {
            generatorMessage.textContent = result.message || ADMIN_I18N.error;
            return;
        }

        document.getElementById("question").value = result.question || "";
        document.getElementById("option_a").value = result.option_a || "";
        document.getElementById("option_b").value = result.option_b || "";
        document.getElementById("option_c").value = result.option_c || "";
        document.getElementById("option_d").value = result.option_d || "";
        document.getElementById("correct_option").value = result.correct_option || "A";
        document.getElementById("explanation").value = result.explanation || "";
        document.getElementById("category").value = result.category || topic;
        document.getElementById("difficulty").value = result.difficulty || difficulty;
        document.getElementById("language").value = result.language || language;

        editIdInput.value = "";
        saveBtn.textContent = ADMIN_I18N.saveQuestion;
        cancelBtn.style.display = "none";

        generatorMessage.textContent = ADMIN_I18N.generatedReady || "Pregunta generada. Revísala antes de guardar.";

        document.getElementById("manual-question-form").scrollIntoView({
            behavior: "smooth",
            block: "start"
        });

    } catch (error) {
        console.error("Generator error:", error);
        generatorMessage.textContent = ADMIN_I18N.error;
    }
});

// IMPORT CSV
document.getElementById("csv-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById("csv_file");
    const formData = new FormData();

    formData.append("file", fileInput.files[0]);

    const res = await fetch("/colesterol_game/backend/questions/import_questions_csv.php", {
        method: "POST",
        body: formData
    });

    const result = await res.json();

    alert(result.message);
    loadQuestions();
});

// GENERADOR AUTOMÁTICO CON GEMINI EN MASA
document.getElementById("mass-generator-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const message = document.getElementById("mass-generator-message");

    const topic = document.getElementById("mass_topic").value.trim();
    const quantity = document.getElementById("mass_quantity").value;
    const difficulty = document.getElementById("mass_difficulty").value;
    const language = document.getElementById("mass_language").value;

    message.textContent = ADMIN_I18N.loading;

    try {
        const res = await fetch("/colesterol_game/backend/questions/generate_questions_bulk.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                topic,
                quantity,
                difficulty,
                language
            })
        });

        const result = await res.json();

        if (result.success) {
            message.textContent = `${ADMIN_I18N.massGeneratedSuccess}: ${result.inserted}`;
            loadQuestions();
        } else {
            message.textContent = result.message || ADMIN_I18N.error;
            console.error(result);
        }
    } catch (error) {
        console.error(error);
        message.textContent = ADMIN_I18N.error;
    }
});

// INIT
loadQuestions();