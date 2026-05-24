<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibieron datos válidos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$role = "player";

if ($name === "" || $email === "" || $password === "") {
    echo json_encode([
        "success" => false,
        "message" => "Todos los campos son obligatorios"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Correo electrónico no válido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "La contraseña debe tener al menos 6 caracteres"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkSql = "SELECT id FROM users WHERE email = ?";
$checkStmt = $conn->prepare($checkSql);

if (!$checkStmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar validación",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkStmt->bind_param("s", $email);
$checkStmt->execute();

$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Ese correo ya está registrado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users 
        (name, email, password, role) 
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error al preparar registro",
        "error" => $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param(
    "ssss",
    $name,
    $email,
    $hashedPassword,
    $role
);

if ($stmt->execute()) {
    $_SESSION["user_id"] = (int)$stmt->insert_id;
    $_SESSION["user_name"] = $name;
    $_SESSION["user_email"] = $email;
    $_SESSION["user_role"] = $role;

    echo json_encode([
        "success" => true,
        "message" => "Usuario registrado correctamente",
        "role" => $role,
        "redirect" => redirect_after_login_by_role($role)
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No se pudo registrar el usuario",
        "error" => $stmt->error
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>