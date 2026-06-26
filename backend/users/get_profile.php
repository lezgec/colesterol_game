<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/profile_helpers.php';

require_login();
ensure_user_profile_columns($conn);

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$userId = is_super_admin() && $requestedUserId > 0
    ? $requestedUserId
    : (int)$_SESSION["user_id"];
$stmt = $conn->prepare("
    SELECT id, name, email, role, avatar_key, custom_avatar_path, country, city, institution, occupation, age, career, education_level, bio
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Usuario no encontrado"], JSON_UNESCAPED_UNICODE);
    exit;
}

$user["avatar_key"] = normalize_avatar_key($user["avatar_key"] ?? "");
$user["country"] = normalize_country_code($user["country"] ?? "");
$user["country_display"] = country_display($user["country"], $_SESSION["lang"] ?? "es");
$user["avatar"] = profile_avatar_payload($user);

echo json_encode([
    "success" => true,
    "user" => $user,
    "avatars" => profile_avatar_options(),
    "countries" => app_countries()
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
