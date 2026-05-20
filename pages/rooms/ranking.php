<?php
require_once __DIR__ . '/../../lang/translate.php';
require_once __DIR__ . '/../../config/db.php';


$roomCode = strtoupper(trim($_GET["code"] ?? ""));

if ($roomCode === "") {
    header("Location: /colesterol_game/pages/rooms/index.php");
    exit;
}

$stmtRoom = $conn->prepare("SELECT id, name FROM game_rooms WHERE room_code = ?");
$stmtRoom->bind_param("s", $roomCode);
$stmtRoom->execute();
$roomResult = $stmtRoom->get_result();

if ($roomResult->num_rows === 0) {
    die(current_lang() === "en" ? "Room not found" : "Sala no encontrada");
}

$room = $roomResult->fetch_assoc();
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
        <div class="language-switch">
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=es">ES</a> |
            <a href="?code=<?php echo urlencode($roomCode); ?>&lang=en">EN</a>
        </div>
    </div>

    <h1><?php echo t("room_ranking"); ?></h1>

    <p>
        <strong><?php echo t("room_name"); ?>:</strong>
        <?php echo htmlspecialchars($room["name"]); ?><br>

        <strong><?php echo t("room_code"); ?>:</strong>
        <?php echo htmlspecialchars($roomCode); ?>
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
                </tr>
            </thead>

            <tbody id="ranking-body">
                <tr>
                    <td colspan="4"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <br>

    <a href="/colesterol_game/pages/rooms/join.php" class="primary-btn" style="display:block; text-align:center; text-decoration:none;">
        <?php echo t("join_room"); ?>
    </a>

    <br>

    <a href="/colesterol_game/pages/rooms/index.php">
        <?php echo t("back"); ?>
    </a>

</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const CURRENT_PLAYER = "<?php echo htmlspecialchars($_GET["name"] ?? ""); ?>";

const RANKING_I18N = {
    noResults: "<?php echo current_lang() === 'en' ? 'No results yet' : 'No hay resultados todavía'; ?>",
    error: "<?php echo t('error'); ?>",
    correctAnswers: "<?php echo t('correct_answers'); ?>"
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
            <small>${player.best_correct} / ${player.total_questions}</small>
        `;

        container.appendChild(card);
    });
}

async function loadRoomRanking() {
    try {
        const res = await fetch(`/colesterol_game/backend/get_room_ranking.php?code=${encodeURIComponent(ROOM_CODE)}`);
        const data = await res.json();

        const tbody = document.getElementById("ranking-body");
        tbody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4">${RANKING_I18N.noResults}</td></tr>`;
            renderPodium([]);
            return;
        }

        renderPodium(data);

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
            `;

            tbody.appendChild(row);
        });

    } catch (error) {
        console.error(error);
        const tbody = document.getElementById("ranking-body");
        tbody.innerHTML = `<tr><td colspan="4">${RANKING_I18N.error}</td></tr>`;
    }
}

loadRoomRanking();
setInterval(loadRoomRanking, 2000);
</script>

</body>
</html>