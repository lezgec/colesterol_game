<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';
require_once __DIR__ . '/../includes/user_menu.php';

require_role(["teacher", "super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$userName = $_SESSION["user_name"] ?? (current_lang() === "en" ? "Teacher" : "Docente");
$pageTitle = current_lang() === "en" ? "Teaching profile" : "Perfil docente";
$profileUserId = is_super_admin() ? (int)($_GET["user_id"] ?? 0) : 0;
$profileTargetQuery = $profileUserId > 0 ? "?user_id=" . $profileUserId : "";
$isAdminProfileView = $profileUserId > 0;
$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$uiIconsJsVersion = filemtime(__DIR__ . '/../assets/js/ui_icons.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?v=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">
</head>
<body>

<div class="game-container teacher-profile-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
                <?php echo current_lang() === "en" ? "Back to dashboard" : "Volver al panel"; ?>
            </a>

            <?php render_user_menu(); ?>
        </div>
    </div>

    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <p class="page-intro">
        <?php echo current_lang() === "en"
            ? "Your teaching identity, room activity, and achievements in one place."
            : "Tu identidad docente, actividad de salas y logros en un solo lugar."; ?>
    </p>

    <section class="admin-section profile-editor-section teacher-profile-section">
        <div class="profile-display-card teacher-profile-card profile-showcase-card">
            <aside class="teacher-profile-identity profile-showcase-identity">
                <div id="teacher-display-avatar" class="profile-avatar avatar-pulse">P</div>

                <div class="teacher-profile-nameblock profile-showcase-nameblock">
                    <h2 id="teacher-display-name"><?php echo htmlspecialchars($userName); ?></h2>
                    <p id="teacher-display-email"></p>
                </div>

                <button
                    type="button"
                    id="edit-teacher-profile-btn"
                    class="icon-action-btn profile-edit-text-btn"
                    <?php echo $isAdminProfileView ? 'hidden' : ''; ?>
                    title="<?php echo current_lang() === "en" ? "Edit profile" : "Editar perfil"; ?>"
                >
                    Editar
                </button>
            </aside>
            <div class="profile-display-main">
                <div class="profile-social-stats teacher-profile-stats">
                    <div>
                        <strong id="teacher-stat-rooms">0</strong>
                        <span><?php echo current_lang() === "en" ? "Rooms" : "Salas"; ?></span>
                    </div>
                    <div>
                        <strong id="teacher-stat-games">0</strong>
                        <span><?php echo current_lang() === "en" ? "Games" : "Partidas"; ?></span>
                    </div>
                    <div>
                        <strong id="teacher-stat-participants">0</strong>
                        <span><?php echo current_lang() === "en" ? "Participants" : "Participantes"; ?></span>
                    </div>
                    <div>
                        <strong id="teacher-stat-badges">0</strong>
                        <span><?php echo current_lang() === "en" ? "Badges" : "Logros"; ?></span>
                    </div>
                </div>

                <div class="profile-detail-grid">
                    <div>
                        <span><?php echo current_lang() === "en" ? "Country" : "Pais"; ?></span>
                        <strong id="teacher-display-country">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "City" : "Ciudad"; ?></span>
                        <strong id="teacher-display-city">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "University or workplace" : "Universidad o trabajo"; ?></span>
                        <strong id="teacher-display-institution">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Occupation" : "Ocupacion"; ?></span>
                        <strong id="teacher-display-occupation">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Age" : "Edad"; ?></span>
                        <strong id="teacher-display-age">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Career" : "Carrera"; ?></span>
                        <strong id="teacher-display-career">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Level" : "Nivel"; ?></span>
                        <strong id="teacher-display-education-level">-</strong>
                    </div>
                </div>

                <p id="teacher-display-bio" class="profile-display-bio"></p>
            </div>
        </div>

        <form id="teacher-profile-form" class="profile-form profile-edit-panel" hidden enctype="multipart/form-data">
            <div class="profile-editor-header">
                <div id="teacher-avatar-preview" class="profile-avatar avatar-pulse">P</div>
                <div>
                    <h2><?php echo current_lang() === "en" ? "Edit teaching profile" : "Editar perfil docente"; ?></h2>
                    <p><?php echo current_lang() === "en"
                        ? "Choose an avatar and add context for rankings and reports."
                        : "Elige un avatar y agrega contexto para rankings y reportes."; ?></p>
                </div>
            </div>

            <div class="form-group">
                <label><?php echo current_lang() === "en" ? "Avatar" : "Avatar"; ?></label>
                <div id="teacher-avatar-picker" class="avatar-picker"></div>
            </div>

            <div class="form-group">
                <label for="teacher-avatar-file"><?php echo current_lang() === "en" ? "Upload your avatar" : "Subir tu avatar"; ?></label>
                <input type="file" id="teacher-avatar-file" accept="image/png,image/jpeg,image/webp,image/gif">
                <small class="field-hint">
                    <?php echo current_lang() === "en"
                        ? "Allowed files: JPG, PNG, WebP or GIF. Maximum size: 2 MB. Square images look best."
                        : "Archivos permitidos: JPG, PNG, WebP o GIF. Tamano maximo: 2 MB. Se ve mejor si la imagen es cuadrada."; ?>
                </small>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="teacher-country"><?php echo current_lang() === "en" ? "Country" : "Pais"; ?></label>
                    <select id="teacher-country"></select>
                </div>

                <div class="form-group">
                    <label for="teacher-city"><?php echo current_lang() === "en" ? "City" : "Ciudad"; ?></label>
                    <input type="text" id="teacher-city" maxlength="80">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="teacher-institution"><?php echo current_lang() === "en" ? "University or workplace" : "Universidad o trabajo"; ?></label>
                    <input type="text" id="teacher-institution" maxlength="140">
                </div>

                <div class="form-group">
                    <label for="teacher-occupation"><?php echo current_lang() === "en" ? "Occupation" : "Ocupacion"; ?></label>
                    <input type="text" id="teacher-occupation" maxlength="120">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="teacher-age"><?php echo current_lang() === "en" ? "Age" : "Edad"; ?></label>
                    <input type="number" id="teacher-age" min="5" max="120" inputmode="numeric">
                </div>

                <div class="form-group">
                    <label for="teacher-career"><?php echo current_lang() === "en" ? "Career" : "Carrera"; ?></label>
                    <input type="text" id="teacher-career" maxlength="140">
                </div>

                <div class="form-group">
                    <label for="teacher-education-level"><?php echo current_lang() === "en" ? "Level" : "Nivel"; ?></label>
                    <input type="text" id="teacher-education-level" maxlength="80">
                </div>
            </div>

            <div class="form-group">
                <label for="teacher-bio"><?php echo current_lang() === "en" ? "About you" : "Sobre ti"; ?></label>
                <textarea id="teacher-bio" maxlength="500"></textarea>
            </div>

            <div class="form-actions-inline">
                <button type="submit" class="primary-btn">
                    <?php echo current_lang() === "en" ? "Save profile" : "Guardar perfil"; ?>
                </button>

                <button type="button" id="cancel-teacher-profile-edit-btn" class="secondary-form-btn">
                    <?php echo current_lang() === "en" ? "Cancel" : "Cancelar"; ?>
                </button>
            </div>

            <p id="teacher-profile-form-message"></p>
        </form>
    </section>

    <section class="admin-section teacher-badges-section">
        <h2><?php echo current_lang() === "en" ? "Teaching achievements" : "Logros docentes"; ?></h2>
        <div id="teacher-profile-badges" class="badges-grid teacher-badges-grid">
            <p><?php echo t("loading"); ?></p>
        </div>
    </section>

</div>

<script src="/colesterol_game/assets/js/ui_icons.js?v=<?php echo $uiIconsJsVersion; ?>"></script>
<script>
const TEACHER_PROFILE_I18N = {
    lang: "<?php echo current_lang(); ?>",
    none: "<?php echo current_lang() === "en" ? "Not provided" : "Sin registrar"; ?>",
    saved: "<?php echo current_lang() === "en" ? "Profile saved." : "Perfil guardado."; ?>",
    error: "<?php echo current_lang() === "en" ? "Could not save profile." : "No se pudo guardar el perfil."; ?>",
    noBadges: "<?php echo current_lang() === "en" ? "No teaching achievements yet." : "Aún no hay logros docentes."; ?>",
    customAvatar: "<?php echo current_lang() === "en" ? "Custom avatar" : "Avatar propio"; ?>",
    selectCountry: "<?php echo current_lang() === "en" ? "Select country" : "Selecciona país"; ?>"
};

const TEACHER_PROFILE_TARGET_QUERY = "<?php echo $profileTargetQuery; ?>";
const TEACHER_PROFILE_READONLY = <?php echo $isAdminProfileView ? "true" : "false"; ?>;

let teacherProfileUser = null;
let teacherAvatarOptions = {};
let teacherCountries = [];

function teacherProfileEndpoint(path, query = "") {
    const base = `${path}${query || ""}`;
    const separator = base.includes("?") ? "&" : "?";
    return `${base}${separator}lang=${encodeURIComponent(TEACHER_PROFILE_I18N.lang)}`;
}

function escapeTeacherHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
}

function setTeacherAvatarElement(element, avatar) {
    if (!element) return;

    element.className = "profile-avatar";
    element.innerHTML = "";

    if (avatar && avatar.type === "custom" && avatar.url) {
        element.classList.add("profile-avatar-image");
        const img = document.createElement("img");
        img.src = avatar.url;
        img.alt = TEACHER_PROFILE_I18N.customAvatar;
        element.appendChild(img);
        return;
    }

    const key = avatar && avatar.key ? avatar.key : "pulse";
    element.classList.add(`avatar-${key}`);
    element.innerHTML = window.uiIcon
        ? window.uiIcon(avatar?.icon || teacherAvatarOptions[key]?.icon || "heart", "ui-icon profile-avatar-svg")
        : "";
}

function populateTeacherCountries(countries, selectedCode) {
    const select = document.getElementById("teacher-country");
    if (!select) return;

    select.innerHTML = `<option value="">${TEACHER_PROFILE_I18N.selectCountry}</option>`;

    Object.entries(countries || {}).forEach(([code, country]) => {
        const option = document.createElement("option");
        option.value = code;
        option.textContent = `${country.flag} ${country[TEACHER_PROFILE_I18N.lang] || country.es}`;
        option.selected = code === selectedCode;
        select.appendChild(option);
    });
}

function renderTeacherAvatarPicker(options, selectedKey, currentAvatar = null) {
    const picker = document.getElementById("teacher-avatar-picker");
    if (!picker) return;

    picker.innerHTML = "";

    if (currentAvatar && currentAvatar.type === "custom" && currentAvatar.url) {
        const customLabel = document.createElement("label");
        customLabel.className = "avatar-choice";
        customLabel.innerHTML = `
            <input type="radio" name="teacher_avatar_key" value="custom" ${selectedKey === "custom" ? "checked" : ""}>
            <span class="profile-avatar profile-avatar-image"><img src="${escapeTeacherHtml(currentAvatar.url)}" alt="Avatar actual"></span>
            <em>${TEACHER_PROFILE_I18N.customAvatar}</em>
        `;
        picker.appendChild(customLabel);
    }

    Object.entries(options || {}).forEach(([key, avatar]) => {
        const label = document.createElement("label");
        label.className = "avatar-choice";
        label.innerHTML = `
            <input type="radio" name="teacher_avatar_key" value="${escapeTeacherHtml(key)}" ${key === selectedKey ? "checked" : ""}>
            <span class="profile-avatar avatar-${escapeTeacherHtml(key)}">${window.uiIcon ? window.uiIcon(avatar.icon || "heart", "ui-icon profile-avatar-svg") : ""}</span>
            <em>${escapeTeacherHtml(avatar.label)}</em>
        `;
        picker.appendChild(label);
    });
}

function updateTeacherProfileDisplay(user) {
    teacherProfileUser = user;
    const country = user.country_display || {};
    setTeacherAvatarElement(document.getElementById("teacher-display-avatar"), user.avatar);
    setTeacherAvatarElement(document.getElementById("teacher-avatar-preview"), user.avatar);
    document.getElementById("teacher-display-name").textContent = user.name || "-";
    document.getElementById("teacher-display-email").textContent = user.email || "";
    document.getElementById("teacher-display-country").textContent =
        country.name ? `${country.flag} ${country.name}` : TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-city").textContent = user.city || TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-institution").textContent = user.institution || TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-occupation").textContent = user.occupation || TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-age").textContent = user.age || TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-career").textContent = user.career || TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-education-level").textContent = user.education_level || TEACHER_PROFILE_I18N.none;
    document.getElementById("teacher-display-bio").textContent = user.bio || "";

    document.getElementById("teacher-city").value = user.city || "";
    document.getElementById("teacher-institution").value = user.institution || "";
    document.getElementById("teacher-occupation").value = user.occupation || "";
    document.getElementById("teacher-age").value = user.age || "";
    document.getElementById("teacher-career").value = user.career || "";
    document.getElementById("teacher-education-level").value = user.education_level || "";
    document.getElementById("teacher-bio").value = user.bio || "";
}

function fillTeacherStats(data) {
    document.getElementById("teacher-stat-rooms").textContent = data.total_rooms ?? 0;
    document.getElementById("teacher-stat-games").textContent = data.total_games ?? 0;
    document.getElementById("teacher-stat-participants").textContent = data.total_participants ?? 0;
    document.getElementById("teacher-stat-badges").textContent = Array.isArray(data.teacher_badges) ? data.teacher_badges.length : 0;
}

function renderTeacherBadgeIcon(badge) {
    const iconKey = normalizeTeacherBadgeIconKey(
        badge.badge_key || badge.badge_name || badge.badge_icon
    );

    return window.uiIcon
        ? window.uiIcon(iconKey, "ui-icon badge-svg")
        : "";
}

function normalizeTeacherBadgeIconKey(value) {
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
    const container = document.getElementById("teacher-profile-badges");
    container.innerHTML = "";

    if (!Array.isArray(badges) || badges.length === 0) {
        container.innerHTML = `<p>${TEACHER_PROFILE_I18N.noBadges}</p>`;
        return;
    }

    badges.forEach(badge => {
        const card = document.createElement("div");
        card.className = "badge-card teacher-badge-card";
        card.innerHTML = `
            <div class="badge-icon">${renderTeacherBadgeIcon(badge)}</div>
            <div class="badge-content">
                <h3>${escapeTeacherHtml(badge.badge_name)}</h3>
                <p>${escapeTeacherHtml(badge.badge_description)}</p>
                <small>${escapeTeacherHtml(badge.earned_at || "")}</small>
            </div>
        `;
        container.appendChild(card);
    });
}

async function loadTeacherProfile() {
    const response = await fetch(teacherProfileEndpoint("/colesterol_game/backend/users/get_profile.php", TEACHER_PROFILE_TARGET_QUERY));
    const data = await response.json();

    if (!data.success) return;

    teacherAvatarOptions = data.avatars || {};
    teacherCountries = data.countries || {};
    updateTeacherProfileDisplay(data.user);
    populateTeacherCountries(teacherCountries, data.user.country || "");
    renderTeacherAvatarPicker(
        teacherAvatarOptions,
        data.user.avatar?.type === "custom" ? "custom" : (data.user.avatar_key || "pulse"),
        data.user.avatar || null
    );
}

async function loadTeacherDashboardStats() {
    const response = await fetch(teacherProfileEndpoint("/colesterol_game/backend/dashboard/get_admin_dashboard.php", TEACHER_PROFILE_TARGET_QUERY));
    const result = await response.json();

    if (!result.success) return;

    fillTeacherStats(result.data || {});
    renderTeacherBadges(result.data.teacher_badges || []);
}

if (!TEACHER_PROFILE_READONLY) {
    document.getElementById("edit-teacher-profile-btn").addEventListener("click", () => {
        document.getElementById("teacher-profile-form").hidden = false;
        document.getElementById("edit-teacher-profile-btn").hidden = true;
    });
}

document.getElementById("cancel-teacher-profile-edit-btn").addEventListener("click", () => {
    document.getElementById("teacher-profile-form").hidden = true;
    document.getElementById("edit-teacher-profile-btn").hidden = false;
    if (teacherProfileUser) {
        updateTeacherProfileDisplay(teacherProfileUser);
        populateTeacherCountries(teacherCountries, teacherProfileUser.country || "");
    }
});

document.getElementById("teacher-profile-form").addEventListener("submit", async event => {
    event.preventDefault();

    const formData = new FormData();
    const selectedAvatar = document.querySelector('input[name="teacher_avatar_key"]:checked');
    const file = document.getElementById("teacher-avatar-file").files[0];

    formData.append("avatar_key", selectedAvatar ? selectedAvatar.value : "pulse");
    formData.append("country", document.getElementById("teacher-country").value);
    formData.append("city", document.getElementById("teacher-city").value);
    formData.append("institution", document.getElementById("teacher-institution").value);
    formData.append("occupation", document.getElementById("teacher-occupation").value);
    formData.append("age", document.getElementById("teacher-age").value);
    formData.append("career", document.getElementById("teacher-career").value);
    formData.append("education_level", document.getElementById("teacher-education-level").value);
    formData.append("bio", document.getElementById("teacher-bio").value);

    if (file) {
        formData.append("avatar_file", file);
    }

    const message = document.getElementById("teacher-profile-form-message");
    message.textContent = "";

    try {
        const response = await fetch("/colesterol_game/backend/users/update_profile.php", {
            method: "POST",
            body: formData
        });
        const result = await response.json();

        if (!result.success) {
            message.textContent = result.message || TEACHER_PROFILE_I18N.error;
            return;
        }

        message.textContent = TEACHER_PROFILE_I18N.saved;
        document.getElementById("teacher-avatar-file").value = "";
        await loadTeacherProfile();
        document.getElementById("teacher-profile-form").hidden = true;
        document.getElementById("edit-teacher-profile-btn").hidden = false;
    } catch (error) {
        console.error(error);
        message.textContent = TEACHER_PROFILE_I18N.error;
    }
});

Promise.all([loadTeacherProfile(), loadTeacherDashboardStats()]).catch(error => {
    console.error(error);
});

if (!TEACHER_PROFILE_READONLY && new URLSearchParams(window.location.search).get("edit") === "1") {
    document.getElementById("edit-teacher-profile-btn").click();
}
</script>

<script src="/colesterol_game/assets/js/theme.js?v=<?php echo $themeVersion; ?>"></script>
</body>
</html>
