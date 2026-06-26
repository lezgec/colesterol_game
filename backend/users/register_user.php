<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/password_policy.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/profile_helpers.php';
require_once __DIR__ . '/../../includes/mail_helpers.php';

function register_json_response(array $payload): void {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

ensure_user_profile_columns($conn);

require_csrf_token();

$isMultipart = str_starts_with((string)($_SERVER["CONTENT_TYPE"] ?? ""), "multipart/form-data");
$input = $isMultipart ? "" : file_get_contents("php://input");
$data = $isMultipart ? $_POST : json_decode($input, true);

if (!$data) {
    register_json_response([
        "success" => false,
        "message" => "No se recibieron datos validos"
    ]);
}

$firstName = sanitize_profile_text($data["first_name"] ?? "", 80);
$lastName = sanitize_profile_text($data["last_name"] ?? "", 80);
$name = sanitize_profile_text($data["name"] ?? trim($firstName . " " . $lastName), 170);
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$passwordConfirmation = $data["password_confirmation"] ?? "";
$avatarKey = normalize_avatar_key($data["avatar_key"] ?? "");
$country = normalize_country_code($data["country"] ?? "");
$city = sanitize_profile_text($data["city"] ?? "", 80);
$institution = sanitize_profile_text($data["institution"] ?? "", 140);
$occupation = sanitize_profile_text($data["occupation"] ?? "", 120);
$age = sanitize_profile_age($data["age"] ?? null);
$career = sanitize_profile_text($data["career"] ?? "", 140);
$educationLevel = sanitize_profile_text($data["education_level"] ?? "", 80);
$bio = sanitize_profile_text($data["bio"] ?? "", 500);
$requestedRole = strtolower(trim((string)($data["role"] ?? "player")));
$role = in_array($requestedRole, ["player", "teacher"], true) ? $requestedRole : "player";
$customAvatarPath = "";
$uploadedAvatarPath = "";

if ($firstName === "" || $lastName === "" || $name === "" || $email === "" || $password === "") {
    register_json_response([
        "success" => false,
        "message" => "Nombre, apellido, correo y contrasena son obligatorios"
    ]);
}

if (!hash_equals((string)$password, (string)$passwordConfirmation)) {
    register_json_response([
        "success" => false,
        "message" => "Las contrasenas no coinciden"
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    register_json_response([
        "success" => false,
        "message" => "Correo electronico no valido"
    ]);
}

$passwordErrors = validate_password_policy($password);

if (!empty($passwordErrors)) {
    register_json_response([
        "success" => false,
        "message" => password_policy_message()
    ]);
}

if ($isMultipart && isset($_FILES["avatar_file"]) && $_FILES["avatar_file"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["avatar_file"]["error"] !== UPLOAD_ERR_OK) {
        register_json_response([
            "success" => false,
            "message" => "No se pudo subir el avatar"
        ]);
    }

    if ((int)$_FILES["avatar_file"]["size"] > 2 * 1024 * 1024) {
        register_json_response([
            "success" => false,
            "message" => "El avatar no debe superar 2 MB"
        ]);
    }

    $tmpPath = $_FILES["avatar_file"]["tmp_name"];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    $extensions = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "image/gif" => "gif"
    ];

    if (!isset($extensions[$mime])) {
        register_json_response([
            "success" => false,
            "message" => "Formato de avatar no permitido"
        ]);
    }

    $uploadDir = __DIR__ . '/../../assets/uploads/avatars';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        register_json_response([
            "success" => false,
            "message" => "No se pudo preparar la carpeta de avatares"
        ]);
    }

    $filename = "avatar_" . bin2hex(random_bytes(16)) . "." . $extensions[$mime];
    $uploadedAvatarPath = $uploadDir . "/" . $filename;
    $customAvatarPath = app_path("assets/uploads/avatars/" . $filename);
    $avatarKey = "custom";
} elseif ($avatarKey === "custom") {
    $avatarKey = "pulse";
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");

if (!$checkStmt) {
    register_json_response([
        "success" => false,
        "message" => "Error al preparar validacion",
        "error" => $conn->error
    ]);
}

$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    register_json_response([
        "success" => false,
        "message" => "Ese correo ya esta registrado"
    ]);
}

$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("
    INSERT INTO users
        (name, email, password, role, avatar_key, custom_avatar_path, country, city, institution, occupation, age, career, education_level, bio)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    register_json_response([
        "success" => false,
        "message" => "Error al preparar registro",
        "error" => $conn->error
    ]);
}

$stmt->bind_param(
    "ssssssssssisss",
    $name,
    $email,
    $hashedPassword,
    $role,
    $avatarKey,
    $customAvatarPath,
    $country,
    $city,
    $institution,
    $occupation,
    $age,
    $career,
    $educationLevel,
    $bio
);

$conn->begin_transaction();
$newUserId = 0;

try {
    if ($uploadedAvatarPath !== "" && !move_uploaded_file($_FILES["avatar_file"]["tmp_name"], $uploadedAvatarPath)) {
        throw new RuntimeException("No se pudo guardar el avatar");
    }

    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error ?: "No se pudo registrar el usuario");
    }

    $newUserId = (int)$stmt->insert_id;
    $sessionToken = create_user_session_token();

    if (!store_user_session_token($conn, $newUserId, $sessionToken)) {
        throw new RuntimeException("No se pudo iniciar la sesion segura");
    }

    send_welcome_email($email, $name, $role, $_SESSION["lang"] ?? "es");

    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();

    if ($newUserId > 0) {
        $cleanup = $conn->prepare("DELETE FROM users WHERE id = ? AND email = ? LIMIT 1");

        if ($cleanup) {
            $cleanup->bind_param("is", $newUserId, $email);
            $cleanup->execute();
            $cleanup->close();
        }
    }

    if ($uploadedAvatarPath !== "" && is_file($uploadedAvatarPath)) {
        @unlink($uploadedAvatarPath);
    }
    $stmt->close();
    $conn->close();

    register_json_response([
        "success" => false,
        "message" => "No se pudo completar el registro. Intenta de nuevo o contacta a soporte.",
        "error" => env_bool("APP_DEBUG", false) ? $exception->getMessage() : null
    ]);
}

$stmt->close();

session_regenerate_id(true);

$_SESSION["user_id"] = $newUserId;
$_SESSION["user_name"] = $name;
$_SESSION["user_email"] = $email;
$_SESSION["user_role"] = $role;
$_SESSION["session_token"] = $sessionToken;

$conn->close();

register_json_response([
    "success" => true,
    "message" => "Usuario registrado correctamente",
    "role" => $role,
    "redirect" => redirect_after_login_by_role($role)
]);
