<?php
session_start();

// borrar variables de sesión
$_SESSION = [];

// destruir sesión
session_destroy();

// eliminar cookie de sesión (importante)
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

// redirigir con mensaje
header("Location: /colesterol_game/pages/register.php?logout=1");
exit;
?>