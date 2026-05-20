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
    <title>Esperando partida</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container">
    <h1>Esperando partida...</h1>

    <p>
        <strong><?php echo t("room_code"); ?>:</strong> <?php echo htmlspecialchars($roomCode); ?><br>
        <strong><?php echo t("player_name"); ?>:</strong> <?php echo htmlspecialchars($playerName); ?>
    </p>

    <p>El administrador iniciará la partida pronto.</p>

    <div class="loader-text">⏳</div>
</div>

<script>
const ROOM_CODE = "<?php echo htmlspecialchars($roomCode); ?>";
const PLAYER_NAME = "<?php echo htmlspecialchars($playerName); ?>";

async function checkRoomStatus() {
    const res = await fetch(`/colesterol_game/backend/get_room_status.php?code=${ROOM_CODE}`);
    const result = await res.json();

    if (result.success && result.status === "started") {
        window.location.href = `/colesterol_game/pages/rooms/play.php?code=${encodeURIComponent(ROOM_CODE)}&name=${encodeURIComponent(PLAYER_NAME)}`;
    }
}

setInterval(checkRoomStatus, 2000);
checkRoomStatus();
</script>

</body>
</html>