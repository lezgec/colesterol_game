<?php
require_once __DIR__ . '/../../lang/translate.php';

$roomCode = strtoupper(trim($_GET["code"] ?? ""));
$playerName = trim($_GET["name"] ?? "");

if ($roomCode === "" || $playerName === "") {
    header("Location: /colesterol_game/pages/rooms/join.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">

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

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&name=<?php echo urlencode($playerName); ?>&lang=es">ES</a> |
            <a href="?code=<?php echo urlencode($roomCode); ?>&name=<?php echo urlencode($playerName); ?>&lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/rooms/index.php"
           class="logout-btn secondary-btn">
            <?php echo t("back"); ?>
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

    <p id="waiting-message">
        <?php echo current_lang() === "en"
            ? "The teacher will start the match soon."
            : "El docente iniciará la partida pronto."; ?>
    </p>

    <div class="loader-text">⏳</div>

</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const PLAYER_NAME = "<?php echo htmlspecialchars($playerName); ?>";

async function loadPlayers() {
    try {
        const res = await fetch(
            `/colesterol_game/backend/rooms/get_room_players.php?code=${encodeURIComponent(ROOM_CODE)}`
        );

        const players = await res.json();

        const list = document.getElementById("players-list");

        list.innerHTML = "";

        if (!Array.isArray(players) || players.length === 0) {
            list.innerHTML = `
                <li>
                    <?php echo current_lang() === "en"
                        ? "No players connected yet"
                        : "No hay jugadores conectados"; ?>
                </li>
            `;
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

async function checkRoomStatus() {
    try {
        const res = await fetch(
            `/colesterol_game/backend/rooms/get_room_status.php?code=${encodeURIComponent(ROOM_CODE)}`
        );

        const result = await res.json();

        if (
            result.success &&
            result.status === "started"
        ) {
            window.location.href =
                `/colesterol_game/pages/rooms/play.php?code=${encodeURIComponent(ROOM_CODE)}&name=${encodeURIComponent(PLAYER_NAME)}`;
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

</body>
</html>