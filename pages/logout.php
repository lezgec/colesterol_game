<?php

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../backend/users/session_guard.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$userId = (int)($_SESSION["user_id"] ?? 0);
$sessionToken = $_SESSION["session_token"] ?? null;

if ($userId > 0 && $sessionToken !== null) {
    clear_user_session_token($conn, $userId, $sessionToken);
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: /colesterol_game/pages/login.php?logout=1");
exit;

?>
