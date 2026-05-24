<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../lang/translate.php';

$initialCode = strtoupper(trim($_GET["code"] ?? ""));
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("join_room"); ?></title>

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
            <a href="?code=<?php echo urlencode($initialCode); ?>&lang=es">ES</a> |
            <a href="?code=<?php echo urlencode($initialCode); ?>&lang=en">EN</a>
        </div>

        <?php
            $role = $_SESSION["user_role"] ?? null;
            $isAdmin = in_array($role, ["teacher", "super_admin"], true);
            ?>

            <?php if ($isAdmin): ?>

                <a href="/colesterol_game/pages/rooms/index.php" class="logout-btn secondary-btn">
                    <?php echo t("back"); ?>
                </a>

            <?php else: ?>

                <a href="/colesterol_game/index.php" class="logout-btn secondary-btn">
                    <?php echo t("back"); ?>
                </a>

        <?php endif; ?>
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

        <p id="join-message"></p>
    </form>

</div>

<script>
document.getElementById("join-room-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const code = document.getElementById("room_code").value.trim().toUpperCase();
    const name = document.getElementById("player_name").value.trim();
    const message = document.getElementById("join-message");

    message.textContent = "<?php echo t('loading'); ?>";

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
            window.location.href =
                `/colesterol_game/pages/rooms/waiting.php?code=${encodeURIComponent(code)}&name=${encodeURIComponent(name)}`;
        } else {
            message.textContent = result.message || "<?php echo t('error'); ?>";
        }
    } catch (error) {
        console.error(error);
        message.textContent = "<?php echo t('error'); ?>";
    }
});
</script>

</body>
</html>