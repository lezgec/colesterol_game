<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limit.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../../includes/mail_helpers.php';

require_csrf_token();

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$invalidLoginMessage = "Correo o contraseña incorrectos";

require_rate_limit($conn, "login:" . strtolower($email), 8, 900);

if ($email === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Campos obligatorios"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT id, name, email, password, role, status FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar consulta",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Login failed: user not found for " . strtolower($email));
    echo json_encode([
        "success" => false,
        "message" => $invalidLoginMessage
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["password"])) {
    error_log("Login failed: invalid password for user " . (int)$user["id"]);
    echo json_encode([
        "success" => false,
        "message" => $invalidLoginMessage
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = $user["role"] ?? "player";

if (!in_array($role, ["player", "teacher", "super_admin"], true)) {
    $role = "player";
}

if (($user["status"] ?? "active") !== "active") {
    echo json_encode([
        "success" => false,
        "message" => "Tu usuario está bloqueado. Contacta a soporte: " . app_support_email(),
        "code" => "user_inactive",
        "support_email" => app_support_email()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionToken = create_user_session_token();

if (!store_user_session_token($conn, (int)$user["id"], $sessionToken)) {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo iniciar la sesión segura"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

session_regenerate_id(true);

$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["user_role"] = $role;
$_SESSION["session_token"] = $sessionToken;

echo json_encode([
    "success" => true,
    "message" => "Inicio de sesión correcto",
    "role" => $role,
    "redirect" => redirect_after_login_by_role($role)
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>
