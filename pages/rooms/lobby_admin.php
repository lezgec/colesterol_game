<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../../config/question_categories.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$roomCode = strtoupper(trim($_GET["code"] ?? ""));
$styleVersion = filemtime(__DIR__ . '/../../assets/css/style.css');
$themeVersion = filemtime(__DIR__ . '/../../assets/js/theme.js');
$responsiveTablesVersion = filemtime(__DIR__ . '/../../assets/js/responsive_tables.js');

if ($roomCode === "") {
    header("Location: " . app_path("pages/rooms/create.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("room_lobby"); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=es">ES</a>
            <span>|</span>
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=en">EN</a>
        </div>

        <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>" class="logout-btn secondary-btn">
            <?php echo t("back_to_admin"); ?>
        </a>
    </div>

    <h1><?php echo t("room_lobby"); ?></h1>

    <p>
        <strong><?php echo t("room_code"); ?>:</strong>
        <span id="room-code"><?php echo htmlspecialchars($roomCode); ?></span>
    </p>

    <div class="form-group">
        <label for="room-share-link"><?php echo t("room_share_link"); ?></label>
        <input type="text" id="room-share-link" readonly>
    </div>

    <button id="copy-link-btn" class="primary-btn" type="button">
        <?php echo t("copy_room_link"); ?>
    </button>

    <section class="admin-section">
        <h2><?php echo t("connected_players"); ?></h2>
        <ul id="players-list"></ul>
    </section>

    <section class="admin-section" id="room-questions-panel">
        <h2><?php echo t("room_questions_management"); ?></h2>

        <div class="room-lobby-settings">
            <div class="form-group">
                <label for="lobby-question-count"><?php echo t("question_count"); ?></label>
                <input type="number" id="lobby-question-count" min="1" max="50" value="10">
            </div>

            <div class="form-group">
                <label for="lobby-time-limit"><?php echo t("time_limit"); ?></label>
                <input type="number" id="lobby-time-limit" min="5" max="120" value="20">
            </div>

            <button id="save-room-settings-btn" class="primary-btn secondary-btn" type="button">
                <?php echo t("save"); ?>
            </button>
        </div>

        <div id="room-questions-summary" class="room-question-summary"></div>

        <div class="room-requirement-builder">
            <button
                type="button"
                class="hint-icon-button"
                data-hint-title="<?php echo htmlspecialchars(t("room_question_blocks_hint_title")); ?>"
                data-hint-text="<?php echo htmlspecialchars(t("room_question_blocks_hint")); ?>"
                aria-label="<?php echo t("hint"); ?>"
            >?</button>

            <div class="form-group">
                <label for="room-requirement-category"><?php echo t("category"); ?></label>
                <select id="room-requirement-category"></select>
            </div>

            <div class="form-group">
                <label for="room-requirement-difficulty"><?php echo t("difficulty"); ?></label>
                <select id="room-requirement-difficulty">
                    <?php for ($level = 1; $level <= 5; $level++): ?>
                        <option value="<?php echo $level; ?>"><?php echo $level; ?> / 5</option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="room-requirement-quantity"><?php echo t("question_count"); ?></label>
                <input type="number" id="room-requirement-quantity" min="1" max="50" value="5">
            </div>

            <button id="add-room-requirement-btn" class="primary-btn" type="button">
                <?php echo current_lang() === "en" ? "Add block" : "Agregar bloque"; ?>
            </button>
        </div>

        <div class="admin-room-controls">
            <button id="sync-room-questions-btn" class="primary-btn" type="button">
                <?php echo t("assign_available_questions"); ?>
            </button>

            <a id="review-room-questions-link" class="primary-btn secondary-btn" href="<?php echo app_path('pages/admin_questions.php'); ?>">
                <?php echo t("review_questions_in_admin"); ?>
            </a>
        </div>

        <div id="room-requirements-list" class="room-requirements-list"></div>

        <h3><?php echo t("assigned_questions"); ?></h3>
        <div class="room-selection-actions">
            <button id="remove-selected-room-questions-btn" class="secondary-form-btn" type="button" disabled>
                <?php echo t("remove_selected_questions"); ?>
            </button>
            <span id="assigned-selection-count"></span>
        </div>
        <div id="assigned-room-questions" class="assigned-room-questions"></div>

        <section class="room-question-bank-panel">
            <div class="room-question-bank-heading">
                <div>
                    <h3><?php echo t("room_question_bank"); ?></h3>
                    <p>
                        <?php echo t("room_question_bank_hint_short"); ?>
                        <button
                            type="button"
                            class="hint-icon-button inline-hint"
                            data-hint-title="<?php echo htmlspecialchars(t("room_question_bank")); ?>"
                            data-hint-text="<?php echo htmlspecialchars(t("room_question_bank_hint")); ?>"
                            aria-label="<?php echo t("hint"); ?>"
                        >?</button>
                    </p>
                </div>
                <button id="add-selected-room-questions-btn" class="primary-btn" type="button" disabled>
                    <?php echo t("add_selected_questions"); ?>
                </button>
            </div>

            <div class="question-bank-filters compact-bank-filters" aria-label="<?php echo t("question_filters"); ?>">
                <label class="question-filter-field question-search-field">
                    <span><?php echo t("search"); ?></span>
                    <input type="search" id="room-bank-search" placeholder="<?php echo t("search_question_placeholder"); ?>">
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("category"); ?></span>
                    <select id="room-bank-category">
                        <option value=""><?php echo t("all_categories"); ?></option>
                    </select>
                </label>

                <label class="question-filter-field">
                    <span><?php echo t("difficulty_level"); ?></span>
                    <select id="room-bank-difficulty">
                        <option value=""><?php echo t("all_difficulties"); ?></option>
                        <?php for ($level = 1; $level <= 5; $level++): ?>
                            <option value="<?php echo $level; ?>"><?php echo $level; ?> / 5</option>
                        <?php endfor; ?>
                    </select>
                </label>

                <button type="button" id="room-bank-clear-filters" class="secondary-form-btn">
                    <?php echo t("clear_filters"); ?>
                </button>
            </div>

            <table id="room-question-bank-table" class="admin-table questions-admin-table room-question-bank-table">
                <thead>
                    <tr>
                        <th class="col-select">
                            <input type="checkbox" id="room-bank-select-all" aria-label="<?php echo t("select_all_questions"); ?>">
                        </th>
                        <th class="col-id">ID</th>
                        <th class="col-question"><?php echo t("question"); ?></th>
                        <th class="col-correct"><?php echo t("correct_option"); ?></th>
                        <th class="col-difficulty"><?php echo t("difficulty_level"); ?></th>
                        <th class="col-category"><?php echo t("category"); ?></th>
                        <th class="col-origin"><?php echo t("origin"); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    </section>

    <section class="admin-section">
        <h2><?php echo t("room_control"); ?></h2>

        <p>
            <strong><?php echo t("status"); ?>:</strong>
            <span id="room-status">...</span>
        </p>

        <p>
            <strong><?php echo t("current_question"); ?>:</strong>
            <span id="current-question">...</span>
        </p>

        <p>
            <strong><?php echo t("time_left"); ?>:</strong>
            <span id="time-left">...</span>
        </p>

        <div class="admin-room-controls">
            <button id="start-room-btn" class="primary-btn" type="button">
                <?php echo t("start_game"); ?>
            </button>

            <button id="pause-room-btn" class="primary-btn secondary-btn" type="button">
                <?php echo t("pause_room"); ?>
            </button>

            <button id="resume-room-btn" class="primary-btn secondary-btn" type="button">
                <?php echo t("resume_room"); ?>
            </button>

            <button id="next-room-btn" class="primary-btn secondary-btn" type="button">
                <?php echo t("next_question"); ?>
            </button>

            <button id="finish-room-btn" class="logout-btn" type="button">
                <?php echo t("finish_room"); ?>
            </button>
        </div>
    </section>

    <p id="admin-lobby-message" class="room-status-message" aria-live="polite"></p>
</div>

<script>
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="<?php echo asset_path('js/http.js'); ?>?m=<?php echo filemtime(__DIR__ . '/../../assets/js/http.js'); ?>"></script>
<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const CURRENT_LANG = "<?php echo current_lang(); ?>";
const ROOM_CATEGORIES = <?php echo json_encode([
    "es" => question_categories("es"),
    "en" => question_categories("en")
], JSON_UNESCAPED_UNICODE); ?>;

const LOBBY_I18N = {
    noPlayersYet: "<?php echo t('no_players_yet'); ?>",
    gameStarted: "<?php echo t('game_started'); ?>",
    linkCopied: "<?php echo t('link_copied'); ?>",
    loading: "<?php echo t('loading'); ?>",
    error: "<?php echo t('error'); ?>",
    unnamedPlayer: "<?php echo t('unnamed_player'); ?>",
    roomPausedSuccess: "<?php echo t('room_paused_success'); ?>",
    roomResumedSuccess: "<?php echo t('room_resumed_success'); ?>",
    roomAdvancedSuccess: "<?php echo t('room_advanced_success'); ?>",
    roomFinishedSuccess: "<?php echo t('room_finished_success'); ?>",
    confirmFinishRoom: "<?php echo t('confirm_finish_room'); ?>",
    requiredQuestions: "<?php echo t('required_questions'); ?>",
    readyQuestions: "<?php echo t('room_questions_ready'); ?>",
    missingQuestions: "<?php echo t('room_questions_missing'); ?>",
    assignedQuestions: "<?php echo t('assigned_questions'); ?>",
    assignAvailableQuestions: "<?php echo t('assign_available_questions'); ?>",
    generateMissingForBlock: "<?php echo t('generate_missing_for_block'); ?>",
    reviewQuestionsInAdmin: "<?php echo t('review_questions_in_admin'); ?>",
    readyToStart: "<?php echo t('ready_to_start'); ?>",
    incompleteRoomQuestions: "<?php echo t('incomplete_room_questions'); ?>",
    noAssignedQuestions: "<?php echo t('no_assigned_questions'); ?>",
    removeQuestionFromRoom: "<?php echo t('remove_question_from_room'); ?>",
    confirmRemoveRoomQuestion: "<?php echo t('confirm_remove_room_question'); ?>",
    roomQuestionsSynced: "<?php echo t('room_questions_synced'); ?>",
    roomSettingsSaved: "<?php echo t('room_settings_saved'); ?>",
    waitingOnlySettings: "<?php echo t('waiting_only_settings'); ?>",
    finishedRoomSettings: "<?php echo t('finished_room_settings'); ?>",
    removeBlock: "<?php echo t('remove_block'); ?>",
    duplicateBlockUpdated: "<?php echo t('duplicate_block_updated'); ?>",
    customBlocksHelp: "<?php echo t('custom_blocks_help'); ?>",
    addSelectedQuestions: "<?php echo t('add_selected_questions'); ?>",
    removeSelectedQuestions: "<?php echo t('remove_selected_questions'); ?>",
    selectedQuestionsCount: "<?php echo t('selected_questions_count'); ?>",
    noQuestionBankResults: "<?php echo t('no_filter_results'); ?>",
    questionAlreadyAssigned: "<?php echo t('question_already_assigned'); ?>",
    roomQuestionsAdded: "<?php echo t('room_questions_added'); ?>",
    roomQuestionsRemoved: "<?php echo t('room_questions_removed'); ?>",
    confirmRemoveSelectedRoomQuestions: "<?php echo t('confirm_remove_selected_room_questions'); ?>",
    allCategories: "<?php echo t('all_categories'); ?>",
    hint: "<?php echo t('hint'); ?>",
    category: "<?php echo t('category'); ?>",
    difficulty: "<?php echo t('difficulty'); ?>",
    requested: "<?php echo t('requested'); ?>",
    available: "<?php echo t('available'); ?>",
    verified: "<?php echo t('verified'); ?>",
    pending: "<?php echo t('pending'); ?>",
    rejected: "<?php echo t('rejected'); ?>",
    active: "<?php echo t('active'); ?>",
    inactive: "<?php echo t('inactive'); ?>",
    unknownStatus: "<?php echo t('room_status_unknown'); ?>",
    close: "<?php echo t('close'); ?>",
    cancel: "<?php echo t('cancel'); ?>",
    confirm: "<?php echo t('confirm'); ?>",
    confirmAction: "<?php echo t('confirm_action'); ?>",
    statuses: {
        waiting: "<?php echo t('room_status_waiting'); ?>",
        started: "<?php echo t('room_status_started'); ?>",
        paused: "<?php echo t('room_status_paused'); ?>",
        finished: "<?php echo t('room_status_finished'); ?>"
    }
};

const joinLink = `${window.location.origin}${appUrl(`pages/rooms/join.php?code=${encodeURIComponent(ROOM_CODE)}`)}`;
let playersSignature = "";
let roomQuestionsReady = false;
let roomIsWaiting = true;
let roomIsFinished = false;
let roomQuestionLanguage = CURRENT_LANG;
let lobbyModalResolver = null;
let lastQuestionCount = 10;
let lastTimeLimit = 20;
let roomRequirements = [];
let roomQuestionBank = [];
let assignedSelectedIds = new Set();
let bankSelectedIds = new Set();

document.getElementById("room-share-link").value = joinLink;
document.getElementById("review-room-questions-link").href =
    appUrl(`pages/admin_questions.php?return_room=${encodeURIComponent(ROOM_CODE)}&lang=${encodeURIComponent(CURRENT_LANG)}#question-bank-section`);

function formatRoomStatus(status) {
    return LOBBY_I18N.statuses[status] || LOBBY_I18N.unknownStatus;
}

function formatQuestionStatus(status) {
    if (status === "verified") return LOBBY_I18N.verified;
    if (status === "pending") return LOBBY_I18N.pending;
    if (status === "rejected") return LOBBY_I18N.rejected;
    return status || "-";
}

function updateStartRoomAvailability() {
    const startButton = document.getElementById("start-room-btn");
    const settingsButton = document.getElementById("save-room-settings-btn");
    const questionCountInput = document.getElementById("lobby-question-count");
    const timeLimitInput = document.getElementById("lobby-time-limit");

    startButton.disabled = !roomQuestionsReady || !roomIsWaiting;
    startButton.title = roomQuestionsReady
        ? ""
        : LOBBY_I18N.incompleteRoomQuestions;

    const editable = !roomIsFinished;

    if (settingsButton && questionCountInput && timeLimitInput) {
        settingsButton.disabled = !editable;
        questionCountInput.disabled = !editable;
        timeLimitInput.disabled = !editable;
        settingsButton.title = editable ? "" : LOBBY_I18N.finishedRoomSettings;
    }

    document
        .querySelectorAll("#add-room-requirement-btn, [data-remove-requirement]")
        .forEach(control => {
            control.disabled = !editable;
            control.title = editable ? "" : LOBBY_I18N.finishedRoomSettings;
        });

    updateAssignedSelectionState();
    updateBankSelectionState();
}

function ensureLobbyConfirmModal() {
    let modal = document.getElementById("lobby-confirm-modal");

    if (modal) {
        return modal;
    }

    modal = document.createElement("div");
    modal.id = "lobby-confirm-modal";
    modal.className = "question-modal-backdrop app-confirm-backdrop";
    modal.hidden = true;
    modal.innerHTML = `
        <section class="app-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="lobby-confirm-title">
            <header>
                <h2 id="lobby-confirm-title">${LOBBY_I18N.confirmAction}</h2>
                <button type="button" class="question-modal-close" id="lobby-confirm-close" aria-label="${LOBBY_I18N.close}">&times;</button>
            </header>
            <p id="lobby-confirm-message"></p>
            <footer>
                <button type="button" id="lobby-confirm-cancel" class="secondary-form-btn">${LOBBY_I18N.cancel}</button>
                <button type="button" id="lobby-confirm-accept" class="primary-btn">${LOBBY_I18N.confirm}</button>
            </footer>
        </section>
    `;
    document.body.appendChild(modal);

    const close = result => {
        modal.hidden = true;
        document.body.classList.remove("modal-open");

        if (lobbyModalResolver) {
            lobbyModalResolver(result);
            lobbyModalResolver = null;
        }
    };

    modal.querySelector("#lobby-confirm-close").addEventListener("click", () => close(false));
    modal.querySelector("#lobby-confirm-cancel").addEventListener("click", () => close(false));
    modal.querySelector("#lobby-confirm-accept").addEventListener("click", () => close(true));
    modal.addEventListener("click", event => {
        if (event.target === modal) {
            close(false);
        }
    });

    return modal;
}

function showLobbyConfirm(message) {
    const modal = ensureLobbyConfirmModal();
    modal.querySelector("#lobby-confirm-message").textContent = message;
    modal.hidden = false;
    document.body.classList.add("modal-open");

    return new Promise(resolve => {
        lobbyModalResolver = resolve;
    });
}

function setLobbyMessage(text, type = "") {
    const message = document.getElementById("admin-lobby-message");
    message.textContent = text;
    message.classList.remove("is-info", "is-success", "is-error", "is-warning");

    if (type) {
        message.classList.add(`is-${type}`);
    }
}

document.getElementById("copy-link-btn").addEventListener("click", async () => {
    try {
        await navigator.clipboard.writeText(joinLink);
        document.getElementById("copy-link-btn").textContent = LOBBY_I18N.linkCopied;
        setLobbyMessage(LOBBY_I18N.linkCopied, "success");
    } catch (error) {
        setLobbyMessage(LOBBY_I18N.error, "error");
        console.error(error);
    }
});

async function postRoomAction(endpoint) {
    const res = await fetch(appUrl(`backend/rooms/${endpoint}`), {
        method: "POST",
        headers: csrfHeaders({"Content-Type": "application/json"}),
        body: JSON.stringify({ room_code: ROOM_CODE })
    });

    const text = await res.text();

    let result;

    try {
        result = JSON.parse(text);
    } catch (error) {
        console.error("Respuesta no JSON:", text);
        throw error;
    }

    return result;
}

async function loadPlayers() {
    try {
        const res = await fetch(appUrl(`backend/rooms/get_room_players.php?code=${encodeURIComponent(ROOM_CODE)}`));
        const players = await res.json();

        const list = document.getElementById("players-list");
        const nextSignature = Array.isArray(players)
            ? players.map(player => player.player_name).join("|")
            : "";

        if (nextSignature === playersSignature) {
            return;
        }

        playersSignature = nextSignature;
        list.innerHTML = "";

        if (!Array.isArray(players) || players.length === 0) {
            list.innerHTML = `<li class="player-pill is-empty">${LOBBY_I18N.noPlayersYet}</li>`;
            return;
        }

        players.forEach((player, index) => {
            const li = document.createElement("li");
            li.classList.add("player-pill");
            li.style.animationDelay = `${Math.min(index * 60, 360)}ms`;
            const name = player.player_name || LOBBY_I18N.unnamedPlayer;
            const initial = name.trim().charAt(0).toUpperCase() || "?";

            li.innerHTML = `
                <span class="player-pill-avatar">${initial}</span>
                <span class="player-pill-name">${name}</span>
            `;

            list.appendChild(li);
        });
    } catch (error) {
        console.error(error);
    }
}

async function loadRoomState() {
    try {
        const res = await fetch(appUrl(`backend/rooms/get_room_game_state.php?code=${encodeURIComponent(ROOM_CODE)}`));
        const state = await res.json();

        if (!state.success) return;

        roomIsWaiting = state.status === "waiting";
        roomIsFinished = state.status === "finished";
        document.getElementById("room-status").textContent = formatRoomStatus(state.status);
        document.getElementById("current-question").textContent =
            `${state.current_question_index + 1} / ${state.question_count}`;
        document.getElementById("time-left").textContent =
            `${state.time_left}s`;
        updateStartRoomAvailability();

    } catch (error) {
        console.error(error);
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function populateRequirementCategories() {
    const select = document.getElementById("room-requirement-category");
    const categories = ROOM_CATEGORIES[roomQuestionLanguage] || ROOM_CATEGORIES[CURRENT_LANG] || [];

    select.innerHTML = categories
        .map(category => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`)
        .join("");
}

function requirementTotal(requirements = roomRequirements) {
    return requirements.reduce((total, requirement) => total + Number(requirement.quantity || 0), 0);
}

function syncQuestionCountWithRequirements() {
    return;
}

function updateAssignedSelectionState() {
    assignedSelectedIds = new Set(
        Array.from(document.querySelectorAll(".assigned-question-checkbox:checked"))
            .map(input => Number(input.dataset.assignedQuestionId))
            .filter(Number.isFinite)
    );
    const selected = assignedSelectedIds.size;
    const button = document.getElementById("remove-selected-room-questions-btn");
    const count = document.getElementById("assigned-selection-count");

    if (button) {
        button.disabled = selected === 0 || roomIsFinished;
    }

    if (count) {
        count.textContent = selected > 0
            ? LOBBY_I18N.selectedQuestionsCount.replace("{count}", selected)
            : "";
    }
}

function updateBankSelectionState() {
    document.querySelectorAll(".room-bank-question-checkbox:not(:disabled)").forEach(input => {
        const id = Number(input.value);

        if (!Number.isFinite(id)) {
            return;
        }

        if (input.checked) {
            bankSelectedIds.add(id);
        } else {
            bankSelectedIds.delete(id);
        }
    });

    const selected = bankSelectedIds.size;
    const button = document.getElementById("add-selected-room-questions-btn");

    if (button) {
        button.disabled = selected === 0 || roomIsFinished;
        button.textContent = selected > 0
            ? `${LOBBY_I18N.addSelectedQuestions} (${selected})`
            : LOBBY_I18N.addSelectedQuestions;
    }
}

function showHintModal(title, text) {
    const modal = ensureLobbyConfirmModal();
    const titleElement = modal.querySelector("#lobby-confirm-title");
    const messageElement = modal.querySelector("#lobby-confirm-message");
    const cancelButton = modal.querySelector("#lobby-confirm-cancel");
    const acceptButton = modal.querySelector("#lobby-confirm-accept");

    titleElement.textContent = title || LOBBY_I18N.hint;
    messageElement.textContent = text || "";
    cancelButton.hidden = true;
    acceptButton.textContent = LOBBY_I18N.close;
    modal.hidden = false;
    document.body.classList.add("modal-open");

    lobbyModalResolver = () => {
        cancelButton.hidden = false;
        acceptButton.textContent = LOBBY_I18N.confirm;
    };
}

function getFilteredRoomBankQuestions() {
    const search = (document.getElementById("room-bank-search")?.value || "").trim().toLowerCase();
    const category = document.getElementById("room-bank-category")?.value || "";
    const difficulty = document.getElementById("room-bank-difficulty")?.value || "";

    return roomQuestionBank.filter(question => {
        const searchable = [
            question.id,
            question.question,
            question.correct_option,
            question.category,
            question.origin
        ].join(" ").toLowerCase();

        return (!search || searchable.includes(search)) &&
            (!category || question.category === category) &&
            (!difficulty || String(Math.round(Number(question.difficulty_level || 1))) === difficulty);
    });
}

function populateRoomBankFilters() {
    const select = document.getElementById("room-bank-category");
    if (!select) return;

    const current = select.value;
    const categories = Array.from(new Set(roomQuestionBank.map(q => q.category).filter(Boolean)))
        .sort((a, b) => a.localeCompare(b));

    select.innerHTML = `<option value="">${LOBBY_I18N.allCategories}</option>`;
    categories.forEach(category => {
        const option = document.createElement("option");
        option.value = category;
        option.textContent = category;
        select.appendChild(option);
    });

    select.value = categories.includes(current) ? current : "";
}

function renderRoomQuestionBank() {
    const tbody = document.querySelector("#room-question-bank-table tbody");
    if (!tbody) return;

    updateBankSelectionState();
    const data = getFilteredRoomBankQuestions();

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7">${LOBBY_I18N.noQuestionBankResults}</td></tr>`;
        updateBankSelectionState();
        return;
    }

    tbody.innerHTML = data.map(question => {
        const isAssigned = Number(question.assigned_to_room) === 1;
        const isChecked = bankSelectedIds.has(Number(question.id)) && !isAssigned && !roomIsFinished;
        return `
            <tr class="${isAssigned ? "is-assigned-to-room" : ""}">
                <td class="col-select">
                    <input
                        type="checkbox"
                        class="room-bank-question-checkbox"
                        value="${question.id}"
                        ${isAssigned || roomIsFinished ? "disabled" : ""}
                        ${isChecked ? "checked" : ""}
                    >
                </td>
                <td class="col-id">${question.id}</td>
                <td class="col-question question-text-cell">
                    ${escapeHtml(question.question)}
                    ${isAssigned ? `<span class="room-bank-assigned-pill">${LOBBY_I18N.questionAlreadyAssigned}</span>` : ""}
                </td>
                <td class="col-correct">${escapeHtml(question.correct_option || "-")}</td>
                <td class="col-difficulty">${Math.round(Number(question.difficulty_level || 1))} / 5</td>
                <td class="col-category">${escapeHtml(question.category || "-")}</td>
                <td class="col-origin">${escapeHtml(question.origin || "-")}</td>
            </tr>
        `;
    }).join("");

    tbody.querySelectorAll(".room-bank-question-checkbox").forEach(checkbox => {
        checkbox.addEventListener("change", updateBankSelectionState);
    });

    const selectAll = document.getElementById("room-bank-select-all");
    if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }

    updateBankSelectionState();
}

async function loadRoomQuestionBank() {
    try {
        const res = await fetch(appUrl(`backend/rooms/get_questions_for_room_setup.php?code=${encodeURIComponent(ROOM_CODE)}&lang=${encodeURIComponent(roomQuestionLanguage || CURRENT_LANG)}`));
        const data = await res.json();

        if (!Array.isArray(data)) {
            return;
        }

        roomQuestionBank = data;
        populateRoomBankFilters();
        renderRoomQuestionBank();
    } catch (error) {
        console.error(error);
    }
}

function buildGeneratorUrl(requirement) {
    const missing = Math.max(0, Number(requirement.missing || 0));
    const params = new URLSearchParams({
        source: "room",
        return_room: ROOM_CODE,
        lang: CURRENT_LANG,
        language: roomQuestionLanguage,
        category: requirement.category,
        difficulty: requirement.difficulty,
        quantity: missing,
        topic: requirement.category
    });

    return appUrl(`pages/admin_questions.php?${params.toString()}#mass-generator`);
}

function renderRoomQuestionSetup(setup) {
    const summary = document.getElementById("room-questions-summary");
    const requirementsList = document.getElementById("room-requirements-list");
    const assignedContainer = document.getElementById("assigned-room-questions");

    updateAssignedSelectionState();
    roomQuestionsReady = Boolean(setup.ready);
    roomQuestionLanguage = setup.room?.language || roomQuestionLanguage;
    roomIsFinished = setup.room?.status === "finished";
    lastQuestionCount = Number(setup.room?.question_count || setup.required_total || lastQuestionCount);
    lastTimeLimit = Number(setup.room?.time_limit || lastTimeLimit);
    roomRequirements = Array.isArray(setup.requirements)
        ? setup.requirements.map(requirement => ({
            category: requirement.category,
            difficulty: Number(requirement.difficulty || 1),
            quantity: Number(requirement.quantity || 1),
            assigned: Number(requirement.assigned || 0),
            available: Number(requirement.available || 0),
            missing: Number(requirement.missing || 0)
        }))
        : [];
    populateRequirementCategories();
    syncQuestionCountWithRequirements();

    const questionCountInput = document.getElementById("lobby-question-count");
    const timeLimitInput = document.getElementById("lobby-time-limit");

    if (questionCountInput && document.activeElement !== questionCountInput) {
        questionCountInput.value = lastQuestionCount;
    }

    if (timeLimitInput && document.activeElement !== timeLimitInput) {
        timeLimitInput.value = lastTimeLimit;
    }

    updateStartRoomAvailability();

    const summaryClass = setup.ready ? "is-success" : "is-warning";
    summary.className = `room-question-summary ${summaryClass}`;
    summary.innerHTML = `
        <span><strong>${LOBBY_I18N.requiredQuestions}:</strong> ${setup.required_total}</span>
        <span><strong>${LOBBY_I18N.readyQuestions}:</strong> ${setup.assigned_ready_total}</span>
        <span><strong>${LOBBY_I18N.missingQuestions}:</strong> ${setup.missing_total}</span>
        <em>${setup.ready ? LOBBY_I18N.readyToStart : LOBBY_I18N.incompleteRoomQuestions}</em>
    `;

    if (!roomRequirements.length) {
        requirementsList.innerHTML = `<p>${LOBBY_I18N.customBlocksHelp}</p>`;
    } else {
        requirementsList.innerHTML = `
            <table class="admin-table room-config-table">
                <thead>
                    <tr>
                        <th>${LOBBY_I18N.category}</th>
                        <th>${LOBBY_I18N.difficulty}</th>
                        <th>${LOBBY_I18N.requested}</th>
                        <th>${LOBBY_I18N.assignedQuestions}</th>
                        <th>${LOBBY_I18N.available}</th>
                        <th>${LOBBY_I18N.missingQuestions}</th>
                        <th>${LOBBY_I18N.removeBlock}</th>
                    </tr>
                </thead>
                <tbody>
                    ${roomRequirements.map((requirement, index) => {
                        const missing = Number(requirement.missing || 0);
                        return `
                            <tr>
                                <td>${escapeHtml(requirement.category)}</td>
                                <td>${requirement.difficulty} / 5</td>
                                <td>${requirement.quantity}</td>
                                <td>${requirement.assigned}</td>
                                <td>${requirement.available}</td>
                                <td>${missing}</td>
                                <td>
                                    ${missing > 0
                                        ? `<a class="table-btn edit-btn" href="${buildGeneratorUrl(requirement)}">${LOBBY_I18N.generateMissingForBlock}</a>`
                                        : ""}
                                    <button type="button" class="table-btn delete-btn" data-remove-requirement="${index}">
                                        ${LOBBY_I18N.removeBlock}
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join("")}
                </tbody>
            </table>
        `;
    }

    requirementsList.querySelectorAll("[data-remove-requirement]").forEach(button => {
        button.addEventListener("click", async () => {
            const index = Number(button.dataset.removeRequirement);
            roomRequirements.splice(index, 1);
            syncQuestionCountWithRequirements();
            await saveRoomSettings();
        });
    });

    if (!Array.isArray(setup.assigned_questions) || setup.assigned_questions.length === 0) {
        assignedContainer.innerHTML = `<p>${LOBBY_I18N.noAssignedQuestions}</p>`;
        updateAssignedSelectionState();
        return;
    }

    assignedContainer.innerHTML = setup.assigned_questions.map(question => {
        const activeLabel = Number(question.is_active) === 1
            ? LOBBY_I18N.active
            : LOBBY_I18N.inactive;
        const isChecked = assignedSelectedIds.has(Number(question.id));

        return `
            <details class="room-question-detail">
                <summary>
                    <input
                        type="checkbox"
                        class="assigned-question-checkbox"
                        data-assigned-question-id="${question.id}"
                        aria-label="${LOBBY_I18N.removeQuestionFromRoom}"
                        ${isChecked ? "checked" : ""}
                    >
                    <span class="room-question-id-badge">#${question.id}</span>
                    <strong>${question.question}</strong>
                    <em class="room-question-state-pills">
                        <span>${formatQuestionStatus(question.status)}</span>
                        <span>${activeLabel}</span>
                    </em>
                </summary>
                <div class="room-question-detail-body">
                    <p>
                        <strong>${LOBBY_I18N.category}:</strong> ${question.category}
                        | <strong>${LOBBY_I18N.difficulty}:</strong> ${Math.min(5, Math.max(1, Math.round(Number(question.difficulty_level || 1))))} / 5
                        | <strong><?php echo t("correct_option"); ?>:</strong> ${question.correct_option}
                    </p>
                    <button
                        type="button"
                        class="table-btn delete-btn"
                        data-remove-room-question="${question.id}"
                    >
                        ${LOBBY_I18N.removeQuestionFromRoom}
                    </button>
                </div>
            </details>
        `;
    }).join("");

    assignedContainer.querySelectorAll("[data-remove-room-question]").forEach(button => {
        button.addEventListener("click", async () => {
            const questionId = Number(button.dataset.removeRoomQuestion);
            await removeRoomQuestion(questionId);
        });
    });

    assignedContainer.querySelectorAll(".assigned-question-checkbox").forEach(checkbox => {
        checkbox.addEventListener("click", event => {
            event.stopPropagation();
        });
        checkbox.addEventListener("change", updateAssignedSelectionState);
    });

    updateAssignedSelectionState();
}

async function loadRoomQuestionSetup() {
    try {
        const res = await fetch(appUrl(`backend/rooms/get_room_question_setup.php?code=${encodeURIComponent(ROOM_CODE)}`));
        const setup = await res.json();

        if (!setup.success) {
            setLobbyMessage(setup.message || LOBBY_I18N.error, "error");
            return;
        }

        roomQuestionLanguage = setup.room?.language || roomQuestionLanguage;
        await loadRoomQuestionBank();
        renderRoomQuestionSetup(setup);
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
}

async function syncRoomQuestions() {
    try {
        setLobbyMessage(LOBBY_I18N.loading, "info");

        const result = await postRoomAction("sync_room_questions.php");

        if (!result.success) {
            setLobbyMessage(result.error || result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(`${LOBBY_I18N.roomQuestionsSynced}: ${result.inserted}`, "success");
        await loadRoomQuestionSetup();
        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
}

async function saveRoomSettings() {
    const questionCount = parseInt(document.getElementById("lobby-question-count").value, 10);
    const timeLimit = parseInt(document.getElementById("lobby-time-limit").value, 10);

    if (roomIsFinished) {
        setLobbyMessage(LOBBY_I18N.finishedRoomSettings, "warning");
        return;
    }

    if (!Number.isFinite(questionCount) || questionCount < 1 || questionCount > 50) {
        setLobbyMessage(LOBBY_I18N.incompleteRoomQuestions, "warning");
        return;
    }

    if (!Number.isFinite(timeLimit) || timeLimit < 5 || timeLimit > 120) {
        setLobbyMessage(LOBBY_I18N.error, "warning");
        return;
    }

    try {
        setLobbyMessage(LOBBY_I18N.loading, "info");

        const res = await fetch(appUrl("backend/rooms/update_room_settings.php"), {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({
                room_code: ROOM_CODE,
                question_count: questionCount,
                time_limit: timeLimit,
                requirements: roomRequirements.map(requirement => ({
                    category: requirement.category,
                    difficulty: Number(requirement.difficulty),
                    quantity: Number(requirement.quantity)
                }))
            })
        });

        const result = await res.json();

        if (!result.success) {
            setLobbyMessage(result.error || result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.roomSettingsSaved, "success");
        await loadRoomQuestionSetup();
        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
}

async function addRoomRequirement() {
    if (roomIsFinished) {
        setLobbyMessage(LOBBY_I18N.finishedRoomSettings, "warning");
        return;
    }

    const category = document.getElementById("room-requirement-category").value;
    const difficulty = parseInt(document.getElementById("room-requirement-difficulty").value, 10);
    const quantity = parseInt(document.getElementById("room-requirement-quantity").value, 10);

    if (!category || !Number.isFinite(difficulty) || !Number.isFinite(quantity) || quantity < 1) {
        setLobbyMessage(LOBBY_I18N.error, "warning");
        return;
    }

    const existing = roomRequirements.find(requirement =>
        requirement.category === category && Number(requirement.difficulty) === difficulty
    );

    if (existing) {
        existing.quantity = quantity;
        setLobbyMessage(LOBBY_I18N.duplicateBlockUpdated, "info");
    } else {
        roomRequirements.push({ category, difficulty, quantity });
    }

    syncQuestionCountWithRequirements();
    await saveRoomSettings();
}

async function removeRoomQuestion(questionId) {
    if (!await showLobbyConfirm(LOBBY_I18N.confirmRemoveRoomQuestion)) {
        return;
    }

    try {
        const res = await fetch(appUrl("backend/rooms/remove_room_question.php"), {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({
                room_code: ROOM_CODE,
                question_id: questionId
            })
        });

        const result = await res.json();

        if (!result.success) {
            setLobbyMessage(result.error || result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(result.message, "success");
        await loadRoomQuestionSetup();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
}

async function addSelectedRoomQuestions() {
    const selectedIds = Array.from(document.querySelectorAll(".room-bank-question-checkbox:checked:not(:disabled)"))
        .map(input => Number(input.value))
        .filter(Number.isFinite);

    if (selectedIds.length === 0 || roomIsFinished) {
        return;
    }

    try {
        setLobbyMessage(LOBBY_I18N.loading, "info");

        const res = await fetch(appUrl("backend/rooms/add_room_questions.php"), {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({
                room_code: ROOM_CODE,
                question_ids: selectedIds
            })
        });

        const result = await res.json();

        if (!result.success) {
            setLobbyMessage(result.error || result.message || LOBBY_I18N.error, "error");
            return;
        }

        bankSelectedIds.clear();
        setLobbyMessage(`${LOBBY_I18N.roomQuestionsAdded}: ${result.inserted}`, "success");
        await loadRoomQuestionSetup();
        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
}

async function removeSelectedRoomQuestions() {
    const selectedIds = Array.from(document.querySelectorAll(".assigned-question-checkbox:checked"))
        .map(input => Number(input.dataset.assignedQuestionId))
        .filter(Number.isFinite);

    if (selectedIds.length === 0) {
        return;
    }

    if (!await showLobbyConfirm(LOBBY_I18N.confirmRemoveSelectedRoomQuestions)) {
        return;
    }

    try {
        const res = await fetch(appUrl("backend/rooms/remove_room_question.php"), {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({
                room_code: ROOM_CODE,
                question_ids: selectedIds
            })
        });

        const result = await res.json();

        if (!result.success) {
            setLobbyMessage(result.error || result.message || LOBBY_I18N.error, "error");
            return;
        }

        assignedSelectedIds.clear();
        setLobbyMessage(`${LOBBY_I18N.roomQuestionsRemoved}: ${result.removed || 0}`, "success");
        await loadRoomQuestionSetup();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
}

document.getElementById("sync-room-questions-btn").addEventListener("click", syncRoomQuestions);
document.getElementById("save-room-settings-btn").addEventListener("click", saveRoomSettings);
document.getElementById("add-room-requirement-btn").addEventListener("click", addRoomRequirement);
document.getElementById("add-selected-room-questions-btn").addEventListener("click", addSelectedRoomQuestions);
document.getElementById("remove-selected-room-questions-btn").addEventListener("click", removeSelectedRoomQuestions);
["room-bank-search", "room-bank-category", "room-bank-difficulty"].forEach(id => {
    document.getElementById(id)?.addEventListener("input", renderRoomQuestionBank);
    document.getElementById(id)?.addEventListener("change", renderRoomQuestionBank);
});
document.getElementById("room-bank-clear-filters")?.addEventListener("click", () => {
    document.getElementById("room-bank-search").value = "";
    document.getElementById("room-bank-category").value = "";
    document.getElementById("room-bank-difficulty").value = "";
    renderRoomQuestionBank();
});
document.getElementById("room-bank-select-all")?.addEventListener("change", event => {
    document.querySelectorAll(".room-bank-question-checkbox:not(:disabled)").forEach(checkbox => {
        checkbox.checked = event.target.checked;
    });
    updateBankSelectionState();
});
document.querySelectorAll("[data-hint-title][data-hint-text]").forEach(button => {
    button.addEventListener("mouseenter", () => {
        button.setAttribute("title", button.dataset.hintText || "");
    });
    button.addEventListener("click", () => {
        showHintModal(button.dataset.hintTitle, button.dataset.hintText);
    });
});

document.getElementById("start-room-btn").addEventListener("click", async () => {
    if (!roomQuestionsReady) {
        setLobbyMessage(LOBBY_I18N.incompleteRoomQuestions, "warning");
        await loadRoomQuestionSetup();
        return;
    }

    try {
        const result = await postRoomAction("start_room.php");

        if (result.success === false) {
            setLobbyMessage(result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.gameStarted, "success");

        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
});

document.getElementById("pause-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("pause_room.php");
        if (result.success === false) {
            setLobbyMessage(result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.roomPausedSuccess, "warning");
        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
});

document.getElementById("resume-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("resume_room.php");
        if (result.success === false) {
            setLobbyMessage(result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.roomResumedSuccess, "success");
        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
});

document.getElementById("next-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("next_room_question.php");
        if (result.success === false) {
            setLobbyMessage(result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.roomAdvancedSuccess, "info");
        await loadRoomState();
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
});

document.getElementById("finish-room-btn").addEventListener("click", async () => {
    if (!await showLobbyConfirm(LOBBY_I18N.confirmFinishRoom)) return;

    try {
        const result = await postRoomAction("finish_room.php");
        if (result.success === false) {
            setLobbyMessage(result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.roomFinishedSuccess, "success");
        await loadRoomState();

        window.location.href =
            appUrl(`pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}`);
    } catch (error) {
        console.error(error);
        setLobbyMessage(LOBBY_I18N.error, "error");
    }
});

loadPlayers();
loadRoomState();
loadRoomQuestionSetup();

setInterval(loadPlayers, 2000);
setInterval(loadRoomState, 1000);
setInterval(loadRoomQuestionSetup, 5000);
</script>

<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
</body>
</html>
