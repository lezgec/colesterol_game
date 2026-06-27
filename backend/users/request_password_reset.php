<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/rate_limit.php";
require_once __DIR__ . "/password_reset_helpers.php";

require_csrf_token();

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    $data = [];
}

$email = strtolower(trim($data["email"] ?? ""));
require_rate_limit($conn, "password-reset:" . $email, 4, 3600);
$genericMessage = "Si el correo está registrado, enviaremos instrucciones para recuperar la contraseña.";

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse([
        "success" => false,
        "message" => "Ingresa un correo electrónico válido."
    ]);
}

if (!ensure_password_resets_table($conn)) {
    jsonResponse([
        "success" => false,
        "message" => "No se pudo preparar la recuperación de contraseña.",
        "error" => $conn->error
    ]);
}

$stmt = $conn->prepare("
    SELECT id, name, email
    FROM users
    WHERE email = ?
      AND COALESCE(status, 'active') = 'active'
    LIMIT 1
");

if (!$stmt) {
    jsonResponse([
        "success" => false,
        "message" => "Error al preparar la consulta.",
        "error" => $conn->error
    ]);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    jsonResponse([
        "success" => true,
        "message" => $genericMessage
    ]);
}

$userId = (int)$user["id"];
$token = bin2hex(random_bytes(32));
$tokenHash = hash("sha256", $token);
$expiresAt = date("Y-m-d H:i:s", time() + 1800);

$stmt = $conn->prepare("
    UPDATE password_resets
    SET used_at = NOW()
    WHERE user_id = ?
      AND used_at IS NULL
");

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

$stmt = $conn->prepare("
    INSERT INTO password_resets (user_id, token_hash, expires_at)
    VALUES (?, ?, ?)
");

if (!$stmt) {
    jsonResponse([
        "success" => false,
        "message" => "Error al crear el token.",
        "error" => $conn->error
    ]);
}

$stmt->bind_param("iss", $userId, $tokenHash, $expiresAt);

if (!$stmt->execute()) {
    jsonResponse([
        "success" => false,
        "message" => "No se pudo crear la solicitud de recuperación.",
        "error" => $stmt->error
    ]);
}

$stmt->close();

$mailConfig = load_mail_config();
$appUrl = rtrim($mailConfig["app_url"] ?? app_url_base(), "/");
$resetLink = $appUrl . "/pages/reset_password.php?token=" . urlencode($token);

try {
    send_password_reset_email($user["email"], $user["name"], $resetLink);
} catch (RuntimeException $exception) {
    jsonResponse([
        "success" => false,
        "message" => "La solicitud fue creada, pero el correo no pudo enviarse.",
        "error" => $exception->getMessage()
    ]);
}

$conn->close();

jsonResponse([
    "success" => true,
    "message" => $genericMessage
]);
