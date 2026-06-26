<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function load_mail_config() {
    $configPath = __DIR__ . "/../config/mail.php";

    if (!file_exists($configPath)) {
        $configPath = __DIR__ . "/../config/mail.example.php";
    }

    return require $configPath;
}

function app_support_email() {
    $config = load_mail_config();
    return $config["reply_to"] ?? $config["from_email"] ?? "support@example.dev";
}

function app_url() {
    $config = load_mail_config();
    return rtrim($config["app_url"] ?? app_url_base(), "/");
}

function get_mail_log_connection() {
    global $conn;

    if (isset($conn) && $conn instanceof mysqli) {
        return $conn;
    }

    require __DIR__ . "/../config/db.php";
    return $conn;
}

function ensure_email_logs_table($db) {
    $sql = "
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email_type VARCHAR(60) NOT NULL DEFAULT 'general',
            recipient_email VARCHAR(190) NOT NULL,
            recipient_name VARCHAR(190) NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('sent', 'failed') NOT NULL,
            error_message TEXT NULL,
            sent_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_logs_type (email_type),
            INDEX idx_email_logs_recipient (recipient_email),
            INDEX idx_email_logs_status (status),
            INDEX idx_email_logs_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    return $db->query($sql);
}

function log_email_event($emailType, $recipientEmail, $recipientName, $subject, $status, $errorMessage = null) {
    try {
        $db = get_mail_log_connection();

        if (!$db || !ensure_email_logs_table($db)) {
            return;
        }

        $sentAt = $status === "sent" ? date("Y-m-d H:i:s") : null;
        $stmt = $db->prepare("
            INSERT INTO email_logs
                (email_type, recipient_email, recipient_name, subject, status, error_message, sent_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return;
        }

        $stmt->bind_param("sssssss", $emailType, $recipientEmail, $recipientName, $subject, $status, $errorMessage, $sentAt);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $exception) {
        error_log("Email log failed: " . $exception->getMessage());
    }
}

function role_display_name($role, $language = "es") {
    $labels = [
        "es" => [
            "player" => "Estudiante",
            "teacher" => "Docente",
            "super_admin" => "Super administrador"
        ],
        "en" => [
            "player" => "Student",
            "teacher" => "Teacher",
            "super_admin" => "Super administrator"
        ]
    ];

    return $labels[$language][$role] ?? $labels["es"][$role] ?? $role;
}

function send_app_email($recipientEmail, $recipientName, $subject, $htmlBody, $textBody, $emailType = "general") {
    require_once __DIR__ . "/../vendor/autoload.php";

    $config = load_mail_config();
    $required = ["host", "port", "username", "password", "from_email", "from_name"];

    foreach ($required as $key) {
        if (empty($config[$key]) || $config[$key] === "APP_SPECIFIC_PASSWORD") {
            throw new RuntimeException("Configuracion SMTP incompleta: {$key}");
        }
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config["host"];
        $mail->SMTPAuth = true;
        $mail->Username = $config["username"];
        $mail->Password = $config["password"];
        $mail->SMTPSecure = $config["encryption"] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)$config["port"];
        $mail->CharSet = "UTF-8";

        $mail->setFrom($config["from_email"], $config["from_name"]);

        if (!empty($config["reply_to"])) {
            $mail->addReplyTo($config["reply_to"], $config["from_name"]);
        }

        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
        log_email_event($emailType, $recipientEmail, $recipientName, $subject, "sent");
    } catch (Exception $exception) {
        $error = $mail->ErrorInfo ?: $exception->getMessage();
        log_email_event($emailType, $recipientEmail, $recipientName, $subject, "failed", $error);
        throw new RuntimeException("No se pudo enviar el correo: " . $error);
    }
}

function send_password_reset_email($recipientEmail, $recipientName, $resetLink) {
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, "UTF-8");
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, "UTF-8");

    send_app_email(
        $recipientEmail,
        $recipientName,
        "Recupera tu contraseña - Colesterol Game",
        "
            <p>Hola {$safeName},</p>
            <p>Recibimos una solicitud para restablecer tu contraseña en Colesterol Game.</p>
            <p><a href=\"{$safeLink}\">Crear nueva contraseña</a></p>
            <p>Este enlace vence en 30 minutos y solo puede usarse una vez.</p>
            <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
        ",
        "Hola {$recipientName},\n\n"
            . "Recibimos una solicitud para restablecer tu contraseña en Colesterol Game.\n\n"
            . "Abre este enlace para crear una nueva contraseña:\n{$resetLink}\n\n"
            . "Este enlace vence en 30 minutos y solo puede usarse una vez.\n\n"
            . "Si no solicitaste este cambio, puedes ignorar este mensaje.",
        "password_reset"
    );
}

function send_welcome_email($recipientEmail, $recipientName, $role = "player", $language = "es") {
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, "UTF-8");
    $loginUrl = app_url() . "/pages/login.php";
    $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, "UTF-8");
    $roleName = role_display_name($role, $language);

    if ($language === "en") {
        send_app_email(
            $recipientEmail,
            $recipientName,
            "Welcome to Colesterol Game",
            "
                <p>Hello {$safeName},</p>
                <p>Thank you for registering in Colesterol Game as <strong>{$roleName}</strong>.</p>
                <p>Your account is ready. You can sign in and start exploring the educational game, rooms, rankings, and your profile.</p>
                <p><a href=\"{$safeLoginUrl}\">Open Colesterol Game</a></p>
                <p>If you need help, contact us at " . htmlspecialchars(app_support_email(), ENT_QUOTES, "UTF-8") . ".</p>
            ",
            "Hello {$recipientName},\n\nThank you for registering in Colesterol Game as {$roleName}.\n\nOpen Colesterol Game: {$loginUrl}\n\nSupport: " . app_support_email(),
            "welcome"
        );
        return;
    }

    send_app_email(
        $recipientEmail,
        $recipientName,
        "Bienvenido a Colesterol Game",
        "
            <p>Hola {$safeName},</p>
            <p>Gracias por registrarte en Colesterol Game como <strong>{$roleName}</strong>.</p>
            <p>Tu cuenta ya está lista. Puedes iniciar sesión y comenzar a explorar el juego educativo, las salas, rankings y tu perfil.</p>
            <p><a href=\"{$safeLoginUrl}\">Abrir Colesterol Game</a></p>
            <p>Si necesitas ayuda, contáctanos en " . htmlspecialchars(app_support_email(), ENT_QUOTES, "UTF-8") . ".</p>
        ",
        "Hola {$recipientName},\n\nGracias por registrarte en Colesterol Game como {$roleName}.\n\nAbrir Colesterol Game: {$loginUrl}\n\nSoporte: " . app_support_email(),
        "welcome"
    );
}

function send_role_changed_email($recipientEmail, $recipientName, $oldRole, $newRole, $language = "es") {
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, "UTF-8");
    $loginUrl = app_url() . "/pages/login.php";
    $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, "UTF-8");
    $oldRoleName = role_display_name($oldRole, $language);
    $newRoleName = role_display_name($newRole, $language);

    if ($language === "en") {
        send_app_email(
            $recipientEmail,
            $recipientName,
            "Your role changed in Colesterol Game",
            "
                <p>Hello {$safeName},</p>
                <p>An administrator changed your role in Colesterol Game.</p>
                <p>Previous role: <strong>{$oldRoleName}</strong><br>New role: <strong>{$newRoleName}</strong></p>
                <p><a href=\"{$safeLoginUrl}\">Open Colesterol Game</a></p>
                <p>If you did not expect this change, contact " . htmlspecialchars(app_support_email(), ENT_QUOTES, "UTF-8") . ".</p>
            ",
            "Hello {$recipientName},\n\nYour role changed in Colesterol Game.\nPrevious role: {$oldRoleName}\nNew role: {$newRoleName}\n\n{$loginUrl}\n\nSupport: " . app_support_email(),
            "role_changed"
        );
        return;
    }

    send_app_email(
        $recipientEmail,
        $recipientName,
        "Tu rol cambió en Colesterol Game",
        "
            <p>Hola {$safeName},</p>
            <p>Un administrador cambió tu rol en Colesterol Game.</p>
            <p>Rol anterior: <strong>{$oldRoleName}</strong><br>Nuevo rol: <strong>{$newRoleName}</strong></p>
            <p><a href=\"{$safeLoginUrl}\">Abrir Colesterol Game</a></p>
            <p>Si no esperabas este cambio, contacta a " . htmlspecialchars(app_support_email(), ENT_QUOTES, "UTF-8") . ".</p>
        ",
        "Hola {$recipientName},\n\nTu rol cambió en Colesterol Game.\nRol anterior: {$oldRoleName}\nNuevo rol: {$newRoleName}\n\n{$loginUrl}\n\nSoporte: " . app_support_email(),
        "role_changed"
    );
}

function send_global_question_request_email($recipientEmail, $recipientName, $teacherName, $reviewUrl) {
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, "UTF-8");
    $safeTeacher = htmlspecialchars($teacherName, ENT_QUOTES, "UTF-8");
    $safeReviewUrl = htmlspecialchars($reviewUrl, ENT_QUOTES, "UTF-8");

    send_app_email(
        $recipientEmail,
        $recipientName,
        "Solicitud de pregunta para banco global - Colesterol Game",
        "
            <p>Hola {$safeName},</p>
            <p>El docente <strong>{$safeTeacher}</strong> envió preguntas para revisión en el banco global.</p>
            <p>Hay preguntas pendientes por aprobar antes de que puedan usarse en el banco general.</p>
            <p><a href=\"{$safeReviewUrl}\">Revisar preguntas pendientes</a></p>
        ",
        "Hola {$recipientName},\n\n"
            . "El docente {$teacherName} envió preguntas para revisión en el banco global.\n\n"
            . "Hay preguntas pendientes por aprobar antes de que puedan usarse en el banco general.\n\n"
            . "Revisar: {$reviewUrl}",
        "question_global_request"
    );
}
