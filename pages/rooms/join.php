<?php require_once __DIR__ . '/../../lang/translate.php';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("join_room"); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>
<div class="game-container">
    <div style="text-align:right;">
        <a href="?lang=es">ES</a> | <a href="?lang=en">EN</a>
    </div>

    <h1><?php echo t("join_room"); ?></h1>

    <form id="join-room-form">
        <div class="form-group">
            <label><?php echo t("room_code"); ?></label>
            <input type="text" id="room_code" value="<?php echo htmlspecialchars($_GET['code'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label><?php echo t("player_name"); ?></label>
            <input type="text" id="player_name" required>
        </div>

        <button type="submit" class="primary-btn"><?php echo t("join_room"); ?></button>
    </form>

    <a href="/colesterol_game/pages/rooms/index.php"><?php echo t("back"); ?></a>
</div>

<script>
document.getElementById("join-room-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const code = document.getElementById("room_code").value.trim().toUpperCase();
    const name = document.getElementById("player_name").value.trim();

    const res = await fetch("/colesterol_game/backend/join_room_player.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            room_code: code,
            player_name: name
        })
    });

    const result = await res.json();

    if (result.success) {
        window.location.href = `/colesterol_game/pages/rooms/waiting.php?code=${encodeURIComponent(code)}&name=${encodeURIComponent(name)}`;
    } else {
        alert(result.message);
    }
});
</script>
</body>
</html>