<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

function current_user_role() {
    return $_SESSION["user_role"] ?? "guest";
}

function is_player() {
    return current_user_role() === "player";
}

function is_teacher() {
    return current_user_role() === "teacher";
}

function is_super_admin() {
    return current_user_role() === "super_admin";
}

function has_role($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return is_logged_in() && in_array(current_user_role(), $roles, true);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /colesterol_game/pages/login.php");
        exit;
    }
}

function require_role($roles) {
    if (!has_role($roles)) {
        header("Location: /colesterol_game/pages/login.php");
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
        return "/colesterol_game/pages/admin_dashboard.php";
    }

    if ($role === "teacher") {
        return "/colesterol_game/pages/admin_dashboard.php";
    }

    if ($role === "player") {
        return "/colesterol_game/pages/player_dashboard.php";
    }

    return "/colesterol_game/index.php";
}
?>