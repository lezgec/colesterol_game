<?php
ini_set('display_errors', 0);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user_menu.php';
require_once __DIR__ . '/../config/db.php';
require_role(["teacher", "super_admin"]);

$canManageUsers = can_manage_users();
$isTeacherOnly = is_teacher() && !is_super_admin();
$dashboardTitle = $isTeacherOnly
    ? (current_lang() === "en" ? "Teacher dashboard" : "Panel docente")
    : t("admin_dashboard");
$dashboardDescription = $isTeacherOnly
    ? (current_lang() === "en"
        ? "Manage your rooms, questions, reports, profile, and teaching achievements."
        : "Gestiona tus salas, preguntas, reportes, perfil y logros docentes.")
    : t("admin_dashboard_description");
$toolsTitle = $isTeacherOnly
    ? (current_lang() === "en" ? "Teaching tools" : "Herramientas docentes")
    : t("admin_tools");
$pendingNotifications = 0;

$notificationsTable = $conn->query("SHOW TABLES LIKE 'app_notifications'");

if ($notificationsTable && $notificationsTable->num_rows > 0) {
    $notificationStmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM app_notifications
            WHERE read_at IS NULL
              AND (target_role = ? OR user_id = ?)
        ");

    if ($notificationStmt) {
        $currentRole = current_user_role();
        $currentUserId = current_user_id();
        $notificationStmt->bind_param("si", $currentRole, $currentUserId);
        $notificationStmt->execute();
        $notificationResult = $notificationStmt->get_result();
        $pendingNotifications = (int)($notificationResult->fetch_assoc()["total"] ?? 0);
        $notificationStmt->close();
    }
}

function admin_tool_icon($name) {
    $icons = [
        "questions" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 5.5a3.5 3.5 0 0 1 6.7 1.4c0 2.7-3.2 3-3.2 5.1"/><path d="M12 17h.01"/><path d="M4.5 19.5a9.5 9.5 0 1 1 15 0"/></svg>',
        "room" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        "ranking" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M17 6h3a2 2 0 0 1-2 4h-1"/><path d="M7 6H4a2 2 0 0 0 2 4h1"/></svg>',
        "dashboard" => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="15" width="7" height="6" rx="2"/></svg>',
        "public" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg>',
        "reports" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><rect x="7" y="10" width="3" height="6" rx="1"/><rect x="12" y="7" width="3" height="9" rx="1"/><rect x="17" y="12" width="3" height="4" rx="1"/></svg>',
        "notifications" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>',
        "users" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        "mail" => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>'
    ];

    return $icons[$name] ?? "";
}

function admin_metric_icon($name) {
    $icons = [
        "users" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        "questions" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 5.5a3.5 3.5 0 0 1 6.7 1.4c0 2.7-3.2 3-3.2 5.1"/><path d="M12 17h.01"/><path d="M4.5 19.5a9.5 9.5 0 1 1 15 0"/></svg>',
        "games" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h4"/><path d="M8 10v4"/><path d="M15 13h.01"/><path d="M18 11h.01"/><path d="M5.5 7h13A3.5 3.5 0 0 1 22 10.5v3A3.5 3.5 0 0 1 18.5 17H17l-2 2h-6l-2-2H5.5A3.5 3.5 0 0 1 2 13.5v-3A3.5 3.5 0 0 1 5.5 7Z"/></svg>',
        "rooms" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg>',
        "waiting" => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        "score" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 15 9l6 .9-4.5 4.4 1.1 6.2L12 17.5 6.4 20.5l1.1-6.2L3 9.9 9 9l3-6Z"/></svg>',
        "verified" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/><circle cx="12" cy="12" r="9"/></svg>',
        "pending" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5"/><path d="M12 17h.01"/><path d="M10.3 4.2 2.7 17.4A2 2 0 0 0 4.4 20h15.2a2 2 0 0 0 1.7-2.6L13.7 4.2a2 2 0 0 0-3.4 0Z"/></svg>',
        "difficulty" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M7 16h2"/><path d="M11 13h2"/><path d="M15 9h2"/><path d="M19 6h1"/></svg>'
    ];

    return $icons[$name] ?? "";
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$uiIconsJsVersion = filemtime(__DIR__ . '/../assets/js/ui_icons.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($dashboardTitle); ?></title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?m=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container admin-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <?php render_user_menu(); ?>
    </div>

    <h1><?php echo htmlspecialchars($dashboardTitle); ?></h1>
    <p class="page-intro"><?php echo htmlspecialchars($dashboardDescription); ?></p>

    <div class="admin-dashboard-layout">

        <section class="admin-section dashboard-summary-section">
            <h2><?php echo t("system_summary"); ?></h2>

            <div id="admin-summary" class="dashboard-grid">
                <div class="dashboard-card" data-empty-message-es="Aún no llega la primera tripulación." data-empty-message-en="No learners on board yet.">
                    <span class="metric-card-icon metric-users"><?php echo admin_metric_icon("users"); ?></span>
                    <h3><?php echo t("total_users"); ?></h3>
                    <p id="total-users">...</p>
                </div>

                <div class="dashboard-card" data-empty-message-es="El banco está esperando su primera pregunta." data-empty-message-en="The bank is waiting for its first question.">
                    <span class="metric-card-icon metric-questions"><?php echo admin_metric_icon("questions"); ?></span>
                    <h3><?php echo t("total_questions"); ?></h3>
                    <p id="total-questions">...</p>
                </div>

                <div class="dashboard-card" data-empty-message-es="Todavía no se juega la primera partida." data-empty-message-en="No first match yet.">
                    <span class="metric-card-icon metric-games"><?php echo admin_metric_icon("games"); ?></span>
                    <h3><?php echo t("total_games"); ?></h3>
                    <p id="total-games">...</p>
                </div>

                <div class="dashboard-card" data-empty-message-es="Aún no hay aulas abiertas en el mapa." data-empty-message-en="No rooms on the map yet.">
                    <span class="metric-card-icon metric-rooms"><?php echo admin_metric_icon("rooms"); ?></span>
                    <h3><?php echo t("total_rooms"); ?></h3>
                    <p id="total-rooms">...</p>
                </div>

                <div class="dashboard-card" data-empty-message-es="Todo tranquilo: no hay salas en espera." data-empty-message-en="Quiet for now: no waiting rooms.">
                    <span class="metric-card-icon metric-waiting"><?php echo admin_metric_icon("waiting"); ?></span>
                    <h3><?php echo t("active_rooms"); ?></h3>
                    <p id="active-rooms">...</p>
                </div>

                <div class="dashboard-card" data-empty-message-es="El promedio aparecerá con las primeras respuestas." data-empty-message-en="The average appears after the first answers.">
                    <span class="metric-card-icon metric-score"><?php echo admin_metric_icon("score"); ?></span>
                    <h3><?php echo t("average_score"); ?></h3>
                    <p id="avg-score">...</p>
                </div>
                <div class="dashboard-card" data-empty-message-es="No hay preguntas aprobadas todavía." data-empty-message-en="No approved questions yet.">
                    <span class="metric-card-icon metric-verified"><?php echo admin_metric_icon("verified"); ?></span>
                    <h3><?php echo t("verified_questions"); ?></h3>
                    <p id="verified-questions">...</p>
                </div>
                <div class="dashboard-card" data-empty-message-es="Bandeja limpia: nada pendiente por revisar." data-empty-message-en="Clean queue: nothing pending review.">
                    <span class="metric-card-icon metric-pending"><?php echo admin_metric_icon("pending"); ?></span>
                    <h3><?php echo t("pending_questions"); ?></h3>
                    <p id="pending-questions">...</p>
                </div>
                <div class="dashboard-card" data-empty-message-es="La dificultad se calibrará con el uso." data-empty-message-en="Difficulty will calibrate with use.">
                    <span class="metric-card-icon metric-difficulty"><?php echo admin_metric_icon("difficulty"); ?></span>
                    <h3><?php echo t("average_difficulty"); ?></h3>
                    <p id="avg-difficulty">...</p>
                </div>
            </div>
        </section>

        <aside class="admin-tools-panel">
            <h2><?php echo htmlspecialchars($toolsTitle); ?></h2>

            <div class="admin-tools-list">

                <a href="<?php echo app_path('pages/admin_questions.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo admin_tool_icon("questions"); ?><span><?php echo t("admin_questions"); ?></span></h3>
                </a>

                <a href="<?php echo app_path('pages/rooms/create.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo admin_tool_icon("room"); ?><span><?php echo t("create_room"); ?></span></h3>
                </a>

                <a href="<?php echo app_path('pages/ranking.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo admin_tool_icon("ranking"); ?><span><?php echo t("ranking"); ?></span></h3>
                </a>

                <a href="<?php echo app_path('pages/dashboard.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo admin_tool_icon("dashboard"); ?><span><?php echo t("dashboard"); ?></span></h3>
                </a>

                <a href="<?php echo app_path('index.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo admin_tool_icon("public"); ?><span><?php echo t("public_view"); ?></span></h3>
                </a>

                <a href="<?php echo app_path('pages/admin_reports.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3><?php echo admin_tool_icon("reports"); ?><span><?php echo t("reports_center"); ?></span></h3>
                </a>

                <a href="<?php echo app_path('pages/notifications.php'); ?>"
                   class="dashboard-card dashboard-link tool-card">
                    <h3>
                        <?php echo admin_tool_icon("notifications"); ?>
                        <span><?php echo t("notifications"); ?></span>
                        <?php if ($pendingNotifications > 0): ?>
                            <span class="tool-pending-badge" aria-label="<?php echo htmlspecialchars(t("pending")); ?>">
                                <?php echo $pendingNotifications; ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                </a>

                <?php if ($canManageUsers): ?>
                    <a href="<?php echo app_path('pages/users_management.php'); ?>"
                       class="dashboard-card dashboard-link tool-card">
                        <h3><?php echo admin_tool_icon("users"); ?><span><?php echo t("users_management"); ?></span></h3>
                    </a>
                    <a href="<?php echo app_path('pages/email_logs.php'); ?>"
                       class="dashboard-card dashboard-link tool-card">
                        <h3><?php echo admin_tool_icon("mail"); ?><span><?php echo t("email_logs"); ?></span></h3>
                    </a>
                <?php endif; ?>

            </div>
        </aside>

    </div>

    <section class="admin-section teacher-badges-section">
        <h2>
            <?php echo current_lang() === "en" ? "Teacher achievements" : "Logros docentes"; ?>
        </h2>

        <p>
            <?php echo current_lang() === "en"
                ? "Badges earned by creating, running, and reviewing learning rooms."
                : "Insignias ganadas al crear, dirigir y revisar salas de aprendizaje."; ?>
        </p>

        <div id="teacher-badges-container" class="badges-grid teacher-badges-grid">
            <p><?php echo t("loading"); ?></p>
        </div>
    </section>

    <section class="admin-section">
        <h2>
            <?php echo current_lang() === "en" ? "Open rooms" : "Salas abiertas"; ?>
        </h2>

        <p>
            <?php echo current_lang() === "en"
                ? "Return to a waiting, started, or paused room without recreating it."
                : "Vuelve a una sala en espera, iniciada o pausada sin tener que crearla otra vez."; ?>
        </p>

        <table class="admin-table open-rooms-table">
            <thead>
                <tr>
                    <th><?php echo t("room_code"); ?></th>
                    <th><?php echo t("room_name"); ?></th>
                    <th><?php echo t("status"); ?></th>
                    <th><?php echo t("total_players"); ?></th>
                    <th><?php echo t("total_questions"); ?></th>
                    <th><?php echo t("created_at"); ?></th>
                    <th><?php echo t("actions"); ?></th>
                </tr>
            </thead>

            <tbody id="open-rooms-body">
                <tr>
                    <td colspan="7"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("educational_analytics"); ?></h2>

        <div class="dashboard-grid">

            <div class="dashboard-card">
                <h3><?php echo t("overall_accuracy"); ?></h3>
                <p id="overall-accuracy">...</p>
            </div>

            <div class="dashboard-card">
                <h3><?php echo t("total_correct_answers"); ?></h3>
                <p id="total-correct">...</p>
            </div>

            <div class="dashboard-card">
                <h3><?php echo t("total_answered_questions"); ?></h3>
                <p id="total-answered">...</p>
            </div>

        </div>

        <div class="analytics-layout">

            <div class="admin-section">
                <h3><?php echo t("performance_by_difficulty"); ?></h3>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?php echo t("difficulty"); ?></th>
                            <th>%</th>
                        </tr>
                    </thead>

                    <tbody id="difficulty-analytics-body">
                        <tr>
                            <td colspan="2"><?php echo t("loading"); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h3><?php echo t("top_players"); ?></h3>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo t("player_name"); ?></th>
                            <th><?php echo t("score"); ?></th>
                        </tr>
                    </thead>

                    <tbody id="top-players-body">
                        <tr>
                            <td colspan="3"><?php echo t("loading"); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </section>

</div>

<script src="<?php echo asset_path('js/ui_icons.js'); ?>?m=<?php echo $uiIconsJsVersion; ?>"></script>
<script>
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const appUrl = path => `${window.APP_BASE_PATH}/${String(path || "").replace(/^\//, "")}`;

const ADMIN_DASHBOARD_I18N = {
    lang: "<?php echo current_lang(); ?>",
    noOpenRooms: "<?php echo current_lang() === "en" ? "No open rooms right now." : "No hay salas abiertas en este momento."; ?>",
    noTeacherBadges: "<?php echo current_lang() === "en" ? "No teacher achievements yet." : "Aún no hay logros docentes."; ?>",
    returnToRoom: "<?php echo current_lang() === "en" ? "Return to lobby" : "Volver al lobby"; ?>",
    unknownStatus: "<?php echo t('room_status_unknown'); ?>",
    statuses: {
        waiting: "<?php echo t('room_status_waiting'); ?>",
        started: "<?php echo t('room_status_started'); ?>",
        paused: "<?php echo t('room_status_paused'); ?>",
        finished: "<?php echo t('room_status_finished'); ?>"
    },
    loadError: "<?php echo current_lang() === "en" ? "Could not load this section." : "No se pudo cargar esta sección."; ?>"
};

function dashboardEndpoint(path) {
    const separator = path.includes("?") ? "&" : "?";
    return `${path}${separator}lang=${encodeURIComponent(ADMIN_DASHBOARD_I18N.lang)}`;
}

async function fetchDashboardJson(url) {
    const response = await fetch(url);
    const text = await response.text();
    return JSON.parse(text.replace(/^\uFEFF/, ""));
}

function escapeDashboardHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function formatDashboardBadgeDate(date) {
    if (!date) return "";

    return new Date(date).toLocaleDateString();
}

function renderDashboardBadgeIcon(badge) {
    const iconKey = normalizeDashboardBadgeIconKey(
        badge.badge_key || badge.badge_name || badge.badge_icon
    );

    return window.uiIcon
        ? window.uiIcon(iconKey, "ui-icon badge-svg")
        : "";
}

function normalizeDashboardBadgeIconKey(value) {
    const text = String(value || "").toLowerCase();
    const directKeys = ["school", "home", "rocket", "check", "target", "file", "users", "analytics", "star", "zap", "calendar", "medal", "gamepad", "trophy", "brain"];

    if (directKeys.includes(text)) return text;
    if (text.includes("teacher_first_room")) return "school";
    if (text.includes("teacher_room_builder")) return "home";
    if (text.includes("teacher_open_room")) return "home";
    if (text.includes("teacher_launched_room")) return "rocket";
    if (text.includes("teacher_finished_room")) return "check";
    if (text.includes("teacher_curated_room")) return "target";
    if (text.includes("teacher_extended_room")) return "file";
    if (text.includes("teacher_engagement")) return "users";
    if (text.includes("teacher_answers")) return "analytics";
    if (text.includes("teacher_accuracy")) return "target";
    if (text.includes("teacher_high_accuracy")) return "star";
    if (text.includes("primera")) return "school";
    if (text.includes("constructor")) return "home";
    if (text.includes("disponible")) return "home";
    if (text.includes("marcha")) return "rocket";
    if (text.includes("cierre")) return "check";
    if (text.includes("curador")) return "target";
    if (text.includes("profunda")) return "file";
    if (text.includes("comunidad")) return "users";
    if (text.includes("participativa")) return "analytics";
    if (text.includes("destacada")) return "star";
    if (text.includes("sala") || text.includes("room") || text.includes("aula")) return "school";
    if (text.includes("precision") || text.includes("accuracy")) return "target";
    if (text.includes("particip") || text.includes("community")) return "users";

    return "medal";
}

function renderTeacherBadges(badges) {
    const container = document.getElementById("teacher-badges-container");

    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(badges) || badges.length === 0) {
        container.innerHTML = `<p>${ADMIN_DASHBOARD_I18N.noTeacherBadges}</p>`;
        return;
    }

    badges.forEach(badge => {
        const card = document.createElement("div");
        card.classList.add("badge-card", "teacher-badge-card");

        card.innerHTML = `
            <div class="badge-icon">
                ${renderDashboardBadgeIcon(badge)}
            </div>

            <div class="badge-content">
                <h3>${escapeDashboardHtml(badge.badge_name)}</h3>
                <p>${escapeDashboardHtml(badge.badge_description)}</p>
                <small>${escapeDashboardHtml(formatDashboardBadgeDate(badge.earned_at))}</small>
            </div>
        `;

        container.appendChild(card);
    });
}

function formatDashboardRoomStatus(status) {
    return ADMIN_DASHBOARD_I18N.statuses[status] || ADMIN_DASHBOARD_I18N.unknownStatus;
}

function renderOpenRooms(rooms) {
    const tbody = document.getElementById("open-rooms-body");

    if (!tbody) return;

    tbody.innerHTML = "";

    if (!Array.isArray(rooms) || rooms.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7">${ADMIN_DASHBOARD_I18N.noOpenRooms}</td>
            </tr>
        `;
        return;
    }

    rooms.forEach(room => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${escapeDashboardHtml(room.room_code)}</td>
            <td>${escapeDashboardHtml(room.name || "-")}</td>
            <td>${escapeDashboardHtml(formatDashboardRoomStatus(room.status))}</td>
            <td>${escapeDashboardHtml(room.total_players ?? 0)}</td>
            <td>${escapeDashboardHtml(room.question_count ?? 0)}</td>
            <td>${escapeDashboardHtml(room.created_at || "-")}</td>
            <td>
                <a
                    href="${appUrl(`pages/rooms/lobby_admin.php?code=${encodeURIComponent(room.room_code)}`)}"
                    class="report-view-btn"
                >
                    ${ADMIN_DASHBOARD_I18N.returnToRoom}
                </a>
            </td>
        `;

        tbody.appendChild(row);
    });

    if (window.prepareResponsiveTables) {
        window.prepareResponsiveTables();
    }
}

function markDashboardMetricsUnavailable() {
    [
        "total-users",
        "total-questions",
        "total-games",
        "total-rooms",
        "active-rooms",
        "avg-score",
        "verified-questions",
        "pending-questions",
        "avg-difficulty"
    ].forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = "-";
    });
}

fetchDashboardJson(dashboardEndpoint(appUrl("backend/dashboard/get_admin_dashboard.php")))
    .then(result => {
        if (!result.success) return;

        const data = result.data;
        const currentLang = "<?php echo current_lang(); ?>";

        function setMetricValue(id, value, zeroValue = 0) {
            const element = document.getElementById(id);
            if (!element) return;

            element.textContent = value;
            const card = element.closest(".dashboard-card");
            if (!card) return;

            card.querySelector(".metric-empty-note")?.remove();

            const numericValue = Number(String(value).replace(/[^0-9.-]/g, ""));

            if (Number.isFinite(numericValue) && numericValue === zeroValue) {
                const note = document.createElement("small");
                note.className = "metric-empty-note";
                note.textContent = card.dataset[currentLang === "en" ? "emptyMessageEn" : "emptyMessageEs"] || "";
                card.appendChild(note);
            }
        }

        setMetricValue("total-users", data.total_users);
        setMetricValue("total-questions", data.total_questions);
        setMetricValue("total-games", data.total_games);
        setMetricValue("total-rooms", data.total_rooms);
        setMetricValue("active-rooms", data.active_rooms);
        setMetricValue("avg-score", data.avg_score);
        setMetricValue("verified-questions", data.verified_questions);
        setMetricValue("pending-questions", data.pending_questions);
        setMetricValue("avg-difficulty", data.avg_difficulty + " / 5", 1);
        renderOpenRooms(data.open_rooms);
        renderTeacherBadges(data.teacher_badges);
    })
    .catch(error => {
        console.error(error);
        markDashboardMetricsUnavailable();
        renderOpenRooms([]);
        renderTeacherBadges([]);
    });

fetchDashboardJson(dashboardEndpoint(appUrl("backend/dashboard/get_admin_analytics.php")))
    .then(result => {
        if (!result.success) return;

        const data = result.data;

        document.getElementById("overall-accuracy").textContent =
            `${data.accuracy.percentage}%`;

        document.getElementById("total-correct").textContent =
            data.accuracy.total_correct;

        document.getElementById("total-answered").textContent =
            data.accuracy.total_answered;

        const difficultyBody = document.getElementById("difficulty-analytics-body");
        difficultyBody.innerHTML = "";

        if (!Array.isArray(data.difficulty_stats) || data.difficulty_stats.length === 0) {
            difficultyBody.innerHTML = `<tr><td colspan="2">${data.no_data_message || "<?php echo t('no_data_available'); ?>"}</td></tr>`;
        } else {
            data.difficulty_stats.forEach(item => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${escapeDashboardHtml(item.difficulty)}</td>
                    <td>${escapeDashboardHtml(item.percentage)}%</td>
                `;

                difficultyBody.appendChild(row);
            });
        }

        const playersBody = document.getElementById("top-players-body");
        playersBody.innerHTML = "";

        if (!Array.isArray(data.top_players) || data.top_players.length === 0) {
            playersBody.innerHTML = `<tr><td colspan="3">${data.no_data_message || "<?php echo t('no_data_available'); ?>"}</td></tr>`;
        } else {
            data.top_players.forEach((player, index) => {
                const row = document.createElement("tr");

                row.innerHTML = `
                    <td>${escapeDashboardHtml(index + 1)}</td>
                    <td>${escapeDashboardHtml(player.player_name ?? "-")}</td>
                    <td>${escapeDashboardHtml(player.best_score)}</td>
                `;

                playersBody.appendChild(row);
            });
        }
    })
    .catch(error => {
        console.error(error);
        const difficultyBody = document.getElementById("difficulty-analytics-body");
        const playersBody = document.getElementById("top-players-body");

        document.getElementById("overall-accuracy").textContent = "-";
        document.getElementById("total-correct").textContent = "-";
        document.getElementById("total-answered").textContent = "-";

        if (difficultyBody) {
            difficultyBody.innerHTML = `<tr><td colspan="2">${ADMIN_DASHBOARD_I18N.loadError}</td></tr>`;
        }

        if (playersBody) {
            playersBody.innerHTML = `<tr><td colspan="3">${ADMIN_DASHBOARD_I18N.loadError}</td></tr>`;
        }
    });
</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
