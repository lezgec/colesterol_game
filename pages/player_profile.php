<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/ui_icons.php';
require_once __DIR__ . '/../includes/user_menu.php';

require_role(["player", "super_admin"]);

$requestedUserId = (int)($_GET["user_id"] ?? 0);
$isAdminProfileView = is_super_admin() && $requestedUserId > 0;
$profileUserParam = $isAdminProfileView
    ? "?user_id=" . $requestedUserId
    : "";
$profileFetchParam = $isAdminProfileView
    ? "&user_id=" . $requestedUserId
    : "";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$uiIconsJsVersion = filemtime(__DIR__ . '/../assets/js/ui_icons.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

$userName = $isAdminProfileView ? "Player" : ($_SESSION["user_name"] ?? "Player");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t("player_profile"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css?m=<?php echo $styleVersion; ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/svg+xml" href="/colesterol_game/assets/icons/icon.svg">

</head>
<body>

<div class="game-container player-profile-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <div class="top-links">
            <a href="<?php echo $isAdminProfileView ? '/colesterol_game/pages/users_management.php' : '/colesterol_game/pages/player_dashboard.php'; ?>" class="logout-btn secondary-btn">
                <?php echo $isAdminProfileView ? t("users_management") : t("back_to_player_dashboard"); ?>
            </a>

            <?php if (!$isAdminProfileView): ?>
                <?php render_user_menu(); ?>
            <?php else: ?>
                <a href="/colesterol_game/pages/logout.php" class="logout-btn">
                    <?php echo t("logout"); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <h1><?php echo ui_icon("users"); ?> <?php echo t("player_profile"); ?></h1>

    <a href="/colesterol_game/backend/exports/export_player_profile_csv.php"
    class="primary-btn"
    style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        <?php echo ui_icon("download"); ?> <?php echo t("export_csv"); ?>
    </a>
    <a href="/colesterol_game/backend/exports/pdf/export_player_profile_pdf.php"
    class="primary-btn"
    style="display:block; text-align:center; text-decoration:none; margin-bottom:15px;">
        <?php echo ui_icon("file"); ?> <?php echo t("export_pdf"); ?>
    </a>

    <p class="player-welcome">
        <?php echo t("learning_report_for"); ?>
        <strong><?php echo htmlspecialchars($userName); ?></strong>
    </p>

    <section class="admin-section profile-editor-section profile-page-section">
        <div class="profile-display-card profile-showcase-card">
            <aside class="profile-showcase-identity">
                <div id="profile-display-avatar" class="profile-avatar avatar-pulse"><?php echo ui_icon("heart"); ?></div>

                <div class="profile-showcase-nameblock">
                    <h2 id="profile-display-name"><?php echo htmlspecialchars($userName); ?></h2>
                    <p id="profile-display-email"></p>
                </div>

                <button type="button" id="edit-profile-btn" class="icon-action-btn profile-edit-text-btn" <?php echo $isAdminProfileView ? 'hidden' : ''; ?> title="<?php echo current_lang() === "en" ? "Edit profile" : "Editar perfil"; ?>">
                    <?php echo ui_icon("edit"); ?> <span><?php echo current_lang() === "en" ? "Edit" : "Editar"; ?></span>
                </button>
            </aside>
            <div class="profile-display-main">
                <div class="profile-social-stats">
                    <div>
                        <strong id="profile-stat-correct">0</strong>
                        <span><?php echo current_lang() === "en" ? "Correct" : "Correctas"; ?></span>
                    </div>
                    <div>
                        <strong id="profile-stat-rooms">0</strong>
                        <span><?php echo current_lang() === "en" ? "Rooms" : "Salas"; ?></span>
                    </div>
                    <div>
                        <strong id="profile-stat-points">0</strong>
                        <span><?php echo current_lang() === "en" ? "Points" : "Puntos"; ?></span>
                    </div>
                    <div>
                        <strong id="profile-stat-precision">0%</strong>
                        <span><?php echo current_lang() === "en" ? "Precision" : "Precision"; ?></span>
                    </div>
                </div>

                <div class="profile-detail-grid">
                    <div>
                        <span><?php echo current_lang() === "en" ? "Country" : "Pais"; ?></span>
                        <strong id="profile-display-country">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "City" : "Ciudad"; ?></span>
                        <strong id="profile-display-city">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "University or workplace" : "Universidad o trabajo"; ?></span>
                        <strong id="profile-display-institution">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Occupation" : "Ocupacion"; ?></span>
                        <strong id="profile-display-occupation">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Age" : "Edad"; ?></span>
                        <strong id="profile-display-age">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Career" : "Carrera"; ?></span>
                        <strong id="profile-display-career">-</strong>
                    </div>
                    <div>
                        <span><?php echo current_lang() === "en" ? "Level" : "Nivel"; ?></span>
                        <strong id="profile-display-education-level">-</strong>
                    </div>
                </div>

                <p id="profile-display-bio" class="profile-display-bio"></p>
            </div>
        </div>

        <div class="profile-editor-header">
            <div id="profile-avatar-preview" class="profile-avatar avatar-pulse"><?php echo ui_icon("heart"); ?></div>
            <div>
                <h2><?php echo current_lang() === "en" ? "Player profile" : "Perfil del jugador"; ?></h2>
                <p><?php echo current_lang() === "en"
                    ? "Customize your avatar and add context about where you study or work."
                    : "Personaliza tu avatar y agrega contexto sobre donde estudias o trabajas."; ?></p>
            </div>
        </div>

        <form id="profile-form" class="profile-form profile-edit-panel" hidden data-admin-readonly="<?php echo $isAdminProfileView ? '1' : '0'; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label><?php echo current_lang() === "en" ? "Avatar" : "Avatar"; ?></label>
                <div id="profile-avatar-picker" class="avatar-picker"></div>
            </div>

            <div class="form-group">
                <label for="profile-avatar-file"><?php echo current_lang() === "en" ? "Upload your avatar" : "Subir tu avatar"; ?></label>
                <input type="file" id="profile-avatar-file" accept="image/png,image/jpeg,image/webp,image/gif">
                <small class="field-hint">
                    <?php echo current_lang() === "en"
                        ? "Allowed files: JPG, PNG, WebP or GIF. Maximum size: 2 MB. Square images look best."
                        : "Archivos permitidos: JPG, PNG, WebP o GIF. Tamano maximo: 2 MB. Se ve mejor si la imagen es cuadrada."; ?>
                </small>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="profile-country"><?php echo current_lang() === "en" ? "Country" : "Pais"; ?></label>
                    <select id="profile-country"></select>
                </div>

                <div class="form-group">
                    <label for="profile-city"><?php echo current_lang() === "en" ? "City" : "Ciudad"; ?></label>
                    <input type="text" id="profile-city" maxlength="80">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="profile-institution"><?php echo current_lang() === "en" ? "University or workplace" : "Universidad o trabajo"; ?></label>
                    <input type="text" id="profile-institution" maxlength="140">
                </div>

                <div class="form-group">
                    <label for="profile-occupation"><?php echo current_lang() === "en" ? "Occupation" : "Ocupacion"; ?></label>
                    <input type="text" id="profile-occupation" maxlength="120">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="profile-age"><?php echo current_lang() === "en" ? "Age" : "Edad"; ?></label>
                    <input type="number" id="profile-age" min="5" max="120" inputmode="numeric">
                </div>

                <div class="form-group">
                    <label for="profile-career"><?php echo current_lang() === "en" ? "Career" : "Carrera"; ?></label>
                    <input type="text" id="profile-career" maxlength="140">
                </div>

                <div class="form-group">
                    <label for="profile-education-level"><?php echo current_lang() === "en" ? "Level" : "Nivel"; ?></label>
                    <input type="text" id="profile-education-level" maxlength="80">
                </div>
            </div>

            <div class="form-group">
                <label for="profile-bio"><?php echo current_lang() === "en" ? "About you" : "Sobre ti"; ?></label>
                <textarea id="profile-bio" maxlength="500"></textarea>
            </div>

            <button type="submit" class="primary-btn">
                <?php echo current_lang() === "en" ? "Save profile" : "Guardar perfil"; ?>
            </button>

            <button type="button" id="cancel-profile-edit-btn" class="secondary-form-btn">
                <?php echo current_lang() === "en" ? "Cancel" : "Cancelar"; ?>
            </button>

            <p id="profile-form-message"></p>
        </form>
    </section>

    <section class="profile-export-actions">
        <a href="/colesterol_game/backend/exports/export_player_profile_csv.php<?php echo $profileUserParam; ?>" class="primary-btn">
            <?php echo t("export_csv"); ?>
        </a>
        <a href="/colesterol_game/backend/exports/pdf/export_player_profile_pdf.php<?php echo $profileUserParam; ?>" class="primary-btn">
            <?php echo t("export_pdf"); ?>
        </a>
    </section>


    <section class="dashboard-grid" id="profile-summary">
        <div class="dashboard-card">
            <h3><?php echo t("total_answered_questions"); ?></h3>
            <p id="total-answers">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("precision"); ?></h3>
            <p id="precision">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_difficulty"); ?></h3>
            <p id="avg-difficulty">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("max_difficulty"); ?></h3>
            <p id="max-difficulty">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("average_response_time"); ?></h3>
            <p id="avg-response-time">...</p>
        </div>

        <div class="dashboard-card">
            <h3><?php echo t("total_points"); ?></h3>
            <p id="total-points">...</p>
        </div>
    </section>
    <section class="admin-section">
        <h2><?php echo ui_icon("analytics"); ?> <?php echo t("visual_analytics"); ?></h2>

        <div class="analytics-layout">

            <div class="chart-card">
                <h3><?php echo t("difficulty_progression"); ?></h3>
                <canvas id="difficultyChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("category_precision"); ?></h3>
                <canvas id="categoryRadarChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("mistakes_distribution"); ?></h3>
                <canvas id="mistakesChart"></canvas>
            </div>

            <div class="chart-card">
                <h3><?php echo t("response_time_analysis"); ?></h3>
                <canvas id="responseTimeChart"></canvas>
            </div>

        </div>
    </section>
    <section class="admin-section profile-achievements-section">
        <div class="profile-achievements-layout">
            <div class="profile-streaks-panel">
                <h2><?php echo current_lang() === "en" ? "Streaks" : "Rachas"; ?></h2>
                <p><?php echo current_lang() === "en"
                    ? "Your current momentum and best answer streak."
                    : "Tu impulso actual y tu mejor cadena de respuestas."; ?></p>
                <div id="profile-streaks-list" class="profile-streaks-list"></div>
            </div>

            <div class="profile-badges-panel">
                <h2><?php echo t("badges"); ?></h2>
                <div id="badges-container" class="badges-grid"></div>
            </div>
        </div>
    </section>
    <section class="admin-section">
        <h2><?php echo t("performance_by_category"); ?></h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th><?php echo t("category"); ?></th>
                    <th><?php echo t("correct_answers"); ?></th>
                    <th><?php echo t("precision"); ?></th>
                    <th><?php echo t("average_response_time"); ?></th>
                    <th><?php echo t("average_difficulty"); ?></th>
                </tr>
            </thead>

            <tbody id="category-body">
                <tr>
                    <td colspan="5"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="admin-section">
        <h2><?php echo t("your_mistakes"); ?></h2>

        <div id="mistakes-list">
            <?php echo t("loading"); ?>
        </div>
    </section>

</div>

<script src="/colesterol_game/assets/js/ui_icons.js?m=<?php echo $uiIconsJsVersion; ?>"></script>
<script>
const PROFILE_I18N = {
    lang: "<?php echo current_lang(); ?>",
    noData: "<?php echo t('no_data_available'); ?>",
    noMistakes: "<?php echo t('no_mistakes_recorded'); ?>",
    noInsights: "<?php echo t('no_insights_available'); ?>",
    noBadges: "<?php echo t('no_badges_earned'); ?>",
    selectedAnswer: "<?php echo t('selected_answer'); ?>",
    correctAnswer: "<?php echo t('correct_answer'); ?>",
    difficulty: "<?php echo t('difficulty'); ?>",
    precisionPercent: "<?php echo t('precision_percent'); ?>",
    avgResponseTimeShort: "<?php echo t('avg_response_time_short'); ?>",
    loading: "<?php echo t('loading'); ?>",
    saved: "<?php echo current_lang() === "en" ? "Profile saved" : "Perfil guardado"; ?>",
    error: "<?php echo t('error'); ?>"
};
const PROFILE_TARGET_QUERY = "<?php echo $isAdminProfileView ? '?user_id=' . $requestedUserId : ''; ?>";

function profileEndpoint(path, query = PROFILE_TARGET_QUERY) {
    const base = `${path}${query || ""}`;
    const separator = base.includes("?") ? "&" : "?";
    return `${base}${separator}lang=${encodeURIComponent(PROFILE_I18N.lang)}`;
}

let profileAvatars = {};

function avatarClass(key) {
    return `profile-avatar avatar-${key || "pulse"}`;
}

function renderAvatarPicker(avatars, selectedKey, currentAvatar = null) {
    const picker = document.getElementById("profile-avatar-picker");
    picker.innerHTML = "";

    if (currentAvatar && currentAvatar.type === "custom" && currentAvatar.url) {
        const customLabel = document.createElement("label");
        customLabel.className = "avatar-choice";
        customLabel.innerHTML = `
            <input type="radio" name="profile_avatar_key" value="custom" ${selectedKey === "custom" ? "checked" : ""}>
            <span class="profile-avatar profile-avatar-image"><img src="${currentAvatar.url}" alt="Avatar actual"></span>
            <em><?php echo current_lang() === "en" ? "Current avatar" : "Avatar actual"; ?></em>
        `;
        picker.appendChild(customLabel);
    }

    Object.entries(avatars || {}).forEach(([key, avatar]) => {
        const label = document.createElement("label");
        label.className = "avatar-choice";
        label.innerHTML = `
            <input type="radio" name="profile_avatar_key" value="${key}" ${key === selectedKey ? "checked" : ""}>
            <span class="${avatarClass(key)}">${window.uiIcon ? window.uiIcon(avatar.icon || "heart", "ui-icon profile-avatar-svg") : ""}</span>
            <em>${avatar.label}</em>
        `;
        picker.appendChild(label);
    });

    picker.querySelectorAll("input[name='profile_avatar_key']").forEach(input => {
        input.addEventListener("change", () => {
            updateAvatarPreview(input.value);
        });
    });
}

function updateAvatarPreview(key) {
    const preview = document.getElementById("profile-avatar-preview");
    if (key === "custom" && currentProfileUser?.avatar?.type === "custom") {
        setProfileAvatarElement(preview, currentProfileUser.avatar);
        return;
    }
    const avatar = profileAvatars[key] || profileAvatars.pulse || { icon: "heart" };
    preview.className = avatarClass(key);
    preview.innerHTML = window.uiIcon ? window.uiIcon(avatar.icon || "heart", "ui-icon profile-avatar-svg") : "";
}

let profileCountries = {};
let currentProfileUser = null;

function setProfileAvatarElement(element, avatar) {
    if (!element) return;

    element.innerHTML = "";

    if (avatar?.type === "custom" && avatar.url) {
        element.className = "profile-avatar profile-avatar-image";
        const img = document.createElement("img");
        img.src = avatar.url;
        img.alt = "Avatar";
        element.appendChild(img);
        return;
    }

    const key = avatar?.key || "pulse";
    element.className = avatarClass(key);
    element.innerHTML = window.uiIcon ? window.uiIcon(avatar?.icon || profileAvatars[key]?.icon || "heart", "ui-icon profile-avatar-svg") : "";
}

function renderCountrySelect(countries, selectedCode) {
    const select = document.getElementById("profile-country");
    if (!select) return;

    select.innerHTML = `<option value="">${PROFILE_I18N.noData}</option>`;

    Object.entries(countries || {}).forEach(([code, country]) => {
        const option = document.createElement("option");
        option.value = code;
        option.textContent = `${country.flag} ${country.<?php echo current_lang() === "en" ? "en" : "es"; ?>}`;
        option.selected = code === selectedCode;
        select.appendChild(option);
    });
}

function renderProfileDisplay(user) {
    currentProfileUser = user;

    setProfileAvatarElement(document.getElementById("profile-display-avatar"), user.avatar);
    document.getElementById("profile-display-name").textContent = user.name || "-";
    document.getElementById("profile-display-email").textContent = user.email || "";

    const country = user.country_display || {};
    document.getElementById("profile-display-country").textContent =
        country.name ? `${country.flag} ${country.name}` : "-";
    document.getElementById("profile-display-city").textContent = user.city || "-";
    document.getElementById("profile-display-institution").textContent = user.institution || "-";
    document.getElementById("profile-display-occupation").textContent = user.occupation || "-";
    document.getElementById("profile-display-age").textContent = user.age || "-";
    document.getElementById("profile-display-career").textContent = user.career || "-";
    document.getElementById("profile-display-education-level").textContent = user.education_level || "-";
    document.getElementById("profile-display-bio").textContent =
        user.bio || "<?php echo current_lang() === "en" ? "No bio yet." : "Aún no hay biografía."; ?>";
}

function fillProfileEditForm(user) {
    const presetKey = user.avatar?.type === "custom" ? "custom" : (user.avatar_key || "pulse");

    setProfileAvatarElement(document.getElementById("profile-avatar-preview"), user.avatar);
    renderAvatarPicker(profileAvatars, presetKey, user.avatar || null);
    renderCountrySelect(profileCountries, user.country || "");

    document.getElementById("profile-city").value = user.city || "";
    document.getElementById("profile-institution").value = user.institution || "";
    document.getElementById("profile-occupation").value = user.occupation || "";
    document.getElementById("profile-age").value = user.age || "";
    document.getElementById("profile-career").value = user.career || "";
    document.getElementById("profile-education-level").value = user.education_level || "";
    document.getElementById("profile-bio").value = user.bio || "";
    document.getElementById("profile-avatar-file").value = "";
}

function ensureProfileStreakStats(summary) {
    const stats = document.getElementById("profile-streaks-list");

    if (!stats) return;

    const items = [
        {
            id: "profile-stat-best-streak",
            value: summary.best_correct_streak || 0,
            label: "<?php echo current_lang() === "en" ? "Best correct streak" : "Mejor racha correcta"; ?>",
            hint: "<?php echo current_lang() === "en" ? "Most correct answers in a row" : "Mayor cantidad de aciertos seguidos"; ?>"
        },
        {
            id: "profile-stat-daily-streak",
            value: summary.current_daily_streak || 0,
            label: "<?php echo current_lang() === "en" ? "Daily streak" : "Racha diaria"; ?>",
            hint: "<?php echo current_lang() === "en" ? "Consecutive active days" : "Dias activos consecutivos"; ?>"
        }
    ];

    stats.innerHTML = "";

    items.forEach(item => {
        const wrapper = document.createElement("div");
        wrapper.className = "streak-card";
        wrapper.innerHTML = `
            <strong id="${item.id}">${item.value}</strong>
            <span>${item.label}</span>
            <small>${item.hint}</small>
        `;
        stats.appendChild(wrapper);
    });
}

async function loadPublicProfile() {
    const response = await fetch(profileEndpoint("/colesterol_game/backend/users/get_profile.php"));
    const data = await response.json();

    if (!data.success) return;

    profileAvatars = data.avatars || {};
    profileCountries = data.countries || {};
    renderProfileDisplay(data.user || {});
    fillProfileEditForm(data.user || {});
}

document.getElementById("edit-profile-btn").addEventListener("click", () => {
    fillProfileEditForm(currentProfileUser || {});
    document.getElementById("profile-form").hidden = false;
});

document.getElementById("cancel-profile-edit-btn").addEventListener("click", () => {
    document.getElementById("profile-form").hidden = true;
    document.getElementById("profile-form-message").textContent = "";
});

document.getElementById("profile-avatar-file").addEventListener("change", event => {
    const file = event.target.files?.[0];
    if (!file) return;

    setProfileAvatarElement(
        document.getElementById("profile-avatar-preview"),
        { type: "custom", url: URL.createObjectURL(file) }
    );
});

document.getElementById("profile-form").addEventListener("submit", async event => {
    event.preventDefault();
    event.stopImmediatePropagation();

    const message = document.getElementById("profile-form-message");
    const avatarFile = document.getElementById("profile-avatar-file").files?.[0];
    const payload = new FormData();

    message.textContent = PROFILE_I18N.loading;
    payload.append("avatar_key", document.querySelector("input[name='profile_avatar_key']:checked")?.value || "pulse");
    payload.append("country", document.getElementById("profile-country").value);
    payload.append("city", document.getElementById("profile-city").value.trim());
    payload.append("institution", document.getElementById("profile-institution").value.trim());
    payload.append("occupation", document.getElementById("profile-occupation").value.trim());
    payload.append("age", document.getElementById("profile-age").value);
    payload.append("career", document.getElementById("profile-career").value.trim());
    payload.append("education_level", document.getElementById("profile-education-level").value.trim());
    payload.append("bio", document.getElementById("profile-bio").value.trim());

    if (avatarFile) {
        payload.append("avatar_file", avatarFile);
    }

    try {
        const response = await fetch("/colesterol_game/backend/users/update_profile.php", {
            method: "POST",
            body: payload
        });
        const result = await response.json();

        message.textContent = result.success ? PROFILE_I18N.saved : (result.message || PROFILE_I18N.error);

        if (result.success) {
            await loadPublicProfile();
            document.getElementById("profile-form").hidden = true;
        }
    } catch (error) {
        console.error(error);
        message.textContent = PROFILE_I18N.error;
    }
}, true);

loadPublicProfile().catch(console.error);

fetch(profileEndpoint("/colesterol_game/backend/reports/player_profile_report.php"))
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            return;
        }

        const summary = data.summary;

        document.getElementById("total-answers").textContent = summary.total_answers;
        document.getElementById("precision").textContent = summary.precision + "%";
        document.getElementById("avg-difficulty").textContent = summary.avg_difficulty + " / 5";
        document.getElementById("max-difficulty").textContent = summary.max_difficulty + " / 5";
        document.getElementById("avg-response-time").textContent = summary.avg_response_time + "s";
        document.getElementById("total-points").textContent = summary.total_points;
        document.getElementById("profile-stat-correct").textContent = summary.correct_answers;
        document.getElementById("profile-stat-rooms").textContent = summary.rooms_played || 0;
        document.getElementById("profile-stat-points").textContent = summary.total_points;
        document.getElementById("profile-stat-precision").textContent = summary.precision + "%";
        ensureProfileStreakStats(summary);

        renderCategories(data.categories);
        renderMistakes(data.mistakes);
        renderCharts(data);
        loadBadges();
    })
    .catch(console.error);

function renderCategories(categories) {
    const tbody = document.getElementById("category-body");
    tbody.innerHTML = "";

    if (!Array.isArray(categories) || categories.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">${PROFILE_I18N.noData}</td></tr>`;
        return;
    }

    categories.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.category}</td>
            <td>${item.correct} / ${item.total}</td>
            <td>${item.precision}%</td>
            <td>${item.avg_time}s</td>
            <td>${item.avg_difficulty} / 5</td>
        `;

        tbody.appendChild(row);
    });
}

function renderMistakes(mistakes) {
    const container = document.getElementById("mistakes-list");
    container.innerHTML = "";

    if (!Array.isArray(mistakes) || mistakes.length === 0) {
        container.innerHTML = `<p>${PROFILE_I18N.noMistakes}</p>`;
        return;
    }

    mistakes.forEach(item => {
        const card = document.createElement("div");
        card.classList.add("mistake-card");
        const options = item.options || {};
        const optionRows = ["A", "B", "C", "D"].map(letter => {
            const classes = ["mistake-option"];
            const badges = [];

            if (letter === item.selected_option) {
                classes.push("is-selected");
                badges.push(PROFILE_I18N.selectedAnswer);
            }

            if (letter === item.correct_option) {
                classes.push("is-correct");
                badges.push(PROFILE_I18N.correctAnswer);
            }

            return `
                <li class="${classes.join(" ")}">
                    <strong>${letter}</strong>
                    <span>${options[letter] || ""}</span>
                    ${badges.length > 0
                        ? `<em>${badges.join(" / ")}</em>`
                        : ""}
                </li>
            `;
        }).join("");

        card.innerHTML = `
            <h3>${item.question}</h3>

            <p>
                <strong>${item.category}</strong> - 
                ${PROFILE_I18N.difficulty} ${item.difficulty_level} / 5 - 
                ${item.response_time}s
            </p>

            <ul class="mistake-options-list">
                ${optionRows}
            </ul>

            <p>${item.explanation}</p>

            <small>${item.answered_at}</small>
        `;

        container.appendChild(card);
    });
}

function renderCharts(data) {
    renderDifficultyChart(data.answers || []);
    renderCategoryRadarChart(data.categories || []);
    renderMistakesChart(data.mistake_distribution || []);
    renderResponseTimeChart(data.categories || []);
}

function renderDifficultyChart(answers) {

    const ctx =
        document.getElementById("difficultyChart");

    if (!ctx || answers.length === 0) return;

    new Chart(ctx, {
        type: "line",
        data: {
            labels: answers.map((_, i) => i + 1),

            datasets: [{
                label: PROFILE_I18N.difficulty,
                data: answers.map(a =>
                    parseFloat(a.difficulty_level || 1)
                ),
                tension: 0.3
            }]
        },

        options: {
            responsive: true,

            scales: {
                y: {
                    min: 1,
                    max: 5
                }
            }
        }
    });
}

function renderCategoryRadarChart(categories) {

    const ctx =
        document.getElementById("categoryRadarChart");

    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "radar",

        data: {
            labels: categories.map(c => c.category),

            datasets: [{
                label: PROFILE_I18N.precisionPercent,
                data: categories.map(c =>
                    parseFloat(c.precision || 0)
                )
            }]
        },

        options: {
            responsive: true,

            scales: {
                r: {
                    min: 0,
                    max: 100
                }
            }
        }
    });
}

function renderMistakesChart(mistakes) {

    const ctx =
        document.getElementById("mistakesChart");

    if (!ctx || mistakes.length === 0) return;

    new Chart(ctx, {
        type: "doughnut",

        data: {
            labels: mistakes.map(m =>
                m.category || "Unknown"
            ),

            datasets: [{
                data: mistakes.map(m =>
                    parseInt(m.total_errors || 0)
                )
            }]
        },

        options: {
            responsive: true
        }
    });
}

function renderResponseTimeChart(categories) {

    const ctx =
        document.getElementById("responseTimeChart");

    if (!ctx || categories.length === 0) return;

    new Chart(ctx, {
        type: "bar",

        data: {
            labels: categories.map(c => c.category),

            datasets: [{
                label: PROFILE_I18N.avgResponseTimeShort,
                data: categories.map(c =>
                    parseFloat(c.avg_time || 0)
                )
            }]
        },

        options: {
            responsive: true
        }
    });
}

async function loadInsights() {

    try {

        const response = await fetch(
            profileEndpoint("/colesterol_game/backend/exports/player_insights_report.php")
        );

        const data = await response.json();

        if (!data.success) {
            return;
        }

        renderInsights(data.insights || []);

    } catch (error) {

        console.error("Insights error:", error);
    }
}

function renderInsights(insights) {

    const container =
        document.getElementById("insights-container");

    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(insights) || insights.length === 0) {

        container.innerHTML = `
            <p>${PROFILE_I18N.noInsights}</p>
        `;

        return;
    }

    insights.forEach(insight => {

        const card = document.createElement("div");

        card.classList.add("insight-card");

        card.innerHTML = `
            <div class="insight-header">
                ${getInsightIcon(insight.type)}
                <h3>${insight.title}</h3>
            </div>

            <p>${insight.message}</p>
        `;

        container.appendChild(card);
    });
}

function getInsightIcon(type) {

    const icon = (name) => window.uiIcon ? window.uiIcon(name, "ui-icon insight-svg") : "";

    switch(type) {

        case "weak_category":
            return icon("target");

        case "strong_category":
            return icon("trophy");

        case "fast_player":
            return icon("zap");

        case "slow_player":
            return icon("clock");

        case "advanced_player":
            return icon("analytics");

        case "difficulty_master":
            return icon("rocket");

        case "excellent_precision":
            return icon("target");

        case "needs_practice":
            return icon("file");

        default:
            return icon("brain");
    }
}

async function loadBadges() {

    try {

        const response = await fetch(
            profileEndpoint("/colesterol_game/backend/badges/get_user_badges.php")
        );

        const data = await response.json();

        if (!data.success) {
            return;
        }

        renderBadges(data.badges || []);

    } catch (error) {

        console.error("Badges error:", error);
    }
}

function renderBadges(badges) {

    const container =
        document.getElementById("badges-container");

    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(badges) || badges.length === 0) {

        container.innerHTML = `
            <p>${PROFILE_I18N.noBadges}</p>
        `;

        return;
    }

    badges.forEach(badge => {

        const card = document.createElement("div");

        card.classList.add("badge-card");

        card.innerHTML = `
            <div class="badge-icon">
                ${renderBadgeIcon(badge)}
            </div>

            <div class="badge-content">
                <h3>${badge.badge_name}</h3>

                <p>${badge.badge_description}</p>

                <small>
                    ${formatBadgeDate(badge.earned_at)}
                </small>
            </div>
        `;

        container.appendChild(card);
    });
}

function renderBadgeIcon(badge) {
    const iconKey = normalizeBadgeIconKey(
        badge.badge_icon || badge.badge_key || badge.badge_name
    );

    return window.uiIcon
        ? window.uiIcon(iconKey, "ui-icon badge-svg")
        : "";
}

function normalizeBadgeIconKey(value) {
    const text = String(value || "").toLowerCase();
    const directKeys = ["school", "home", "rocket", "check", "target", "file", "users", "analytics", "star", "zap", "calendar", "medal", "gamepad", "trophy", "brain"];

    if (directKeys.includes(text)) return text;
    if (text.includes("streak") || text.includes("racha") || text.includes("fast") || text.includes("difficulty")) return "zap";
    if (text.includes("precision") || text.includes("expert")) return "target";
    if (text.includes("answer")) return "analytics";
    if (text.includes("game") || text.includes("first")) return "gamepad";
    if (text.includes("advanced")) return "rocket";

    return "medal";
}

function formatBadgeDate(date) {

    return new Date(date)
        .toLocaleDateString();
}

if (
    new URLSearchParams(window.location.search).get("edit") === "1" &&
    document.getElementById("edit-profile-btn") &&
    !document.getElementById("edit-profile-btn").hidden
) {
    document.getElementById("edit-profile-btn").click();
}
</script>


<script src="/colesterol_game/assets/js/responsive_tables.js?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="/colesterol_game/assets/js/theme.js?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>

