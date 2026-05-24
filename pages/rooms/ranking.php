<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../../config/db.php';

$role = $_SESSION["user_role"] ?? null;
$isAdmin = in_array($role, ["teacher", "super_admin"], true);

$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    header("Location: /colesterol_game/pages/rooms/join.php");
    exit;
}

$stmtRoom = $conn->prepare("
    SELECT id, name, status
    FROM game_rooms 
    WHERE room_code = ?
");

$stmtRoom->bind_param("s", $roomCode);
$stmtRoom->execute();

$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    die(current_lang() === "en" ? "Room not found" : "Sala no encontrada");
}

$room = $roomResult->fetch_assoc();
$stmtRoom->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("room_ranking"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=es">ES</a> |
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=en">EN</a>
        </div>
    </div>

    <h1><?php echo t("room_ranking"); ?></h1>

    <p>
        <strong><?php echo t("room_name"); ?>:</strong>
        <?php echo htmlspecialchars($room["name"]); ?><br>

        <strong><?php echo t("room_code"); ?>:</strong>
        <?php echo htmlspecialchars($roomCode); ?><br>

        <strong>Estado:</strong>
        <span id="room-status"><?php echo htmlspecialchars($room["status"]); ?></span>
    </p>

    <section id="podium-section" class="podium-section" style="display:none;">
        <h2><?php echo t("podium"); ?></h2>
        <div id="podium-container" class="podium-container"></div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("full_ranking"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo t("player_name"); ?></th>
                    <th><?php echo t("score"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("final_difficulty"); ?></th>
                </tr>
            </thead>

            <tbody id="ranking-body">
                <tr>
                    <td colspan="6"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <br>

    <?php if ($isAdmin): ?>

        <a href="/colesterol_game/pages/rooms/index.php"
           class="primary-btn"
           style="display:block; text-align:center; text-decoration:none;">
            <?php echo t("back"); ?>
        </a>

    <?php else: ?>

        <a href="/colesterol_game/pages/rooms/join.php"
           class="primary-btn"
           style="display:block; text-align:center; text-decoration:none;">
            <?php echo t("join_room"); ?>
        </a>

    <?php endif; ?>

</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const CURRENT_PLAYER = "<?php echo htmlspecialchars($_GET["name"] ?? ""); ?>";

let lastRankingJSON = "";
let rankingInterval = null;
let stateInterval = null;

const RANKING_I18N = {
    noResults: "<?php echo current_lang() === 'en' ? 'No results yet' : 'No hay resultados todavía'; ?>",
    error: "<?php echo t('error'); ?>"
};

function renderPodium(data) {
    const section = document.getElementById("podium-section");
    const container = document.getElementById("podium-container");

    if (!Array.isArray(data) || data.length === 0) {
        section.style.display = "none";
        return;
    }

    section.style.display = "block";
    container.innerHTML = "";

    const topThree = data.slice(0, 3);
    const medals = ["🥇", "🥈", "🥉"];
    const classes = ["first-place", "second-place", "third-place"];

    topThree.forEach((player, index) => {
        const card = document.createElement("div");
        card.classList.add("podium-card", classes[index]);

        if (CURRENT_PLAYER && player.player_name === CURRENT_PLAYER) {
            card.classList.add("current-player-rank");
        }

        card.innerHTML = `
            <div class="podium-medal">${medals[index]}</div>
            <h3>${player.player_name}</h3>
            <p>${player.best_score}</p>
            <small>
                ${player.best_correct} / ${player.total_questions}
                <br>
                ${player.precision}% • ${player.final_difficulty}/5
            </small>
        `;

        container.appendChild(card);
    });
}

function renderTable(data) {
    const tbody = document.getElementById("ranking-body");
    tbody.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML =
            `<tr><td colspan="6">${RANKING_I18N.noResults}</td></tr>`;
        return;
    }

    data.forEach((player, index) => {
        const row = document.createElement("tr");

        if (CURRENT_PLAYER && player.player_name === CURRENT_PLAYER) {
            row.style.background = "#d4edda";
            row.style.fontWeight = "bold";
        }

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${player.player_name}</td>
            <td>${player.best_score}</td>
            <td>${player.best_correct} / ${player.total_questions}</td>
            <td>${player.precision}%</td>
            <td>${player.final_difficulty} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

async function loadRoomRanking(force = false) {
    try {
        const res = await fetch(
            `/colesterol_game/backend/rooms/get_room_ranking.php?code=${encodeURIComponent(ROOM_CODE)}`
        );

        const data = await res.json();
        const currentJSON = JSON.stringify(data);

        if (!force && currentJSON === lastRankingJSON) {
            return;
        }

        lastRankingJSON = currentJSON;

        renderPodium(data);
        renderTable(data);

    } catch (error) {
        console.error(error);

        const tbody = document.getElementById("ranking-body");
        tbody.innerHTML =
            `<tr><td colspan="6">${RANKING_I18N.error}</td></tr>`;
    }
}

async function checkRoomStatus() {
    try {
        const res = await fetch(
            `/colesterol_game/backend/rooms/get_room_status.php?code=${encodeURIComponent(ROOM_CODE)}`
        );

        const state = await res.json();

        if (!state.success) {
            return;
        }

        const statusEl = document.getElementById("room-status");
        statusEl.textContent = state.status;

        if (state.status === "finished") {
            clearInterval(stateInterval);
            clearInterval(rankingInterval);
            await loadRoomRanking(true);
        }

    } catch (error) {
        console.error(error);
    }
}

loadRoomRanking(true);
checkRoomStatus();

rankingInterval = setInterval(() => loadRoomRanking(false), 3000);
stateInterval = setInterval(checkRoomStatus, 3000);
</script>

</body>
</html>