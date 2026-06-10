const form = document.getElementById("create-room-form");

const modeSelect = document.getElementById("question_mode");
const configuredSection = document.getElementById("configured-questions-section");
const selectedSection = document.getElementById("selected-questions-section");

const languageSelect = document.getElementById("room_language");
const questionCountInput = document.getElementById("question_count");
const blockCategorySelect = document.getElementById("block_category");
const customCategoryGroup = document.getElementById("custom-category-group");
const customCategoryInput = document.getElementById("custom_category");
const blockDifficultySelect = document.getElementById("block_difficulty");
const blockQuantityInput = document.getElementById("block_quantity");
const addBlockBtn = document.getElementById("add-question-block-btn");
const blocksSummary = document.getElementById("question-blocks-summary");
const aiProgress = document.getElementById("ai-generation-progress");
const aiProgressStatus = document.getElementById("ai-generation-status");
const aiProgressPercent = document.getElementById("ai-generation-percent");
const aiProgressFill = document.getElementById("ai-generation-fill");

const loadQuestionsBtn = document.getElementById("load-questions-btn");
const questionsList = document.getElementById("questions-selection-list");

const message = document.getElementById("create-room-message");

let questionBlocks = [];
let progressInterval = null;

function setRoomMessage(text = "", type = "") {
    message.textContent = text;
    message.classList.remove("is-info", "is-success", "is-error", "is-warning");

    if (type) {
        message.classList.add(`is-${type}`);
    }
}

function getCategoriesForLanguage(language) {
    return ROOM_CATEGORIES[language] || ROOM_CATEGORIES.es || [];
}

function populateCategorySelect() {
    const categories = getCategoriesForLanguage(languageSelect.value);

    blockCategorySelect.innerHTML = [
        ...categories.map(category => `<option value="${category}">${category}</option>`),
        `<option value="__custom__">${CREATE_ROOM_I18N.createNewCategory}</option>`
    ].join("");

    syncCustomCategoryField();
}

function normalizeCategoryInput(category) {
    return category.trim().replace(/\s+/g, " ").slice(0, 80);
}

function syncCustomCategoryField() {
    const isCustom = blockCategorySelect.value === "__custom__";
    customCategoryGroup.style.display = isCustom ? "flex" : "none";

    if (!isCustom) {
        customCategoryInput.value = "";
    }
}

function getSelectedCategory() {
    if (blockCategorySelect.value !== "__custom__") {
        return blockCategorySelect.value;
    }

    return normalizeCategoryInput(customCategoryInput.value);
}

function rememberCustomCategory(category) {
    const categories = getCategoriesForLanguage(languageSelect.value);

    if (!categories.some(item => item.toLowerCase() === category.toLowerCase())) {
        categories.push(category);
    }

    populateCategorySelect();
    blockCategorySelect.value = category;
    syncCustomCategoryField();
}

function syncModeSections() {
    const mode = modeSelect.value;

    configuredSection.style.display = mode === "configured" ? "block" : "none";
    selectedSection.style.display = mode === "selected" ? "block" : "none";
    questionCountInput.readOnly = mode === "configured";

    if (mode === "configured") {
        updateQuestionCountFromBlocks();
    }
}

function updateQuestionCountFromBlocks() {
    const total = questionBlocks.reduce(
        (sum, block) => sum + Number(block.quantity || 0),
        0
    );

    questionCountInput.value = total > 0 ? total : 0;
}

function mergeOrAddBlock(nextBlock) {
    const existing = questionBlocks.find(block =>
        block.category === nextBlock.category &&
        block.difficulty === nextBlock.difficulty
    );

    if (existing) {
        existing.quantity += nextBlock.quantity;
        existing.available = nextBlock.available;
    } else {
        questionBlocks.push(nextBlock);
    }
}

async function getAvailability(category, difficulty) {
    const params = new URLSearchParams({
        lang: languageSelect.value,
        category,
        difficulty
    });

    const res = await fetch(
        `/colesterol_game/backend/rooms/get_question_availability.php?${params.toString()}`
    );

    const data = await res.json();

    if (!data.success) {
        throw new Error(data.message || CREATE_ROOM_I18N.error);
    }

    return Number(data.available || 0);
}

function renderQuestionBlocks() {
    updateQuestionCountFromBlocks();

    if (questionBlocks.length === 0) {
        blocksSummary.innerHTML =
            `<p>${CREATE_ROOM_I18N.noQuestionBlocks}</p>`;
        return;
    }

    const total = questionBlocks.reduce(
        (sum, block) => sum + Number(block.quantity || 0),
        0
    );

    const rows = questionBlocks.map((block, index) => {
        const isShort = block.available < block.quantity;
        const missing = Math.max(0, block.quantity - block.available);
        const statusClass = isShort ? "config-status bad" : "config-status good";
        const statusText = `${CREATE_ROOM_I18N.available}: ${block.available}`;
        const generateButton = isShort
            ? `
                <button type="button" class="table-btn edit-btn" data-generate-missing="${index}">
                    ${CREATE_ROOM_I18N.generateMissingQuestions} (${missing})
                </button>
            `
            : "";

        return `
            <tr>
                <td>${block.category}</td>
                <td>${block.difficulty} / 5</td>
                <td>${block.quantity}</td>
                <td><span class="${statusClass}">${statusText}</span></td>
                <td>
                    ${generateButton}
                    <button type="button" class="table-btn delete-btn" data-remove-block="${index}">
                        ${CREATE_ROOM_I18N.remove}
                    </button>
                </td>
            </tr>
        `;
    }).join("");

    blocksSummary.innerHTML = `
        <p><strong>${CREATE_ROOM_I18N.total}:</strong> ${total}</p>
        <table class="admin-table room-config-table">
            <thead>
                <tr>
                    <th>${CREATE_ROOM_I18N.category}</th>
                    <th>${CREATE_ROOM_I18N.difficulty}</th>
                    <th>${CREATE_ROOM_I18N.requested}</th>
                    <th>${CREATE_ROOM_I18N.available}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    `;

    blocksSummary.querySelectorAll("[data-remove-block]").forEach(button => {
        button.addEventListener("click", () => {
            const index = Number(button.dataset.removeBlock);
            questionBlocks.splice(index, 1);
            renderQuestionBlocks();
        });
    });

    blocksSummary.querySelectorAll("[data-generate-missing]").forEach(button => {
        button.addEventListener("click", () => {
            const index = Number(button.dataset.generateMissing);
            goToQuestionGenerator(index);
        });
    });
}

async function refreshBlockAvailability() {
    await Promise.all(questionBlocks.map(async block => {
        block.available = await getAvailability(block.category, block.difficulty);
    }));

    renderQuestionBlocks();
}

function setProgress(percent, text) {
    const safePercent = Math.max(0, Math.min(100, percent));

    aiProgress.hidden = false;
    aiProgressStatus.textContent = text;
    aiProgressPercent.textContent = `${Math.round(safePercent)}%`;
    aiProgressFill.style.width = `${safePercent}%`;
    aiProgressFill.classList.toggle("is-active", safePercent > 0);
}

function startAiProgress() {
    const messages = CREATE_ROOM_I18N.aiProgressMessages || [];
    let step = 0;
    let percent = 8;

    clearInterval(progressInterval);
    setProgress(percent, messages[0] || CREATE_ROOM_I18N.generatingMissingQuestions);

    progressInterval = setInterval(() => {
        step = (step + 1) % Math.max(messages.length, 1);
        percent = Math.min(92, percent + 10 + Math.random() * 8);

        setProgress(
            percent,
            messages[step] || CREATE_ROOM_I18N.generatingMissingQuestions
        );
    }, 1300);
}

function stopAiProgress(text) {
    clearInterval(progressInterval);
    progressInterval = null;
    setProgress(100, text);

    setTimeout(() => {
        aiProgress.hidden = true;
        aiProgressFill.style.width = "0%";
        aiProgressPercent.textContent = "0%";
    }, 1200);
}

function setConfigControlsDisabled(disabled) {
    addBlockBtn.disabled = disabled;
    modeSelect.disabled = disabled;
    languageSelect.disabled = disabled;
    blockCategorySelect.disabled = disabled;
    blockDifficultySelect.disabled = disabled;
    blockQuantityInput.disabled = disabled;
    customCategoryInput.disabled = disabled;

    blocksSummary
        .querySelectorAll("button")
        .forEach(button => {
            button.disabled = disabled;
        });
}

function goToQuestionGenerator(index) {
    const block = questionBlocks[index];

    if (!block) {
        return;
    }

    const missing = Math.max(0, block.quantity - block.available);

    if (missing <= 0) {
        return;
    }

    const params = new URLSearchParams({
        source: "room",
        language: languageSelect.value,
        category: block.category,
        difficulty: block.difficulty,
        quantity: missing,
        topic: block.category
    });

    window.location.href =
        `/colesterol_game/pages/admin_questions.php?${params.toString()}#mass-generator`;
}

modeSelect.addEventListener("change", syncModeSections);
blockCategorySelect.addEventListener("change", syncCustomCategoryField);

languageSelect.addEventListener("change", async () => {
    populateCategorySelect();
    questionsList.innerHTML = "";

    if (questionBlocks.length > 0) {
        questionBlocks = [];
        renderQuestionBlocks();
    }
});

addBlockBtn.addEventListener("click", async () => {
    const category = getSelectedCategory();
    const difficulty = Number(blockDifficultySelect.value);
    const quantity = Number(blockQuantityInput.value);

    if (!category || quantity < 1) {
        setRoomMessage(
            !category
                ? CREATE_ROOM_I18N.newCategoryRequired
                : CREATE_ROOM_I18N.noQuestionBlocks,
            "warning"
        );
        return;
    }

    setRoomMessage(CREATE_ROOM_I18N.loading, "info");

    try {
        const available = await getAvailability(category, difficulty);

        mergeOrAddBlock({
            category,
            difficulty,
            quantity,
            available
        });

        if (blockCategorySelect.value === "__custom__") {
            rememberCustomCategory(category);
        }

        setRoomMessage("");
        renderQuestionBlocks();
    } catch (error) {
        console.error(error);
        setRoomMessage(error.message || CREATE_ROOM_I18N.error, "error");
    }
});

loadQuestionsBtn.addEventListener("click", async () => {
    const language = languageSelect.value;

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
                        - Difficulty ${difficultyLevel.toFixed(1)}/5
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
    const language = languageSelect.value;

    const question_count = parseInt(questionCountInput.value, 10);

    const time_limit = parseInt(
        document.getElementById("time_limit").value,
        10
    );

    const question_mode = modeSelect.value;

    let selected_questions = [];

    if (question_mode === "configured") {
        if (questionBlocks.length === 0) {
            setRoomMessage(CREATE_ROOM_I18N.noQuestionBlocks, "warning");
            return;
        }
    }

    if (question_mode === "selected") {
        selected_questions = Array
            .from(document.querySelectorAll(".room-question-checkbox:checked"))
            .map(cb => parseInt(cb.value, 10));

        if (selected_questions.length === 0) {
            setRoomMessage(CREATE_ROOM_I18N.selectAtLeastOne, "warning");
            return;
        }
    }

    setRoomMessage(CREATE_ROOM_I18N.loading, "info");

    try {
        if (question_mode === "configured") {
            await refreshBlockAvailability();
        }

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
                selected_questions,
                question_blocks: questionBlocks.map(block => ({
                    category: block.category,
                    difficulty: block.difficulty,
                    quantity: block.quantity
                }))
            })
        });

        const text = await res.text();

        let result;

        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error("JSON parse error:", jsonError, text);
            setRoomMessage("El servidor devolvió una respuesta inválida. Revisa la consola.", "error");
            return;
        }

        if (result.success) {
            window.location.href =
                `/colesterol_game/pages/rooms/lobby_admin.php?code=${encodeURIComponent(result.room_code)}`;
        } else {
            console.error("Create room backend error:", result);
            setRoomMessage(
                result.error || result.message || CREATE_ROOM_I18N.error,
                "error"
            );
        }

    } catch (error) {
        console.error("Create room request error:", error);
        setRoomMessage("Error del servidor. Revisa la consola.", "error");
    }
});

populateCategorySelect();
syncModeSections();
renderQuestionBlocks();
