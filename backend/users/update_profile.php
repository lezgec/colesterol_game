<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/profile_helpers.php';

require_login();
ensure_user_profile_columns($conn);

$userId = (int)$_SESSION["user_id"];
$isMultipart = stripos($_SERVER["CONTENT_TYPE"] ?? "", "multipart/form-data") !== false;
$data = $isMultipart
    ? $_POST
    : (json_decode(file_get_contents("php://input"), true) ?: []);

$avatarKey = normalize_avatar_key($data["avatar_key"] ?? "");
$country = normalize_country_code($data["country"] ?? "");
$city = sanitize_profile_text($data["city"] ?? "", 80);
$institution = sanitize_profile_text($data["institution"] ?? "", 140);
$occupation = sanitize_profile_text($data["occupation"] ?? "", 120);
$age = sanitize_profile_age($data["age"] ?? null);
$career = sanitize_profile_text($data["career"] ?? "", 140);
$educationLevel = sanitize_profile_text($data["education_level"] ?? "", 80);
$bio = sanitize_profile_text($data["bio"] ?? "", 500);
$customAvatarPath = null;

if ($isMultipart && isset($_FILES["avatar_file"]) && $_FILES["avatar_file"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["avatar_file"]["error"] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "No se pudo subir el avatar"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$_FILES["avatar_file"]["size"] > 2 * 1024 * 1024) {
        echo json_encode(["success" => false, "message" => "El avatar no debe superar 2 MB"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tmpPath = $_FILES["avatar_file"]["tmp_name"];
    $mime = mime_content_type($tmpPath);
    $extensions = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "image/gif" => "gif"
    ];

    if (!isset($extensions[$mime])) {
        echo json_encode(["success" => false, "message" => "Formato de avatar no permitido"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $uploadDir = __DIR__ . '/../../assets/uploads/avatars';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = "user_" . $userId . "_" . bin2hex(random_bytes(8)) . "." . $extensions[$mime];
    $targetPath = $uploadDir . "/" . $filename;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        echo json_encode(["success" => false, "message" => "No se pudo guardar el avatar"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $customAvatarPath = "/colesterol_game/assets/uploads/avatars/" . $filename;
    $avatarKey = "custom";
}

if ($customAvatarPath !== null) {
    $stmt = $conn->prepare("
        UPDATE users
        SET avatar_key = ?,
            custom_avatar_path = ?,
            country = ?,
            city = ?,
            institution = ?,
            occupation = ?,
            age = ?,
            career = ?,
            education_level = ?,
            bio = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => $conn->error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param(
        "ssssssisssi",
        $avatarKey,
        $customAvatarPath,
        $country,
        $city,
        $institution,
        $occupation,
        $age,
        $career,
        $educationLevel,
        $bio,
        $userId
    );
} else {
    $stmt = $conn->prepare("
        UPDATE users
        SET avatar_key = ?,
            country = ?,
            city = ?,
            institution = ?,
            occupation = ?,
            age = ?,
            career = ?,
            education_level = ?,
            bio = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => $conn->error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param("sssssisssi", $avatarKey, $country, $city, $institution, $occupation, $age, $career, $educationLevel, $bio, $userId);
}

$ok = $stmt->execute();
$stmt->close();

echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Perfil actualizado" : "No se pudo actualizar el perfil"
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
