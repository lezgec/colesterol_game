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
    header("Location: " . app_path("pages/rooms/join.php"));
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("room_ranking"); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>">
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
    </div>

    <h1><?php echo t("room_ranking"); ?></h1>

    <p class="room-meta">
        <strong><?php echo t("room_name"); ?>:</strong>
        <?php echo htmlspecialchars($room["name"]); ?><br>

        <strong><?php echo t("room_code"); ?>:</strong>
        <?php echo htmlspecialchars($roomCode); ?><br>

        <strong><?php echo t("status"); ?>:</strong>
        <span id="room-status"><?php echo htmlspecialchars(room_status_label($room["status"])); ?></span>
    </p>

    <section id="podium-section" class="podium-section" style="display:none;">
        <h2><?php echo t("podium"); ?></h2>
        <div id="podium-container" class="podium-container"></div>
    </section>

    <section class="admin-section">
        <h2><?php echo t("full_ranking"); ?></h2>

        <table class="admin-table ranking-table">
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

        <a href="<?php echo app_path('pages/rooms/index.php'); ?>"
           class="primary-btn"
           style="display:block; text-align:center; text-decoration:none;">
            <?php echo t("back_to_rooms"); ?>
        </a>
        <a href="<?php echo app_path('pages/rooms/room_report.php?code=' . urlencode($roomCode)); ?>"
        class="primary-btn"
        style="display:block; text-align:center; text-decoration:none; margin-bottom:10px;">
            <?php echo t("room_report"); ?>
        </a>

    <?php else: ?>

        <a href="<?php echo app_path('pages/rooms/join.php'); ?>"
           class="primary-btn"
           style="display:block; text-align:center; text-decoration:none;">
            <?php echo t("join_room"); ?>
        </a>

    <?php endif; ?>

</div>

<script src="<?php echo asset_path('js/ui_icons.js'); ?>"></script>
<script>
const APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const CURRENT_PLAYER = "<?php echo htmlspecialchars($_GET["name"] ?? ""); ?>";
const escapeHtml = value => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

let lastRankingJSON = "";
let rankingInterval = null;
let stateInterval = null;

const RANKING_I18N = {
    noResults: "<?php echo t('no_results_yet'); ?>",
    error: "<?php echo t('error'); ?>",
    unknownStatus: "<?php echo t('room_status_unknown'); ?>",
    statuses: {
        waiting: "<?php echo t('room_status_waiting'); ?>",
        started: "<?php echo t('room_status_started'); ?>",
        paused: "<?php echo t('room_status_paused'); ?>",
        finished: "<?php echo t('room_status_finished'); ?>"
    }
};

function formatRoomStatus(status) {
    return RANKING_I18N.statuses[status] || RANKING_I18N.unknownStatus;
}

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
    const classes = ["first-place", "second-place", "third-place"];

    topThree.forEach((player, index) => {
        const card = document.createElement("div");
        card.classList.add("podium-card", classes[index]);

        if (CURRENT_PLAYER && player.player_name === CURRENT_PLAYER) {
            card.classList.add("current-player-rank");
        }

        card.innerHTML = `
            <div class="podium-medal">${window.uiIcon ? window.uiIcon("medal", `ui-icon podium-medal-svg podium-medal-${index + 1}`) : index + 1}</div>
            <h3>${escapeHtml(player.player_name)}</h3>
            <p>${escapeHtml(player.best_score)}</p>
            <small>
                ${escapeHtml(player.best_correct)} / ${escapeHtml(player.total_questions)}
                <br>
                ${escapeHtml(player.precision)}% - ${escapeHtml(player.final_difficulty)}/5
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
            row.classList.add("current-player-rank");
        }

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${escapeHtml(player.player_name)}</td>
            <td>${escapeHtml(player.best_score)}</td>
            <td>${escapeHtml(player.best_correct)} / ${escapeHtml(player.total_questions)}</td>
            <td>${escapeHtml(player.precision)}%</td>
            <td>${escapeHtml(player.final_difficulty)} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

async function loadRoomRanking(force = false) {
    try {
        const res = await fetch(
            appUrl(`backend/rooms/get_room_ranking.php?code=${encodeURIComponent(ROOM_CODE)}`)
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
            appUrl(`backend/rooms/get_room_status.php?code=${encodeURIComponent(ROOM_CODE)}`)
        );

        const state = await res.json();

        if (!state.success) {
            return;
        }

        const statusEl = document.getElementById("room-status");
        statusEl.textContent = formatRoomStatus(state.status);

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


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>"></script>
</body>
</html>
