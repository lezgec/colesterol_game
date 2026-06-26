<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/password_reset_helpers.php";
require_once __DIR__ . "/password_policy.php";
require_once __DIR__ . "/session_guard.php";

require_csrf_token();

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    $data = [];
}

$token = trim($data["token"] ?? "");
$password = $data["password"] ?? "";
$passwordConfirm = $data["password_confirm"] ?? "";

if ($token === "" || strlen($token) < 64) {
    jsonResponse([
        "success" => false,
        "message" => "El enlace de recuperación no es válido."
    ]);
}

$passwordErrors = validate_password_policy($password);

if (!empty($passwordErrors)) {
    jsonResponse([
        "success" => false,
        "message" => password_policy_message()
    ]);
}

if ($password !== $passwordConfirm) {
    jsonResponse([
        "success" => false,
        "message" => "Las contraseñas no coinciden."
    ]);
}

if (!ensure_password_resets_table($conn)) {
    jsonResponse([
        "success" => false,
        "message" => "No se pudo preparar la recuperación de contraseña.",
        "error" => $conn->error
    ]);
}

if (!ensure_user_session_columns($conn)) {
    jsonResponse([
        "success" => false,
        "message" => "No se pudo preparar la seguridad de sesión.",
        "error" => $conn->error
    ]);
}

$tokenHash = hash("sha256", $token);

$stmt = $conn->prepare("
    SELECT pr.id, pr.user_id
    FROM password_resets pr
    INNER JOIN users u ON u.id = pr.user_id
    WHERE pr.token_hash = ?
      AND pr.used_at IS NULL
      AND pr.expires_at > NOW()
      AND COALESCE(u.status, 'active') = 'active'
    LIMIT 1
");

if (!$stmt) {
    jsonResponse([
        "success" => false,
        "message" => "Error al validar el token.",
        "error" => $conn->error
    ]);
}

$stmt->bind_param("s", $tokenHash);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();
$stmt->close();

if (!$reset) {
    jsonResponse([
        "success" => false,
        "message" => "El enlace expiró o ya fue usado."
    ]);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        UPDATE users
        SET password = ?,
            session_token = NULL,
            session_updated_at = NULL
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    $userId = (int)$reset["user_id"];
    $stmt->bind_param("si", $hash, $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");

    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    $resetId = (int)$reset["id"];
    $stmt->bind_param("i", $resetId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();

    jsonResponse([
        "success" => false,
        "message" => "No se pudo actualizar la contraseña.",
        "error" => $exception->getMessage()
    ]);
}

$conn->close();

jsonResponse([
    "success" => true,
    "message" => "Contraseña actualizada correctamente. Ya puedes iniciar sesión."
]);
