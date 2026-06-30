<?php

require_once __DIR__ . '/../app/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    $cookieSecure = env_bool(
        "SESSION_COOKIE_SECURE",
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
    );

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => app_base_path() ?: "/",
        "domain" => "",
        "secure" => $cookieSecure,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

function destroy_local_session() {
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
}

function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

function current_session_is_active() {
    static $isActive = null;
    global $conn;

    if (!is_logged_in()) {
        return false;
    }

    if ($isActive !== null) {
        return $isActive;
    }

    if (!isset($_SESSION["session_token"])) {
        if (!isset($conn) || !($conn instanceof mysqli)) {
            require __DIR__ . '/../config/db.php';
        }

        require_once __DIR__ . '/../backend/users/session_guard.php';

        $userId = (int)$_SESSION["user_id"];
        $sessionToken = create_user_session_token();

        if (store_user_session_token($conn, $userId, $sessionToken)) {
            $_SESSION["session_token"] = $sessionToken;
            $isActive = true;
            return true;
        }

        $isActive = false;
        destroy_local_session();
        return false;
    }

    if (!isset($conn) || !($conn instanceof mysqli)) {
        require __DIR__ . '/../config/db.php';
    }

    require_once __DIR__ . '/../backend/users/session_guard.php';

    $userId = (int)$_SESSION["user_id"];
    $sessionToken = (string)$_SESSION["session_token"];
    $isActive = active_session_matches($conn, $userId, $sessionToken);

    if (!$isActive) {
        destroy_local_session();
    }

    return $isActive;
}

function current_user_role() {
    return $_SESSION["user_role"] ?? "guest";
}

function current_user_id() {
    return (int)($_SESSION["user_id"] ?? 0);
}

function is_player() {
    return current_session_is_active() && current_user_role() === "player";
}

function is_teacher() {
    return current_session_is_active() && current_user_role() === "teacher";
}

function is_super_admin() {
    return current_session_is_active() && current_user_role() === "super_admin";
}

function has_role($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return current_session_is_active() && in_array(current_user_role(), $roles, true);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . app_base_path() . "/pages/login.php");
        exit;
    }

    $hadSessionToken = isset($_SESSION["session_token"]);

    if (!current_session_is_active()) {
        $reason = $hadSessionToken ? "replaced" : "expired";
        header("Location: " . app_base_path() . "/pages/login.php?session={$reason}");
        exit;
    }
}

function require_role($roles) {
    require_login();

    if (!has_role($roles)) {
        header("Location: " . app_base_path() . "/pages/login.php");
        exit;
    }
}

function can_manage_rooms() {
    return has_role(["teacher", "super_admin"]);
}

function can_manage_questions() {
    return has_role(["teacher", "super_admin"]);
}

function can_view_global_stats() {
    return has_role("super_admin");
}

function can_manage_users() {
    return has_role("super_admin");
}

function redirect_after_login_by_role($role) {
    if ($role === "super_admin") {
        return app_base_path() . "/pages/admin_dashboard.php";
    }

    if ($role === "teacher") {
        return app_base_path() . "/pages/admin_dashboard.php";
    }

    if ($role === "player") {
        return app_base_path() . "/pages/player_dashboard.php";
    }

    return app_base_path() . "/index.php";
}
