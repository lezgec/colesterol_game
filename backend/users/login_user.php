<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Campos obligatorios"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
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
    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["password"])) {
    echo json_encode([
        "success" => false,
        "message" => "Contraseña incorrecta"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = $user["role"] ?? "player";

if (!in_array($role, ["player", "teacher", "super_admin"], true)) {
    $role = "player";
}

$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["user_role"] = $role;

echo json_encode([
    "success" => true,
    "message" => "Inicio de sesión correcto",
    "role" => $role,
    "redirect" => redirect_after_login_by_role($role)
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
?>