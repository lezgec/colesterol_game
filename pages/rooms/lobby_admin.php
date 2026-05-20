<?php
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../assets/includes/auth.php';
require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: /colesterol_game/pages/login.php");
    exit;
}

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
        <div class="language-switch">
            <a href="?lang=es">ES</a> |
            <a href="?lang=en">EN</a>
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

    <button id="start-room-btn" class="primary-btn" type="button">
        <?php echo t("start_game"); ?>
    </button>

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

async function loadPlayers() {
    try {
        const res = await fetch(`/colesterol_game/backend/get_room_players.php?code=${encodeURIComponent(ROOM_CODE)}`);
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

document.getElementById("start-room-btn").addEventListener("click", async () => {
    const res = await fetch("/colesterol_game/backend/start_room.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ room_code: ROOM_CODE })
    });

    const result = await res.json();

    if (result.success) {
        document.getElementById("admin-lobby-message").textContent = LOBBY_I18N.gameStarted + " ✅";
        window.location.href = `/colesterol_game/pages/rooms/ranking.php?code=${encodeURIComponent(ROOM_CODE)}`;
    } else {
        document.getElementById("admin-lobby-message").textContent = result.message || LOBBY_I18N.error;
    }
});

loadPlayers();
setInterval(loadPlayers, 2000);
</script>

</body>
</html>