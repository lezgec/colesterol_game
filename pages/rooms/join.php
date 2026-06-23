<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../lang/translate.php';

$initialCode = strtoupper(trim($_GET["code"] ?? ""));
$styleVersion = filemtime(__DIR__ . '/../../assets/css/style.css');
$themeVersion = filemtime(__DIR__ . '/../../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("join_room"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?v=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">

</head>
<body>

<div class="game-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?code=<?php echo urlencode($initialCode); ?>&lang=es">ES</a>
            <span>|</span>
            <a href="?code=<?php echo urlencode($initialCode); ?>&lang=en">EN</a>
        </div>

        <?php
            $isLogged = isset($_SESSION["user_id"]);
            $role = $_SESSION["user_role"] ?? null;
            $isAdmin = in_array($role, ["teacher", "super_admin"], true);
            $backHref = $isLogged && !$isAdmin
                ? "/colesterol_game/pages/player_dashboard.php"
                : "/colesterol_game/pages/rooms/index.php";
            $backLabel = $isLogged && !$isAdmin
                ? t("back_to_player_dashboard")
                : t("back_to_rooms");
        ?>
    </div>

    <h1><?php echo t("join_room"); ?></h1>

    <form id="join-room-form">
        <div class="form-group">
            <label for="room_code"><?php echo t("room_code"); ?></label>
            <input
                type="text"
                id="room_code"
                value="<?php echo htmlspecialchars($initialCode); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="player_name"><?php echo t("player_name"); ?></label>
            <input type="text" id="player_name" required>
        </div>

        <button type="submit" class="primary-btn">
            <?php echo t("join_room"); ?>
        </button>

        <p id="join-message" class="room-status-message" aria-live="polite"></p>
    </form>

    <a href="<?php echo htmlspecialchars($backHref); ?>" class="secondary-link room-back-link">
        <?php echo htmlspecialchars($backLabel); ?>
    </a>

</div>

<script>
document.getElementById("join-room-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const code = document.getElementById("room_code").value.trim().toUpperCase();
    const name = document.getElementById("player_name").value.trim();
    const message = document.getElementById("join-message");

    function setJoinMessage(text, type = "") {
        message.textContent = text;
        message.classList.remove("is-info", "is-success", "is-error", "is-warning");

        if (type) {
            message.classList.add(`is-${type}`);
        }
    }

    setJoinMessage("<?php echo t('loading'); ?>", "info");

    try {
        const res = await fetch("/colesterol_game/backend/rooms/join_room_player.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                room_code: code,
                player_name: name
            })
        });

        const result = await res.json();

        if (result.success) {
            setJoinMessage("<?php echo current_lang() === 'en' ? 'Joining room...' : 'Entrando a la sala...'; ?>", "success");
            window.location.href =
                `/colesterol_game/pages/rooms/waiting.php?code=${encodeURIComponent(code)}&name=${encodeURIComponent(name)}`;
        } else {
            setJoinMessage(result.message || "<?php echo t('error'); ?>", "error");
        }
    } catch (error) {
        console.error(error);
        setJoinMessage("<?php echo t('error'); ?>", "error");
    }
});
</script>

<script src="/colesterol_game/assets/js/theme.js?v=<?php echo $themeVersion; ?>"></script>
</body>
</html>
