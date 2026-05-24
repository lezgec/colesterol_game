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
    <title><?php echo t("room_lobby"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=es">ES</a> |
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

    <section class="admin-section">
        <h2>Control de sala</h2>

        <p>
            <strong>Estado:</strong>
            <span id="room-status">...</span>
        </p>

        <p>
            <strong>Pregunta actual:</strong>
            <span id="current-question">...</span>
        </p>

        <p>
            <strong>Tiempo restante:</strong>
            <span id="time-left">...</span>
        </p>

        <div class="admin-room-controls">
            <button id="start-room-btn" class="primary-btn" type="button">
                <?php echo t("start_game"); ?>
            </button>

            <button id="pause-room-btn" class="primary-btn secondary-btn" type="button">
                Pausar
            </button>

            <button id="resume-room-btn" class="primary-btn secondary-btn" type="button">
                Reanudar
            </button>

            <button id="next-room-btn" class="primary-btn secondary-btn" type="button">
                Siguiente pregunta
            </button>

            <button id="finish-room-btn" class="logout-btn" type="button">
                Finalizar sala
            </button>
        </div>
    </section>

    <p id="admin-lobby-message"></p>
</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";

const LOBBY_I18N = {
    noPlayersYet: "<?php echo t('no_players_yet'); ?>",
    gameStarted: "<?php echo t('game_started'); ?>",
    linkCopied: "<?php echo t('link_copied'); ?>",
    loading: "<?php echo t('loading'); ?>",
    error: "<?php echo t('error'); ?>"
};

const joinLink = `${window.location.origin}/colesterol_game/pages/rooms/join.php?code=${encodeURIComponent(ROOM_CODE)}`;

document.getElementById("room-share-link").value = joinLink;

document.getElementById("copy-link-btn").addEventListener("click", async () => {
    try {
        await navigator.clipboard.writeText(joinLink);
        document.getElementById("copy-link-btn").textContent = LOBBY_I18N.linkCopied + " ✅";
    } catch (error) {
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.error;
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
        list.innerHTML = "";

        if (!Array.isArray(players) || players.length === 0) {
            list.innerHTML = `<li>${LOBBY_I18N.noPlayersYet}</li>`;
            return;
        }

        players.forEach(player => {
            const li = document.createElement("li");
            li.textContent = player.player_name;
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

        document.getElementById("room-status").textContent = state.status;
        document.getElementById("current-question").textContent =
            `${state.current_question_index + 1} / ${state.question_count}`;
        document.getElementById("time-left").textContent =
            `${state.time_left}s`;

    } catch (error) {
        console.error(error);
    }
}

document.getElementById("start-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("start_room.php");

        document.getElementById("admin-lobby-message").textContent =
            result.message || LOBBY_I18N.gameStarted;

        await loadRoomState();
    } catch (error) {
        console.error(error);
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.error;
    }
});

document.getElementById("pause-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("pause_room.php");
        document.getElementById("admin-lobby-message").textContent = result.message || "Sala pausada";
        await loadRoomState();
    } catch (error) {
        console.error(error);
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.error;
    }
});

document.getElementById("resume-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("resume_room.php");
        document.getElementById("admin-lobby-message").textContent = result.message || "Sala reanudada";
        await loadRoomState();
    } catch (error) {
        console.error(error);
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.error;
    }
});

document.getElementById("next-room-btn").addEventListener("click", async () => {
    try {
        const result = await postRoomAction("next_room_question.php");
        document.getElementById("admin-lobby-message").textContent = result.message || "Pregunta avanzada";
        await loadRoomState();
    } catch (error) {
        console.error(error);
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.error;
    }
});

document.getElementById("finish-room-btn").addEventListener("click", async () => {
    if (!confirm("¿Seguro que deseas finalizar la sala?")) return;

    try {
        const result = await postRoomAction("finish_room.php");
        document.getElementById("admin-lobby-message").textContent = result.message || "Sala finalizada";
        await loadRoomState();

        window.location.href =
            `/colesterol_game/pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}`;
    } catch (error) {
        console.error(error);
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.error;
    }
});

loadPlayers();
loadRoomState();

setInterval(loadPlayers, 2000);
setInterval(loadRoomState, 1000);
</script>

</body>
</html>