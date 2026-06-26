<?php
require_once __DIR__ . '/../../lang/translate.php';

$roomCode = strtoupper(trim($_GET["code"] ?? ""));
$playerName = trim($_GET["name"] ?? "");

if ($roomCode === "" || $playerName === "") {
    header("Location: " . app_path("pages/rooms/join.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo current_lang() === "en"
            ? "Waiting room"
            : "Sala de espera"; ?>
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&name=<?php echo urlencode($playerName); ?>&lang=es">ES</a>
            <span>|</span>
            <a href="?code=<?php echo urlencode($roomCode); ?>&name=<?php echo urlencode($playerName); ?>&lang=en">EN</a>
        </div>

        <a href="<?php echo app_path('pages/rooms/index.php'); ?>"
           class="logout-btn secondary-btn">
            <?php echo t("back_to_rooms"); ?>
        </a>
    </div>

    <h1>
        <?php echo current_lang() === "en"
            ? "Waiting for the game..."
            : "Esperando partida..."; ?>
    </h1>

    <p>
        <strong><?php echo t("room_code"); ?>:</strong>
        <?php echo htmlspecialchars($roomCode); ?>
        <br>

        <strong><?php echo t("player_name"); ?>:</strong>
        <?php echo htmlspecialchars($playerName); ?>
    </p>

    <div class="admin-section">
        <h2>
            <?php echo current_lang() === "en"
                ? "Connected players"
                : "Jugadores conectados"; ?>
        </h2>

        <ul id="players-list"></ul>
    </div>

    <p id="waiting-message" class="room-status-message is-info">
        <?php echo current_lang() === "en"
            ? "The teacher will start the match soon."
            : "El docente iniciará la partida pronto."; ?>
    </p>

    <div class="loader-text room-loader">⏳</div>

</div>

<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const PLAYER_NAME = "<?php echo htmlspecialchars($playerName); ?>";
let playersSignature = "";

async function loadPlayers() {
    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_room_players.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );

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
            list.innerHTML = `
                <li class="player-pill is-empty">
                    <?php echo current_lang() === "en"
                        ? "No players connected yet"
                        : "No hay jugadores conectados"; ?>
                </li>
            `;
            return;
        }

        players.forEach((player, index) => {
            const li = document.createElement("li");
            li.classList.add("player-pill");
            li.style.animationDelay = `${Math.min(index * 60, 360)}ms`;
            const name = player.player_name || "Player";
            const initial = name.trim().charAt(0).toUpperCase() || "?";

            if (name === PLAYER_NAME) {
                li.classList.add("is-current");
            }

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

async function checkRoomStatus() {
    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_room_status.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );

        const result = await res.json();

        if (
            result.success &&
            result.status === "started"
        ) {
            window.location.href =
                appUrl(`pages/rooms/play.php?code=${encodeURIComponent(ROOM_CODE)}&name=${encodeURIComponent(PLAYER_NAME)}`);
        }

    } catch (error) {
        console.error(error);
    }
}

loadPlayers();
checkRoomStatus();

setInterval(loadPlayers, 2000);
setInterval(checkRoomStatus, 2000);
</script>

<script src="<?php echo asset_path('js/theme.js'); ?>"></script>
</body>
</html>
