const tableBody = document.querySelector("#questionsTable tbody");
const form = document.getElementById("manual-question-form");
const message = document.getElementById("manual-message");

const editIdInput = document.getElementById("edit-id");
const saveBtn = document.getElementById("save-question-btn");
const cancelBtn = document.getElementById("cancel-edit-btn");
const questionEditModal = document.getElementById("question-edit-modal");
const questionEditModalForm = document.getElementById("question-edit-modal-form");
const questionModalMessage = document.getElementById("question-modal-message");
const openCreateQuestionModalBtn = document.getElementById("open-create-question-modal");
const csvForm = document.getElementById("csv-form");
const generatorForm = document.getElementById("generator-form");
const csvFileInput = document.getElementById("csv_file");
const csvMessage = document.getElementById("csv-message");
const questionSearchInput = document.getElementById("question-search");
const questionFilterCategory = document.getElementById("question-filter-category");
const questionFilterDifficulty = document.getElementById("question-filter-difficulty");
const questionFilterLanguage = document.getElementById("question-filter-language");
const questionFilterStatus = document.getElementById("question-filter-status");
const questionFilterOrigin = document.getElementById("question-filter-origin");
const questionFilterActive = document.getElementById("question-filter-active");
const questionClearFiltersBtn = document.getElementById("question-clear-filters");
const aiProgressPanel = document.getElementById("ai-progress-panel");
const aiProgressBar = document.getElementById("ai-progress-bar");
const reviewGeneratedQuestionsBtn = document.getElementById("review-generated-questions-btn");
const appConfirmModal = document.getElementById("app-confirm-modal");
const appConfirmMessage = document.getElementById("app-confirm-message");
const appConfirmAccept = document.getElementById("app-confirm-accept");
const appConfirmCancel = document.getElementById("app-confirm-cancel");
const appConfirmClose = document.getElementById("app-confirm-close");

const TABLE_COLS = 10;
const CUSTOM_CATEGORY_VALUE = "__custom__";
let questionsCache = [];

function formatApiMessage(data, fallback) {
    return [data?.message, data?.action]
        .filter(Boolean)
        .join(" ")
        || fallback;
}
let confirmResolver = null;
let generatedQuestionIdsFilter = [];
let aiProgressTimer = null;

function normalizeDifficultyLevel(value) {
    const parsed = Math.round(Number(value) || 1);
    return Math.min(5, Math.max(1, parsed));
}

function getAdminSections() {
    return Array.from(document.querySelectorAll("[data-collapsible-section]"));
}

function setAdminSection(section, isOpen, options = {}) {
    if (!section) return;

    const body = section.querySelector(".admin-section-body");
    const toggle = section.querySelector(".admin-section-heading");

    if (!body || !toggle) return;

    if (options.closeOthers && isOpen) {
        getAdminSections().forEach(otherSection => {
            if (otherSection !== section) {
                setAdminSection(otherSection, false);
            }
        });
    }

    section.classList.toggle("is-open", isOpen);
    section.classList.toggle("is-collapsed", !isOpen);
    body.hidden = !isOpen;
    toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");

    if (isOpen && options.scroll) {
        section.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }
}

function openAdminSectionById(sectionId, options = {}) {
    const section = document.getElementById(sectionId);
    setAdminSection(section, true, {
        closeOthers: true,
        scroll: false,
        ...options
    });
}

function setupAdminAccordions() {
    getAdminSections().forEach(section => {
        const toggle = section.querySelector(".admin-section-heading");

        setAdminSection(section, false);

        if (!toggle) return;

        toggle.addEventListener("click", () => {
            const shouldOpen = !section.classList.contains("is-open");
            setAdminSection(section, shouldOpen, { closeOthers: true });
        });
    });

    document.querySelectorAll(".admin-question-nav a[href^='#']").forEach(link => {
        link.addEventListener("click", event => {
            const sectionId = link.getAttribute("href").replace("#", "");
            const section = document.getElementById(sectionId);

            if (!section) return;

            event.preventDefault();
            openAdminSectionById(sectionId, { scroll: true });
            history.replaceState(null, "", `#${sectionId}`);
        });
    });

    if (window.location.hash) {
        const requestedSection = window.location.hash.replace("#", "");
        const sectionId = ["single-generator", "mass-generator"].includes(requestedSection)
            ? "ai-generator"
            : requestedSection;

        openAdminSectionById(sectionId, { scroll: true });
    }
}

function getCategoriesForLanguage(language) {
    return (
        typeof QUESTION_CATEGORIES !== "undefined" &&
        QUESTION_CATEGORIES[language]
    )
        ? QUESTION_CATEGORIES[language]
        : QUESTION_CATEGORIES.es;
}

function populateCategorySelect(select, language, selectedValue = "") {
    const categories = getCategoriesForLanguage(language);
    const customInput = getCustomCategoryInput(select);
    const normalizedSelectedValue = (selectedValue || "").trim();
    const matchingCategory = categories.find(
        category => category.toLowerCase() === normalizedSelectedValue.toLowerCase()
    );
    const isCustomValue = normalizedSelectedValue && !matchingCategory;

    select.innerHTML = "";

    categories.forEach(category => {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        select.appendChild(option);
    });

    const customOption = document.createElement("option");
    customOption.value = CUSTOM_CATEGORY_VALUE;
    customOption.textContent = ADMIN_I18N.otherCategory || "Otra categoría";
    select.appendChild(customOption);

    select.value = isCustomValue
        ? CUSTOM_CATEGORY_VALUE
        : (matchingCategory || categories[0]);

    if (customInput) {
        customInput.value = isCustomValue ? normalizedSelectedValue : "";
        updateCustomCategoryInput(select);
    }
}

function getCustomCategoryInput(select) {
    const inputId = select.dataset.customInput;
    return inputId ? document.getElementById(inputId) : null;
}

function updateCustomCategoryInput(select) {
    const customInput = getCustomCategoryInput(select);

    if (!customInput) return;

    const isCustom = select.value === CUSTOM_CATEGORY_VALUE;
    customInput.style.display = isCustom ? "block" : "none";
    customInput.required = isCustom;

    if (!isCustom) {
        customInput.value = "";
    }
}

function getCategoryValue(selectId) {
    const select = document.getElementById(selectId);
    const customInput = getCustomCategoryInput(select);

    if (select.value === CUSTOM_CATEGORY_VALUE) {
        return (customInput?.value || "").trim();
    }

    return select.value.trim();
}

function bindCategorySelect(selectId) {
    const select = document.getElementById(selectId);

    select.addEventListener("change", () => {
        updateCustomCategoryInput(select);
    });
}

function bindCategorySelectIfExists(selectId) {
    const select = document.getElementById(selectId);

    if (!select) return;

    select.addEventListener("change", () => {
        updateCustomCategoryInput(select);
    });
}

function syncCategoryDropdowns() {
    populateCategorySelect(
        document.getElementById("category"),
        document.getElementById("language").value,
        getCategoryValue("category")
    );

    populateCategorySelect(
        document.getElementById("generator_category"),
        document.getElementById("generator_language").value,
        getCategoryValue("generator_category")
    );

    const generatorMode = document.getElementById("generator_mode");

    if (generatorMode) {
        updateGeneratorMode();
    }
}

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

function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
}

function populateQuestionFilters() {
    if (!questionFilterCategory) return;

    const currentCategory = questionFilterCategory.value;
    const categories = Array.from(
        new Set(questionsCache.map(q => (q.category || "").trim()).filter(Boolean))
    ).sort((a, b) => a.localeCompare(b));

    questionFilterCategory.innerHTML = `<option value="">${ADMIN_I18N.allCategories || "Todas"}</option>`;

    categories.forEach(category => {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        questionFilterCategory.appendChild(option);
    });

    questionFilterCategory.value = categories.includes(currentCategory) ? currentCategory : "";
}

function getFilteredQuestions() {
    const search = (questionSearchInput?.value || "").trim().toLowerCase();
    const category = questionFilterCategory?.value || "";
    const difficulty = questionFilterDifficulty?.value || "";
    const language = questionFilterLanguage?.value || "";
    const status = questionFilterStatus?.value || "";
    const origin = questionFilterOrigin?.value || "";
    const active = questionFilterActive?.value || "";

    const generatedIds = generatedQuestionIdsFilter.map(id => parseInt(id, 10)).filter(Number.isFinite);

    return questionsCache.filter(q => {
        const difficultyLevel = String(normalizeDifficultyLevel(q.difficulty_level));
        const activeValue = String(parseInt(q.is_active, 10) === 1 ? 1 : 0);
        const questionId = parseInt(q.id, 10);
        const searchable = [
            q.id,
            q.question,
            q.correct_option,
            q.category,
            q.language,
            q.status,
            q.origin
        ].join(" ").toLowerCase();

        return (generatedIds.length === 0 || generatedIds.includes(questionId)) &&
            (!search || searchable.includes(search)) &&
            (!category || q.category === category) &&
            (!difficulty || difficultyLevel === difficulty) &&
            (!language || q.language === language) &&
            (!status || q.status === status) &&
            (!origin || q.origin === origin) &&
            (!active || activeValue === active);
    });
}

function renderQuestions(data) {
    tableBody.innerHTML = "";

    if (questionsCache.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${ADMIN_I18N.noQuestionsRegistered}</td></tr>`;
        return;
    }

    if (data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${ADMIN_I18N.noFilterResults || ADMIN_I18N.noQuestionsRegistered}</td></tr>`;
        return;
    }

    data.forEach(q => {
        const row = document.createElement("tr");
        const difficultyLevel = normalizeDifficultyLevel(q.difficulty_level);

        row.innerHTML = `
            <td class="col-id">${escapeHtml(q.id)}</td>
            <td class="col-question question-text-cell">${escapeHtml(q.question)}</td>
            <td class="col-correct">${escapeHtml(q.correct_option)}</td>
            <td class="col-difficulty">${difficultyLevel} / 5</td>
            <td class="col-language">${escapeHtml(q.language)}</td>
            <td class="col-category">${escapeHtml(q.category || "-")}</td>
            <td class="col-status">${escapeHtml(getStatusLabel(q.status))}</td>
            <td class="col-origin">${escapeHtml(q.origin || "-")}</td>
            <td class="col-active">${escapeHtml(getActiveLabel(q.is_active))}</td>
            <td class="col-actions">
                <div class="question-row-actions">
                    <button class="table-btn edit-btn" type="button" onclick="editQuestion(${parseInt(q.id, 10)})">
                        ${escapeHtml(ADMIN_I18N.edit)}
                    </button>
                    <button class="table-btn delete-btn" type="button" onclick="deleteQuestion(${parseInt(q.id, 10)})">
                        ${escapeHtml(ADMIN_I18N.delete)}
                    </button>
                </div>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

function applyQuestionFilters() {
    renderQuestions(getFilteredQuestions());
}

function resetGeneratedQuestionFilter() {
    generatedQuestionIdsFilter = [];
    updateReviewGeneratedButton(0);
}

function updateReviewGeneratedButton(count = generatedQuestionIdsFilter.length) {
    if (!reviewGeneratedQuestionsBtn) return;

    reviewGeneratedQuestionsBtn.hidden = count === 0;
    reviewGeneratedQuestionsBtn.textContent = count > 0
        ? `${ADMIN_I18N.reviewGeneratedQuestions || "Verificar preguntas generadas"} (${count})`
        : (ADMIN_I18N.reviewGeneratedQuestions || "Verificar preguntas generadas");
}

function setAiProgress(stepName, percent) {
    if (!aiProgressPanel || !aiProgressBar) return;

    aiProgressPanel.hidden = false;
    aiProgressBar.style.width = `${Math.max(0, Math.min(100, percent))}%`;

    aiProgressPanel.querySelectorAll("[data-ai-step]").forEach(step => {
        const isCurrent = step.dataset.aiStep === stepName;
        const stepOrder = ["prepare", "generate", "save", "ready"];
        const currentIndex = stepOrder.indexOf(stepName);
        const stepIndex = stepOrder.indexOf(step.dataset.aiStep);

        step.classList.toggle("is-current", isCurrent);
        step.classList.toggle("is-complete", stepIndex >= 0 && stepIndex < currentIndex);
    });
}

function startAiProgress() {
    const timeline = [
        ["prepare", 14, ADMIN_I18N.aiProgressPrepare],
        ["generate", 42, ADMIN_I18N.aiProgressGenerate],
        ["save", 72, ADMIN_I18N.aiProgressSave]
    ];
    let index = 0;

    clearInterval(aiProgressTimer);
    updateReviewGeneratedButton(0);
    setAiProgress(timeline[0][0], timeline[0][1]);

    aiProgressTimer = setInterval(() => {
        index = Math.min(index + 1, timeline.length - 1);
        setAiProgress(timeline[index][0], timeline[index][1]);
    }, 1600);
}

function finishAiProgress(success = true) {
    clearInterval(aiProgressTimer);
    aiProgressTimer = null;

    if (success) {
        setAiProgress("ready", 100);
    }
}

async function focusGeneratedQuestions(ids) {
    generatedQuestionIdsFilter = Array.isArray(ids)
        ? ids.map(id => parseInt(id, 10)).filter(Number.isFinite)
        : [];

    updateReviewGeneratedButton(generatedQuestionIdsFilter.length);

    if (questionSearchInput) questionSearchInput.value = "";
    if (questionFilterCategory) questionFilterCategory.value = "";
    if (questionFilterDifficulty) questionFilterDifficulty.value = "";
    if (questionFilterLanguage) questionFilterLanguage.value = "";
    if (questionFilterStatus) questionFilterStatus.value = "pending";
    if (questionFilterOrigin) questionFilterOrigin.value = "ai";
    if (questionFilterActive) questionFilterActive.value = "0";

    await loadQuestions();
    openAdminSectionById("question-bank-section", { scroll: true });
}

// Cargar preguntas
async function loadQuestions() {
    tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${ADMIN_I18N.loading}</td></tr>`;

    try {
        const res = await fetch(appUrl("backend/questions/get_all_questions.php"));
        const data = await res.json();

        if (!Array.isArray(data)) {
            tableBody.innerHTML = `<tr><td colspan="${TABLE_COLS}">${escapeHtml(formatApiMessage(data, ADMIN_I18N.error))}</td></tr>`;
            console.error(data);
            return;
        }

        questionsCache = data;
        populateQuestionFilters();
        applyQuestionFilters();
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
        category: getCategoryValue("category"),
        difficulty_level: normalizeDifficultyLevel(document.getElementById("difficulty_level").value),
        language: document.getElementById("language").value,
        status: document.getElementById("status").value,
        origin: document.getElementById("origin").value,
        is_active: parseInt(document.getElementById("is_active").value, 10)
    };

    if (payload.status !== "verified") {
        payload.is_active = 0;
        document.getElementById("is_active").value = "0";
    }

    const url = payload.id
        ? appUrl("backend/questions/update_question.php")
        : appUrl("backend/questions/create_question.php");

    message.textContent = ADMIN_I18N.loading;

    try {
        const res = await fetch(url, {
            method: "POST",
            headers: csrfHeaders({ "Content-Type": "application/json" }),
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (result.success) {
            message.textContent = payload.id
                ? ADMIN_I18N.updated
                : ADMIN_I18N.saved;

            form.reset();

            document.getElementById("difficulty_level").value = "1";
            document.getElementById("status").value = "verified";
            document.getElementById("origin").value = "manual";
            document.getElementById("is_active").value = "1";
            syncCategoryDropdowns();

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
        let data = questionsCache;

        if (!Array.isArray(data) || data.length === 0) {
            const res = await fetch(appUrl("backend/questions/get_all_questions.php"));
            data = await res.json();
            questionsCache = Array.isArray(data) ? data : [];
        }

        if (!Array.isArray(data)) return;

        const q = data.find(x => parseInt(x.id, 10) === parseInt(id, 10));
        if (!q) return;

        openQuestionEditModal(q);
    } catch (error) {
        console.error(error);
    }
}

function setModalCorrectOption(option) {
    document.querySelectorAll("[data-option-card]").forEach(card => {
        const input = card.querySelector("input[type='radio']");
        const isSelected = input.value === option;

        input.checked = isSelected;
        card.classList.toggle("is-correct", isSelected);
    });
}

function openQuestionEditModal(q) {
    if (!questionEditModal) return;

    const isNew = !q || !q.id;
    const modalTitle = document.getElementById("question-edit-title");
    const submitButton = document.querySelector("button[form='question-edit-modal-form']");
    const initialQuestion = q || {
        id: "",
        question: "",
        option_a: "",
        option_b: "",
        option_c: "",
        option_d: "",
        explanation: "",
        difficulty_level: 1,
        language: document.getElementById("language")?.value || "es",
        status: "verified",
        origin: "manual",
        is_active: 1,
        correct_option: "A",
        category: "",
        visibility: "private",
        global_request_status: "none",
        question_scope: ADMIN_IS_SUPER_ADMIN ? "global" : "private",
        requires_review: false
    };

    questionEditModalForm.dataset.requiresReview = initialQuestion.requires_review ? "1" : "0";

    if (modalTitle) {
        modalTitle.textContent = isNew
            ? (ADMIN_I18N.createQuestion || "Crear pregunta")
            : (ADMIN_I18N.editQuestion || "Editar pregunta");
    }

    if (submitButton) {
        submitButton.textContent = isNew
            ? (ADMIN_I18N.saveQuestion || "Guardar pregunta")
            : (ADMIN_I18N.saveChanges || "Guardar cambios");
    }

    document.getElementById("modal-question-id").value = initialQuestion.id;
    document.getElementById("modal-question").value = initialQuestion.question || "";
    document.getElementById("modal-option-a").value = initialQuestion.option_a || "";
    document.getElementById("modal-option-b").value = initialQuestion.option_b || "";
    document.getElementById("modal-option-c").value = initialQuestion.option_c || "";
    document.getElementById("modal-option-d").value = initialQuestion.option_d || "";
    document.getElementById("modal-explanation").value = initialQuestion.explanation || "";
    document.getElementById("modal-difficulty").value = normalizeDifficultyLevel(initialQuestion.difficulty_level);
    document.getElementById("modal-language").value = initialQuestion.language || "es";
    document.getElementById("modal-status").value = initialQuestion.status || "verified";
    document.getElementById("modal-origin").value = initialQuestion.origin || "manual";
    document.getElementById("modal-is-active").value = parseInt(initialQuestion.is_active, 10) === 1 ? "1" : "0";

    const scopeSelect = document.getElementById("modal-question-scope");
    if (scopeSelect) {
        if (initialQuestion.question_scope) {
            scopeSelect.value = initialQuestion.question_scope;
        } else if (ADMIN_IS_SUPER_ADMIN) {
            scopeSelect.value = initialQuestion.visibility === "private" ? "private" : "global";
        } else {
            scopeSelect.value = initialQuestion.visibility === "global" ? "global_request" : "private";
        }
    }

    populateCategorySelect(
        document.getElementById("modal-category"),
        document.getElementById("modal-language").value,
        initialQuestion.category || ""
    );
    setModalCorrectOption(initialQuestion.correct_option || "A");
    enforceVerifiedAvailability(false);

    questionModalMessage.textContent = "";
    questionEditModal.hidden = false;
    document.body.classList.add("modal-open");
}

function closeQuestionEditModal() {
    if (!questionEditModal) return;

    questionEditModal.hidden = true;
    document.body.classList.remove("modal-open");
}

function enforceVerifiedAvailability(showMessage = false) {
    const statusSelect = document.getElementById("modal-status");
    const activeSelect = document.getElementById("modal-is-active");

    if (!statusSelect || !activeSelect) return;

    const canBeAvailable = statusSelect.value === "verified";

    if (!canBeAvailable && activeSelect.value === "1") {
        activeSelect.value = "0";

        if (showMessage && questionModalMessage) {
            questionModalMessage.textContent = ADMIN_I18N.mustBeVerifiedToBeAvailable || "";
        }
    }

    activeSelect.querySelector("option[value='1']").disabled = !canBeAvailable;
}

async function submitQuestionEditModal(event) {
    event.preventDefault();
    enforceVerifiedAvailability(true);

    const payload = {
        id: document.getElementById("modal-question-id").value,
        question: document.getElementById("modal-question").value.trim(),
        option_a: document.getElementById("modal-option-a").value.trim(),
        option_b: document.getElementById("modal-option-b").value.trim(),
        option_c: document.getElementById("modal-option-c").value.trim(),
        option_d: document.getElementById("modal-option-d").value.trim(),
        correct_option: document.querySelector("input[name='modal-correct-option']:checked")?.value || "A",
        explanation: document.getElementById("modal-explanation").value.trim(),
        category: getCategoryValue("modal-category"),
        difficulty_level: normalizeDifficultyLevel(document.getElementById("modal-difficulty").value),
        language: document.getElementById("modal-language").value,
        status: document.getElementById("modal-status").value,
        origin: document.getElementById("modal-origin").value,
        is_active: parseInt(document.getElementById("modal-is-active").value, 10),
        question_scope: ADMIN_IS_SUPER_ADMIN
            ? "global"
            : (document.getElementById("modal-question-scope")?.value || "private"),
        requires_review: questionEditModalForm.dataset.requiresReview === "1"
    };

    if (payload.status !== "verified") {
        payload.is_active = 0;
    }

    questionModalMessage.textContent = ADMIN_I18N.loading;

    try {
        const url = payload.id
            ? appUrl("backend/questions/update_question.php")
            : appUrl("backend/questions/create_question.php");

        const res = await fetch(url, {
            method: "POST",
            headers: csrfHeaders({ "Content-Type": "application/json" }),
            body: JSON.stringify(payload)
        });
        const rawResponse = await res.text();
        let result;

        try {
            result = JSON.parse(rawResponse);
        } catch (parseError) {
            console.error(rawResponse);
            questionModalMessage.textContent = rawResponse.trim() || ADMIN_I18N.error;
            return;
        }

        if (!result.success) {
            questionModalMessage.textContent = result.message || ADMIN_I18N.error;
            return;
        }

        const successMessage = result.global_request_status === "pending"
            ? (ADMIN_I18N.questionSentToGlobalReview || ADMIN_I18N.saved)
            : (payload.id ? ADMIN_I18N.updated : ADMIN_I18N.saved);
        await loadQuestions();
        closeQuestionEditModal();
        await showAppNotice(successMessage, payload.id ? ADMIN_I18N.updated : ADMIN_I18N.saved);
    } catch (error) {
        console.error(error);
        questionModalMessage.textContent = ADMIN_I18N.error;
    }
}

async function submitGeneratorForm(event) {
    event.preventDefault();

    const mode = document.getElementById("generator_mode").value;
    const questionScope = ADMIN_IS_SUPER_ADMIN
        ? "global"
        : (document.getElementById("generator_question_scope")?.value || "private");
    const payload = {
        category: getCategoryValue("generator_category"),
        topic: document.getElementById("generator_topic").value.trim(),
        difficulty_level: normalizeDifficultyLevel(document.getElementById("generator_difficulty_level").value),
        language: document.getElementById("generator_language").value,
        question_scope: questionScope
    };

    const message = document.getElementById("generator-message");
    message.textContent = ADMIN_I18N.aiProgressPrepare || ADMIN_I18N.loading;
    startAiProgress();

    try {
        const url = mode === "bulk"
            ? appUrl("backend/questions/generate_questions_bulk.php")
            : appUrl("backend/questions/generate_question.php");

        if (mode === "bulk") {
            payload.quantity = parseInt(document.getElementById("generator_quantity").value, 10) || 5;
        }

        const response = await fetch(url, {
            method: "POST",
            headers: csrfHeaders({ "Content-Type": "application/json" }),
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (!result.success) {
            finishAiProgress(false);
            message.textContent = result.message || ADMIN_I18N.error;
            console.error(result);
            return;
        }

        if (mode === "single") {
            finishAiProgress(true);
            message.textContent = ADMIN_I18N.generatedReady;
            openQuestionEditModal({
                ...result,
                id: "",
                origin: "ai",
                status: "pending",
                is_active: 0,
                question_scope: questionScope,
                requires_review: true
            });
            return;
        }

        finishAiProgress(true);
        message.textContent = result.global_request_status === "pending"
            ? (ADMIN_I18N.questionSentToGlobalReview || result.message)
            : (result.message || ADMIN_I18N.massGeneratedSuccess);
        await focusGeneratedQuestions(result.generated_ids || []);
    } catch (error) {
        finishAiProgress(false);
        console.error(error);
        message.textContent = ADMIN_I18N.error;
    }
}

function setupQuestionEditModal() {
    if (!questionEditModal || !questionEditModalForm) return;

    if (openCreateQuestionModalBtn) {
        openCreateQuestionModalBtn.addEventListener("click", () => {
            openQuestionEditModal(null);
        });
    }

    document.getElementById("question-modal-close").addEventListener("click", closeQuestionEditModal);
    document.getElementById("question-modal-cancel").addEventListener("click", closeQuestionEditModal);
    questionEditModal.addEventListener("click", event => {
        if (event.target === questionEditModal) {
            closeQuestionEditModal();
        }
    });
    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && !questionEditModal.hidden) {
            closeQuestionEditModal();
        }
    });
    document.getElementById("modal-language").addEventListener("change", () => {
        populateCategorySelect(
            document.getElementById("modal-category"),
            document.getElementById("modal-language").value,
            getCategoryValue("modal-category")
        );
    });
    document.getElementById("modal-status").addEventListener("change", () => {
        enforceVerifiedAvailability(true);
    });
    document.getElementById("modal-is-active").addEventListener("change", () => {
        enforceVerifiedAvailability(true);
    });
    bindCategorySelectIfExists("modal-category");
    document.querySelectorAll("input[name='modal-correct-option']").forEach(input => {
        input.addEventListener("change", () => setModalCorrectOption(input.value));
    });
    questionEditModalForm.addEventListener("submit", submitQuestionEditModal);
}

function setupCsvImport() {
    if (!csvForm || !csvFileInput || !csvMessage) return;

    csvForm.addEventListener("submit", async event => {
        event.preventDefault();

        if (!csvFileInput.files || csvFileInput.files.length === 0) {
            csvMessage.textContent = ADMIN_I18N.error;
            return;
        }

        const formData = new FormData();
        formData.append("file", csvFileInput.files[0]);
        csvMessage.textContent = ADMIN_I18N.loading;

        try {
            const response = await fetch(appUrl("backend/questions/import_questions_csv.php"), {
                method: "POST",
                headers: csrfHeaders(),
                body: formData
            });
            const result = await response.json();

            csvMessage.textContent = result.message || (result.success ? ADMIN_I18N.saved : ADMIN_I18N.error);

            if (result.success) {
                csvForm.reset();
                await loadQuestions();
            }
        } catch (error) {
            console.error(error);
            csvMessage.textContent = ADMIN_I18N.error;
        } finally {
            csvFileInput.value = "";
        }
    });
}

// Cancelar edición
cancelBtn.addEventListener("click", () => {
    form.reset();

    document.getElementById("difficulty_level").value = "1";
    document.getElementById("status").value = "verified";
    document.getElementById("origin").value = "manual";
    document.getElementById("is_active").value = "1";
    syncCategoryDropdowns();

    editIdInput.value = "";
    saveBtn.textContent = ADMIN_I18N.saveQuestion;
    cancelBtn.style.display = "none";
});

function closeAppConfirmModal(result = false) {
    if (!appConfirmModal) return;

    appConfirmModal.hidden = true;
    document.body.classList.remove("modal-open");

    if (confirmResolver) {
        confirmResolver(result);
        confirmResolver = null;
    }
}

function showAppConfirm(message) {
    if (!appConfirmModal || !appConfirmMessage) {
        return Promise.resolve(false);
    }

    const titleElement = document.getElementById("app-confirm-title");

    if (titleElement) {
        titleElement.textContent = ADMIN_I18N.confirmAction || "Confirmar acción";
    }

    appConfirmCancel.hidden = false;
    appConfirmMessage.textContent = message;
    appConfirmModal.hidden = false;
    document.body.classList.add("modal-open");

    return new Promise(resolve => {
        confirmResolver = resolve;
    });
}

function showAppNotice(message, title = ADMIN_I18N.saved) {
    if (!appConfirmModal || !appConfirmMessage) {
        return Promise.resolve(true);
    }

    const titleElement = document.getElementById("app-confirm-title");

    if (titleElement) {
        titleElement.textContent = title;
    }

    appConfirmMessage.textContent = message;
    appConfirmCancel.hidden = true;
    appConfirmModal.hidden = false;
    document.body.classList.add("modal-open");

    return new Promise(resolve => {
        confirmResolver = result => {
            appConfirmCancel.hidden = false;
            resolve(result);
        };
    });
}

function setupAppConfirmModal() {
    if (!appConfirmModal) return;

    appConfirmAccept?.addEventListener("click", () => closeAppConfirmModal(true));
    appConfirmCancel?.addEventListener("click", () => closeAppConfirmModal(false));
    appConfirmClose?.addEventListener("click", () => closeAppConfirmModal(false));
    appConfirmModal.addEventListener("click", event => {
        if (event.target === appConfirmModal) {
            closeAppConfirmModal(false);
        }
    });
}

function setupQuestionFilters() {
    [
        questionSearchInput,
        questionFilterCategory,
        questionFilterDifficulty,
        questionFilterLanguage,
        questionFilterStatus,
        questionFilterOrigin,
        questionFilterActive
    ].forEach(control => {
        control?.addEventListener("input", applyQuestionFilters);
        control?.addEventListener("change", applyQuestionFilters);
    });

    questionClearFiltersBtn?.addEventListener("click", () => {
        resetGeneratedQuestionFilter();
        if (questionSearchInput) questionSearchInput.value = "";
        if (questionFilterCategory) questionFilterCategory.value = "";
        if (questionFilterDifficulty) questionFilterDifficulty.value = "";
        if (questionFilterLanguage) questionFilterLanguage.value = "";
        if (questionFilterStatus) questionFilterStatus.value = "";
        if (questionFilterOrigin) questionFilterOrigin.value = "";
        if (questionFilterActive) questionFilterActive.value = "";
        applyQuestionFilters();
    });

    reviewGeneratedQuestionsBtn?.addEventListener("click", async () => {
        await focusGeneratedQuestions(generatedQuestionIdsFilter);
    });
}

// Eliminar
async function deleteQuestion(id) {
    const accepted = await showAppConfirm(ADMIN_I18N.confirmDelete);
    if (!accepted) return;

    try {
        const response = await fetch(appUrl("backend/questions/delete_question.php"), {
            method: "POST",
            headers: csrfHeaders({ "Content-Type": "application/json" }),
            body: JSON.stringify({ id })
        });
        const result = await response.json();

        if (!result.success) {
            await showAppNotice(result.message || ADMIN_I18N.error, ADMIN_I18N.error);
            return;
        }

        await showAppNotice(result.message || ADMIN_I18N.deleted || ADMIN_I18N.saved, ADMIN_I18N.saved);
        loadQuestions();
    } catch (error) {
        console.error(error);
    }
}

// INIT
bindCategorySelect("category");
bindCategorySelect("generator_category");
setupAdminAccordions();
setupQuestionEditModal();
setupCsvImport();
setupAppConfirmModal();
setupQuestionFilters();
generatorForm?.addEventListener("submit", submitGeneratorForm);

document.getElementById("language").addEventListener("change", () => {
    populateCategorySelect(
        document.getElementById("category"),
        document.getElementById("language").value
    );
});

document.getElementById("generator_language").addEventListener("change", () => {
    populateCategorySelect(
        document.getElementById("generator_category"),
        document.getElementById("generator_language").value
    );
});

function updateGeneratorMode() {
    const mode = document.getElementById("generator_mode").value;
    const quantityGroup = document.getElementById("generator_quantity_group");
    const quantityInput = document.getElementById("generator_quantity");

    quantityGroup.hidden = mode !== "bulk";
    quantityInput.required = mode === "bulk";
}

document.getElementById("generator_mode").addEventListener("change", updateGeneratorMode);

syncCategoryDropdowns();
updateGeneratorMode();

function applyMassGeneratorParams() {
    const params = new URLSearchParams(window.location.search);

    if (!params.has("category") && !params.has("difficulty") && !params.has("quantity")) {
        return;
    }

    const language = params.get("language") || "es";
    const category = params.get("category") || "";
    const difficulty = normalizeDifficultyLevel(params.get("difficulty") || "1");
    const quantity = params.get("quantity") || "5";
    const topic = params.get("topic") || "";

    document.getElementById("generator_mode").value = "bulk";
    updateGeneratorMode();

    document.getElementById("generator_language").value =
        ["es", "en"].includes(language) ? language : "es";

    populateCategorySelect(
        document.getElementById("generator_category"),
        document.getElementById("generator_language").value,
        category
    );

    document.getElementById("generator_topic").value = topic;
    document.getElementById("generator_quantity").value = quantity;
    document.getElementById("generator_difficulty_level").value = difficulty;

    openAdminSectionById("ai-generator", { scroll: true });
}

applyMassGeneratorParams();
loadQuestions();
