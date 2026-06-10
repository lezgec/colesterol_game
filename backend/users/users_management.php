<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!has_role(["super_admin"])) {
    echo json_encode([
        "success" => false,
        "message" => "No autorizado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];
$action = $_GET["action"] ?? "";

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function validRole($role) {
    return in_array($role, ["player", "teacher", "super_admin"], true);
}

function validStatus($status) {
    return in_array($status, ["active", "inactive"], true);
}

if ($method === "GET" && $action === "list") {

    global $conn;

    $result = $conn->query("
        SELECT
            id,
            name,
            email,
            role,
            status,
            created_at
        FROM users
        ORDER BY created_at DESC
    ");

    if (!$result) {
        jsonResponse([
            "success" => false,
            "message" => "Error al listar usuarios",
            "error" => $conn->error
        ]);
    }

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            "id" => (int)$row["id"],
            "name" => $row["name"],
            "email" => $row["email"],
            "role" => $row["role"],
            "status" => $row["status"],
            "created_at" => $row["created_at"]
        ];
    }

    jsonResponse([
        "success" => true,
        "users" => $users
    ]);
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!is_array($data)) {
    $data = [];
}

if ($method === "POST" && $action === "create") {

    $name = trim($data["name"] ?? "");
    $email = strtolower(trim($data["email"] ?? ""));
    $password = trim($data["password"] ?? "");
    $role = trim($data["role"] ?? "player");
    $status = trim($data["status"] ?? "active");

    if ($name === "" || $email === "" || $password === "") {
        jsonResponse([
            "success" => false,
            "message" => "Nombre, correo y contraseña son obligatorios"
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse([
            "success" => false,
            "message" => "Correo inválido"
        ]);
    }

    if (!validRole($role)) {
        $role = "player";
    }

    if (!validStatus($status)) {
        $status = "active";
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users
        (name, email, password, role, status)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        jsonResponse([
            "success" => false,
            "message" => "Error prepare",
            "error" => $conn->error
        ]);
    }

    $stmt->bind_param(
        "sssss",
        $name,
        $email,
        $hash,
        $role,
        $status
    );

    if (!$stmt->execute()) {
        jsonResponse([
            "success" => false,
            "message" => "No se pudo crear el usuario",
            "error" => $stmt->error
        ]);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    jsonResponse([
        "success" => true,
        "message" => "Usuario creado correctamente",
        "user_id" => $newId
    ]);
}

if ($method === "POST" && $action === "update") {

    $id = (int)($data["id"] ?? 0);
    $name = trim($data["name"] ?? "");
    $email = strtolower(trim($data["email"] ?? ""));
    $role = trim($data["role"] ?? "player");
    $status = trim($data["status"] ?? "active");

    if ($id <= 0) {
        jsonResponse([
            "success" => false,
            "message" => "ID inválido"
        ]);
    }

    if ($name === "" || $email === "") {
        jsonResponse([
            "success" => false,
            "message" => "Nombre y correo son obligatorios"
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse([
            "success" => false,
            "message" => "Correo inválido"
        ]);
    }

    if (!validRole($role)) {
        $role = "player";
    }

    if (!validStatus($status)) {
        $status = "active";
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET
            name = ?,
            email = ?,
            role = ?,
            status = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        jsonResponse([
            "success" => false,
            "message" => "Error prepare",
            "error" => $conn->error
        ]);
    }

    $stmt->bind_param(
        "ssssi",
        $name,
        $email,
        $role,
        $status,
        $id
    );

    if (!$stmt->execute()) {
        jsonResponse([
            "success" => false,
            "message" => "No se pudo actualizar",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();

    jsonResponse([
        "success" => true,
        "message" => "Usuario actualizado correctamente"
    ]);
}

if ($method === "POST" && $action === "reset_password") {

    $id = (int)($data["id"] ?? 0);
    $password = trim($data["password"] ?? "");

    if ($id <= 0 || $password === "") {
        jsonResponse([
            "success" => false,
            "message" => "ID y contraseña son obligatorios"
        ]);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        jsonResponse([
            "success" => false,
            "message" => "Error prepare",
            "error" => $conn->error
        ]);
    }

    $stmt->bind_param("si", $hash, $id);

    if (!$stmt->execute()) {
        jsonResponse([
            "success" => false,
            "message" => "No se pudo resetear contraseña",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();

    jsonResponse([
        "success" => true,
        "message" => "Contraseña actualizada correctamente"
    ]);
}

if ($method === "POST" && $action === "toggle_status") {

    $id = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        jsonResponse([
            "success" => false,
            "message" => "ID inválido"
        ]);
    }

    if ($id === (int)$_SESSION["user_id"]) {
        jsonResponse([
            "success" => false,
            "message" => "No puedes desactivar tu propio usuario"
        ]);
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET status = IF(status = 'active', 'inactive', 'active')
        WHERE id = ?
    ");

    if (!$stmt) {
        jsonResponse([
            "success" => false,
            "message" => "Error prepare",
            "error" => $conn->error
        ]);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        jsonResponse([
            "success" => false,
            "message" => "No se pudo cambiar estado",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();

    jsonResponse([
        "success" => true,
        "message" => "Estado actualizado correctamente"
    ]);
}

jsonResponse([
    "success" => false,
    "message" => "Acción no válida"
]);
?>