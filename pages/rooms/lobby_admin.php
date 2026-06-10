<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../lang/translate.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    header("Location: /colesterol_game/pages/rooms/create.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("room_lobby"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=es">ES</a>
            <span>|</span>
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
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

        <div id="room-questions-summary" class="room-question-summary"></div>

        <div class="admin-room-controls">
            <button id="sync-room-questions-btn" class="primary-btn" type="button">
                <?php echo t("assign_available_questions"); ?>
            </button>

            <a id="review-room-questions-link" class="primary-btn secondary-btn" href="/colesterol_game/pages/admin_questions.php">
                <?php echo t("review_questions_in_admin"); ?>
            </a>
        </div>

        <div id="room-requirements-list" class="room-requirements-list"></div>

        <h3><?php echo t("assigned_questions"); ?></h3>
        <div id="assigned-room-questions" class="assigned-room-questions"></div>
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
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const CURRENT_LANG = "<?php echo current_lang(); ?>";

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
    statuses: {
        waiting: "<?php echo t('room_status_waiting'); ?>",
        started: "<?php echo t('room_status_started'); ?>",
        paused: "<?php echo t('room_status_paused'); ?>",
        finished: "<?php echo t('room_status_finished'); ?>"
    }
};

const joinLink = `${window.location.origin}/colesterol_game/pages/rooms/join.php?code=${encodeURIComponent(ROOM_CODE)}`;
let playersSignature = "";
let roomQuestionsReady = false;
let roomIsWaiting = true;
let roomQuestionLanguage = CURRENT_LANG;

document.getElementById("room-share-link").value = joinLink;
document.getElementById("review-room-questions-link").href =
    `/colesterol_game/pages/admin_questions.php?return_room=${encodeURIComponent(ROOM_CODE)}&lang=${encodeURIComponent(CURRENT_LANG)}#question-bank-section`;

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
    startButton.disabled = !roomQuestionsReady || !roomIsWaiting;
    startButton.title = roomQuestionsReady
        ? ""
        : LOBBY_I18N.incompleteRoomQuestions;
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
        document.getElementById("copy-link-btn").textContent = LOBBY_I18N.linkCopied + " ✅";
        setLobbyMessage(LOBBY_I18N.linkCopied, "success");
    } catch (error) {
        setLobbyMessage(LOBBY_I18N.error, "error");
        console.error(error);
    }
});

async function postRoomAction(endpoint) {
    const res = await fetch(`/colesterol_game/backend/rooms/${endpoint}`, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
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
        const res = await fetch(`/colesterol_game/backend/rooms/get_room_players.php?code=${encodeURIComponent(ROOM_CODE)}`);
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
        const res = await fetch(`/colesterol_game/backend/rooms/get_room_game_state.php?code=${encodeURIComponent(ROOM_CODE)}`);
        const state = await res.json();

        if (!state.success) return;

        roomIsWaiting = state.status === "waiting";
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

    return `/colesterol_game/pages/admin_questions.php?${params.toString()}#mass-generator`;
}

function renderRoomQuestionSetup(setup) {
    const summary = document.getElementById("room-questions-summary");
    const requirementsList = document.getElementById("room-requirements-list");
    const assignedContainer = document.getElementById("assigned-room-questions");

    roomQuestionsReady = Boolean(setup.ready);
    roomQuestionLanguage = setup.room?.language || roomQuestionLanguage;
    updateStartRoomAvailability();

    const summaryClass = setup.ready ? "is-success" : "is-warning";
    summary.className = `room-question-summary ${summaryClass}`;
    summary.innerHTML = `
        <span><strong>${LOBBY_I18N.requiredQuestions}:</strong> ${setup.required_total}</span>
        <span><strong>${LOBBY_I18N.readyQuestions}:</strong> ${setup.assigned_ready_total}</span>
        <span><strong>${LOBBY_I18N.missingQuestions}:</strong> ${setup.missing_total}</span>
        <em>${setup.ready ? LOBBY_I18N.readyToStart : LOBBY_I18N.incompleteRoomQuestions}</em>
    `;

    if (!Array.isArray(setup.requirements) || setup.requirements.length === 0) {
        requirementsList.innerHTML = "";
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    ${setup.requirements.map(requirement => {
                        const missing = Number(requirement.missing || 0);
                        return `
                            <tr>
                                <td>${requirement.category}</td>
                                <td>${requirement.difficulty} / 5</td>
                                <td>${requirement.quantity}</td>
                                <td>${requirement.assigned}</td>
                                <td>${requirement.available}</td>
                                <td>${missing}</td>
                                <td>
                                    ${missing > 0
                                        ? `<a class="table-btn edit-btn" href="${buildGeneratorUrl(requirement)}">${LOBBY_I18N.generateMissingForBlock}</a>`
                                        : ""}
                                </td>
                            </tr>
                        `;
                    }).join("")}
                </tbody>
            </table>
        `;
    }

    if (!Array.isArray(setup.assigned_questions) || setup.assigned_questions.length === 0) {
        assignedContainer.innerHTML = `<p>${LOBBY_I18N.noAssignedQuestions}</p>`;
        return;
    }

    assignedContainer.innerHTML = setup.assigned_questions.map(question => {
        const activeLabel = Number(question.is_active) === 1
            ? LOBBY_I18N.active
            : LOBBY_I18N.inactive;

        return `
            <details class="room-question-detail">
                <summary>
                    <span>#${question.id}</span>
                    <strong>${question.question}</strong>
                    <em>${formatQuestionStatus(question.status)} · ${activeLabel}</em>
                </summary>
                <div class="room-question-detail-body">
                    <p>
                        <strong>${LOBBY_I18N.category}:</strong> ${question.category}
                        · <strong>${LOBBY_I18N.difficulty}:</strong> ${Number(question.difficulty_level || 1).toFixed(1)} / 5
                        · <strong><?php echo t("correct_option"); ?>:</strong> ${question.correct_option}
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
}

async function loadRoomQuestionSetup() {
    try {
        const res = await fetch(`/colesterol_game/backend/rooms/get_room_question_setup.php?code=${encodeURIComponent(ROOM_CODE)}`);
        const setup = await res.json();

        if (!setup.success) {
            setLobbyMessage(setup.message || LOBBY_I18N.error, "error");
            return;
        }

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

async function removeRoomQuestion(questionId) {
    if (!confirm(LOBBY_I18N.confirmRemoveRoomQuestion)) {
        return;
    }

    try {
        const res = await fetch("/colesterol_game/backend/rooms/remove_room_question.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
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

document.getElementById("sync-room-questions-btn").addEventListener("click", syncRoomQuestions);

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
    if (!confirm(LOBBY_I18N.confirmFinishRoom)) return;

    try {
        const result = await postRoomAction("finish_room.php");
        if (result.success === false) {
            setLobbyMessage(result.message || LOBBY_I18N.error, "error");
            return;
        }

        setLobbyMessage(LOBBY_I18N.roomFinishedSuccess, "success");
        await loadRoomState();

        window.location.href =
            `/colesterol_game/pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}`;
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

</body>
</html>
