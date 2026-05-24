const tableBody = document.querySelector("#questionsTable tbody");
const form = document.getElementById("manual-question-form");
const message = document.getElementById("manual-message");

const editIdInput = document.getElementById("edit-id");
const saveBtn = document.getElementById("save-question-btn");
const cancelBtn = document.getElementById("cancel-edit-btn");

const TABLE_COLS = 10;

function getStatusLabel(status) {
    if (status === "verified") return ADMIN_I18N.verified || "Verified";
    if (status === "pending") return ADMIN_I18N.pending || "Pending";
    if (status === "rejected") return ADMIN_I18N.rejected || "Rejected";
    return status || "-";
}

function getActiveLabel(value) {
    return parseInt(value, 10) === 1
        ? (ADMIN_I18N.active || "Active")
        : (ADMIN_I18N.inactive || "Inactive");
}

// Cargar preguntas
async function loadQuestions() {
    tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${ADMIN_I18N.loading}</td></tr>`;

    try {
        const res = await fetch("/colesterol_game/backend/questions/get_all_questions.php");
        const data = await res.json();

        if (!Array.isArray(data)) {
            tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${data.message || ADMIN_I18N.error}</td></tr>`;
            console.error(data);
            return;
        }

        tableBody.innerHTML = "";

        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">No hay preguntas registradas</td></tr>`;
            return;
        }

        data.forEach(q => {
            const row = document.createElement("tr");

            const difficultyLevel = parseFloat(q.difficulty_level || 1.0);

            row.innerHTML = `
                <td>${q.id}</td>
                <td>${q.question}</td>
                <td>${q.correct_option}</td>
                <td>${difficultyLevel.toFixed(1)} / 5</td>
                <td>${q.language}</td>
                <td>${q.category || "-"}</td>
                <td>${getStatusLabel(q.status)}</td>
                <td>${q.origin || "-"}</td>
                <td>${getActiveLabel(q.is_active)}</td>
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
        tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${ADMIN_I18N.error}</td></tr>`;
    }
}

// Crear o actualizar
form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        id: editIdInput.value,
        question: document.getElementById("question").value.trim(),
        option_a: document.getElementById("option_a").value.trim(),
        option_b: document.getElementById("option_b").value.trim(),
        option_c: document.getElementById("option_c").value.trim(),
        option_d: document.getElementById("option_d").value.trim(),
        correct_option: document.getElementById("correct_option").value,
        explanation: document.getElementById("explanation").value.trim(),
        category: document.getElementById("category").value.trim(),
        difficulty_level: parseFloat(document.getElementById("difficulty_level").value || "1"),
        language: document.getElementById("language").value,
        status: document.getElementById("status").value,
        origin: document.getElementById("origin").value,
        is_active: parseInt(document.getElementById("is_active").value, 10)
    };

    if (payload.difficulty_level < 1) payload.difficulty_level = 1;
    if (payload.difficulty_level > 5) payload.difficulty_level = 5;

    const url = payload.id
        ? "/colesterol_game/backend/questions/update_question.php"
        : "/colesterol_game/backend/questions/create_question.php";

    message.textContent = ADMIN_I18N.loading;

    try {
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

            document.getElementById("difficulty_level").value = "1.0";
            document.getElementById("status").value = "verified";
            document.getElementById("origin").value = "manual";
            document.getElementById("is_active").value = "1";

            editIdInput.value = "";
            saveBtn.textContent = ADMIN_I18N.saveQuestion;
            cancelBtn.style.display = "none";

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

// Editar
async function editQuestion(id) {
    try {
        const res = await fetch("/colesterol_game/backend/questions/get_all_questions.php");
        const data = await res.json();

        if (!Array.isArray(data)) return;

        const q = data.find(x => parseInt(x.id, 10) === parseInt(id, 10));
        if (!q) return;

        document.getElementById("question").value = q.question || "";
        document.getElementById("option_a").value = q.option_a || "";
        document.getElementById("option_b").value = q.option_b || "";
        document.getElementById("option_c").value = q.option_c || "";
        document.getElementById("option_d").value = q.option_d || "";
        document.getElementById("correct_option").value = q.correct_option || "A";
        document.getElementById("explanation").value = q.explanation || "";
        document.getElementById("category").value = q.category || "";
        document.getElementById("difficulty_level").value = q.difficulty_level || "1.0";
        document.getElementById("language").value = q.language || "es";
        document.getElementById("status").value = q.status || "verified";
        document.getElementById("origin").value = q.origin || "manual";
        document.getElementById("is_active").value = parseInt(q.is_active, 10) === 1 ? "1" : "0";

        editIdInput.value = q.id;

        saveBtn.textContent = ADMIN_I18N.updateQuestion;
        cancelBtn.style.display = "block";

        form.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    } catch (error) {
        console.error(error);
    }
}

// Cancelar edición
cancelBtn.addEventListener("click", () => {
    form.reset();

    document.getElementById("difficulty_level").value = "1.0";
    document.getElementById("status").value = "verified";
    document.getElementById("origin").value = "manual";
    document.getElementById("is_active").value = "1";

    editIdInput.value = "";
    saveBtn.textContent = ADMIN_I18N.saveQuestion;
    cancelBtn.style.display = "none";
});

// Eliminar
async function deleteQuestion(id) {
    if (!confirm(ADMIN_I18N.confirmDelete)) return;

    try {
        await fetch("/colesterol_game/backend/questions/delete_question.php?id=" + id);
        loadQuestions();
    } catch (error) {
        console.error(error);
    }
}

// GENERADOR AUTOMÁTICO CON GEMINI
document.getElementById("generator-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const generatorMessage = document.getElementById("generator-message");

    const topic = document.getElementById("generator_topic").value.trim();
    const difficulty_level = parseFloat(
        document.getElementById("generator_difficulty_level").value || "1"
    );
    const language = document.getElementById("generator_language").value;

    generatorMessage.textContent = ADMIN_I18N.loading;

    try {
        const res = await fetch("/colesterol_game/backend/questions/generate_question.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                topic,
                difficulty_level,
                language
            })
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
        document.getElementById("difficulty_level").value = result.difficulty_level || difficulty_level;
        document.getElementById("language").value = result.language || language;

        // IA genera pendiente por seguridad
        document.getElementById("status").value = "pending";
        document.getElementById("origin").value = "ai";
        document.getElementById("is_active").value = "0";

        editIdInput.value = "";
        saveBtn.textContent = ADMIN_I18N.saveQuestion;
        cancelBtn.style.display = "none";

        generatorMessage.textContent =
            ADMIN_I18N.generatedReady || "Pregunta generada. Revísala antes de guardar.";

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

    try {
        const res = await fetch("/colesterol_game/backend/questions/import_questions_csv.php", {
            method: "POST",
            body: formData
        });

        const result = await res.json();

        alert(result.message);
        loadQuestions();
    } catch (error) {
        console.error(error);
        alert(ADMIN_I18N.error);
    }
});

// GENERADOR AUTOMÁTICO CON GEMINI EN MASA
document.getElementById("mass-generator-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const message = document.getElementById("mass-generator-message");

    const topic = document.getElementById("mass_topic").value.trim();
    const quantity = parseInt(document.getElementById("mass_quantity").value, 10);
    const difficulty_level = parseFloat(
        document.getElementById("mass_difficulty_level").value || "1"
    );
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
                difficulty_level,
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