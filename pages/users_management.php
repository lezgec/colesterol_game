<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["super_admin"]);

$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$responsiveTablesVersion = filemtime(__DIR__ . '/../assets/js/responsive_tables.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("users_management"); ?></title>
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

        <a href="<?php echo app_path('pages/admin_dashboard.php'); ?>" class="logout-btn secondary-btn">
            <?php echo t("back_dashboard"); ?>
        </a>
    </div>

    <h1><?php echo t("users_management"); ?></h1>

    <section class="admin-section">
        <h2><?php echo t("create_user"); ?></h2>

        <form id="create-user-form" class="form-grid">
            <div class="form-group">
                <label><?php echo t("name"); ?></label>
                <input type="text" id="create-name" required>
            </div>

            <div class="form-group">
                <label><?php echo t("email"); ?></label>
                <input type="email" id="create-email" required>
            </div>

            <div class="form-group">
                <label><?php echo t("password"); ?></label>
                <input type="password" id="create-password" required>
            </div>

            <div class="form-group">
                <label><?php echo t("role"); ?></label>
                <select id="create-role">
                    <option value="player"><?php echo t("role_player"); ?></option>
                    <option value="teacher"><?php echo t("role_teacher"); ?></option>
                    <option value="super_admin"><?php echo t("role_super_admin"); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label><?php echo t("status"); ?></label>
                <select id="create-status">
                    <option value="active"><?php echo t("active"); ?></option>
                    <option value="inactive"><?php echo t("inactive"); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button class="primary-btn" type="submit">
                    <?php echo t("save"); ?>
                </button>
            </div>
        </form>

        <p id="create-user-message"></p>
    </section>

    <section class="admin-section">
        <h2><?php echo t("registered_users"); ?></h2>

        <div class="form-grid">
            <div class="form-group">
                <label><?php echo t("search"); ?></label>
                <input type="text" id="search-users" placeholder="<?php echo t("search"); ?>...">
            </div>

            <div class="form-group">
                <label><?php echo t("role"); ?></label>
                <select id="filter-role">
                    <option value=""><?php echo t("all"); ?></option>
                    <option value="player"><?php echo t("role_player"); ?></option>
                    <option value="teacher"><?php echo t("role_teacher"); ?></option>
                    <option value="super_admin"><?php echo t("role_super_admin"); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label><?php echo t("status"); ?></label>
                <select id="filter-status">
                    <option value=""><?php echo t("all"); ?></option>
                    <option value="active"><?php echo t("active"); ?></option>
                    <option value="inactive"><?php echo t("inactive"); ?></option>
                </select>
            </div>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo t("name"); ?></th>
                    <th><?php echo t("email"); ?></th>
                    <th><?php echo t("role"); ?></th>
                    <th><?php echo t("status"); ?></th>
                    <th><?php echo t("created_at"); ?></th>
                    <th><?php echo t("actions"); ?></th>
                </tr>
            </thead>

            <tbody id="users-body">
                <tr>
                    <td colspan="7"><?php echo t("loading"); ?></td>
                </tr>
            </tbody>
        </table>
    </section>

</div>

<script>
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="<?php echo asset_path('js/http.js'); ?>?m=<?php echo filemtime(__DIR__ . '/../assets/js/http.js'); ?>"></script>
<script>
const USERS_API = appUrl("backend/users/users_management.php");

let users = [];
let userModalResolver = null;

const USERS_I18N = {
    loading: "<?php echo t('loading'); ?>",
    error: "<?php echo t('error'); ?>",
    saved: "<?php echo t('saved_successfully'); ?>",
    updated: "<?php echo t('updated_successfully'); ?>",
    noData: "<?php echo t('no_users_found'); ?>",
    resetPrompt: "<?php echo t('enter_new_password'); ?>",
    confirmToggle: "<?php echo t('confirm_toggle_status'); ?>",
    active: "<?php echo t('active'); ?>",
    inactive: "<?php echo t('inactive'); ?>",
    rolePlayer: "<?php echo t('role_player'); ?>",
    roleTeacher: "<?php echo t('role_teacher'); ?>",
    roleSuperAdmin: "<?php echo t('role_super_admin'); ?>",
    close: "<?php echo t('close'); ?>",
    cancel: "<?php echo t('cancel'); ?>",
    confirm: "<?php echo t('confirm'); ?>",
    viewProfile: "<?php echo t('view_profile'); ?>",
    saveChanges: "<?php echo t('save_changes'); ?>",
    resetPassword: "<?php echo t('reset_password'); ?>",
    changeStatus: "<?php echo t('change_status'); ?>"
};

function ensureUserModal() {
    let modal = document.getElementById("user-action-modal");

    if (modal) {
        return modal;
    }

    modal = document.createElement("div");
    modal.id = "user-action-modal";
    modal.className = "question-modal-backdrop app-confirm-backdrop";
    modal.hidden = true;
    modal.innerHTML = `
        <section class="app-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="user-action-title">
            <header>
                <h2 id="user-action-title"></h2>
                <button type="button" class="question-modal-close" id="user-action-close" aria-label="${USERS_I18N.close}">&times;</button>
            </header>
            <div class="user-action-body">
                <p id="user-action-message"></p>
                <input id="user-action-input" class="table-input" type="password" hidden>
            </div>
            <footer>
                <button type="button" id="user-action-cancel" class="secondary-form-btn">${USERS_I18N.cancel}</button>
                <button type="button" id="user-action-accept" class="primary-btn">${USERS_I18N.confirm}</button>
            </footer>
        </section>
    `;
    document.body.appendChild(modal);

    const close = result => {
        modal.hidden = true;
        document.body.classList.remove("modal-open");

        if (userModalResolver) {
            userModalResolver(result);
            userModalResolver = null;
        }
    };

    modal.querySelector("#user-action-close").addEventListener("click", () => close(null));
    modal.querySelector("#user-action-cancel").addEventListener("click", () => close(null));
    modal.querySelector("#user-action-accept").addEventListener("click", () => {
        const input = modal.querySelector("#user-action-input");
        close(input.hidden ? true : input.value);
    });
    modal.addEventListener("click", event => {
        if (event.target === modal) {
            close(null);
        }
    });

    return modal;
}

function showUserNotice(message, title = USERS_I18N.updated) {
    const modal = ensureUserModal();
    modal.querySelector("#user-action-title").textContent = title;
    modal.querySelector("#user-action-message").textContent = message;
    modal.querySelector("#user-action-input").hidden = true;
    modal.querySelector("#user-action-cancel").hidden = true;
    modal.hidden = false;
    document.body.classList.add("modal-open");

    return new Promise(resolve => {
        userModalResolver = result => {
            modal.querySelector("#user-action-cancel").hidden = false;
            resolve(result);
        };
    });
}

function showUserConfirm(message, title = "<?php echo t('confirm_action'); ?>") {
    const modal = ensureUserModal();
    modal.querySelector("#user-action-title").textContent = title;
    modal.querySelector("#user-action-message").textContent = message;
    modal.querySelector("#user-action-input").hidden = true;
    modal.querySelector("#user-action-cancel").hidden = false;
    modal.hidden = false;
    document.body.classList.add("modal-open");

    return new Promise(resolve => {
        userModalResolver = resolve;
    });
}

function showUserPasswordPrompt(message, title = USERS_I18N.resetPrompt) {
    const modal = ensureUserModal();
    const input = modal.querySelector("#user-action-input");
    modal.querySelector("#user-action-title").textContent = title;
    modal.querySelector("#user-action-message").textContent = message;
    input.value = "";
    input.hidden = false;
    modal.querySelector("#user-action-cancel").hidden = false;
    modal.hidden = false;
    document.body.classList.add("modal-open");
    setTimeout(() => input.focus(), 0);

    return new Promise(resolve => {
        userModalResolver = resolve;
    });
}

document.getElementById("create-user-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        name: document.getElementById("create-name").value.trim(),
        email: document.getElementById("create-email").value.trim(),
        password: document.getElementById("create-password").value.trim(),
        role: document.getElementById("create-role").value,
        status: document.getElementById("create-status").value
    };

    const message = document.getElementById("create-user-message");
    message.textContent = USERS_I18N.loading;

    try {
        const res = await fetch(`${USERS_API}?action=create`, {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.success) {
            message.textContent = USERS_I18N.saved;
            e.target.reset();
            loadUsers();
        } else {
            message.textContent = data.message || USERS_I18N.error;
        }

    } catch (error) {
        console.error(error);
        message.textContent = USERS_I18N.error;
    }
});

document.getElementById("search-users").addEventListener("input", renderUsers);
document.getElementById("filter-role").addEventListener("change", renderUsers);
document.getElementById("filter-status").addEventListener("change", renderUsers);

async function loadUsers() {
    const tbody = document.getElementById("users-body");
    tbody.innerHTML = `<tr><td colspan="7">${USERS_I18N.loading}</td></tr>`;

    try {
        const res = await fetch(`${USERS_API}?action=list`);
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="7">${data.message || USERS_I18N.error}</td></tr>`;
            return;
        }

        users = data.users || [];
        renderUsers();

    } catch (error) {
        console.error(error);
        tbody.innerHTML = `<tr><td colspan="7">${USERS_I18N.error}</td></tr>`;
    }
}

function renderUsers() {
    const tbody = document.getElementById("users-body");

    const search = document.getElementById("search-users").value.toLowerCase().trim();
    const role = document.getElementById("filter-role").value;
    const status = document.getElementById("filter-status").value;

    const filtered = users.filter(user => {
        const matchesSearch =
            user.name.toLowerCase().includes(search) ||
            user.email.toLowerCase().includes(search);

        const matchesRole = role === "" || user.role === role;
        const matchesStatus = status === "" || user.status === status;

        return matchesSearch && matchesRole && matchesStatus;
    });

    tbody.innerHTML = "";

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7">${USERS_I18N.noData}</td></tr>`;
        return;
    }

    filtered.forEach(user => {
        const row = document.createElement("tr");
        const canViewProfile = ["player", "teacher"].includes(user.role);
        const profileHref = user.role === "teacher"
            ? appUrl(`pages/teacher_profile.php?user_id=${user.id}`)
            : appUrl(`pages/player_profile.php?user_id=${user.id}`);

        row.innerHTML = `
            <td class="user-id-cell">${escapeHtml(user.id)}</td>

            <td>
                <input class="table-input user-table-input" id="name-${user.id}" value="${escapeHtml(user.name)}" aria-label="Nombre">
            </td>

            <td>
                <input class="table-input user-table-input" id="email-${user.id}" value="${escapeHtml(user.email)}" aria-label="Correo electronico">
            </td>

            <td>
                <select class="table-input user-table-select" id="role-${user.id}" aria-label="Rol">
                    <option value="player" ${user.role === "player" ? "selected" : ""}>${USERS_I18N.rolePlayer}</option>
                    <option value="teacher" ${user.role === "teacher" ? "selected" : ""}>${USERS_I18N.roleTeacher}</option>
                    <option value="super_admin" ${user.role === "super_admin" ? "selected" : ""}>${USERS_I18N.roleSuperAdmin}</option>
                </select>
            </td>

            <td>
                <select class="table-input user-table-select status-${escapeHtml(user.status)}" id="status-${user.id}" aria-label="Estado">
                    <option value="active" ${user.status === "active" ? "selected" : ""}>${USERS_I18N.active}</option>
                    <option value="inactive" ${user.status === "inactive" ? "selected" : ""}>${USERS_I18N.inactive}</option>
                </select>
            </td>

            <td class="user-created-cell">${escapeHtml(user.created_at)}</td>

            <td>
                <div class="user-actions">
                    ${canViewProfile ? `
                        <a class="table-btn icon-only profile-btn" href="${profileHref}" title="${USERS_I18N.viewProfile}" aria-label="${USERS_I18N.viewProfile}">
                            <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12c2.8 0 5-2.2 5-5s-2.2-5-5-5-5 2.2-5 5 2.2 5 5 5Zm0 2c-3.4 0-8 1.7-8 5v1.5c0 .8.7 1.5 1.5 1.5h13c.8 0 1.5-.7 1.5-1.5V19c0-3.3-4.6-5-8-5Z"/></svg></span>
                        </a>
                    ` : ""}

                    <button class="table-btn icon-only edit-btn" onclick="updateUser(${user.id})" title="${USERS_I18N.saveChanges}" aria-label="${USERS_I18N.saveChanges}">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h12l2 2v16H5a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2Zm2 2v6h9V5H7Zm0 10v4h10v-4H7Z"/></svg></span>
                    </button>

                    <button class="table-btn icon-only key-btn" onclick="resetPassword(${user.id})" title="${USERS_I18N.resetPassword}" aria-label="${USERS_I18N.resetPassword}">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3a7 7 0 0 0-6.7 9L2 17.3V22h4.7l1-1H11v-3.3l1.1-1.1A7 7 0 1 0 14 3Zm0 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/></svg></span>
                    </button>

                    <button class="table-btn icon-only delete-btn" onclick="toggleStatus(${user.id})" title="${USERS_I18N.changeStatus}" aria-label="${USERS_I18N.changeStatus}">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h8.6l-2.3-2.3L14.7 3 20 8.3l-5.3 5.3-1.4-1.4L15.6 10H7a3 3 0 0 0 0 6h2v2H7A5 5 0 0 1 7 7Zm10 10H8.4l2.3 2.3L9.3 21 4 15.7l5.3-5.3 1.4 1.4L8.4 14H17a3 3 0 0 0 0-6h-2V6h2a5 5 0 0 1 0 10Z"/></svg></span>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(row);
    });
}

async function updateUser(id) {
    const payload = {
        id,
        name: document.getElementById(`name-${id}`).value.trim(),
        email: document.getElementById(`email-${id}`).value.trim(),
        role: document.getElementById(`role-${id}`).value,
        status: document.getElementById(`status-${id}`).value
    };

    try {
        const res = await fetch(`${USERS_API}?action=update`, {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        await showUserNotice(data.success ? USERS_I18N.updated : (data.message || USERS_I18N.error), data.success ? USERS_I18N.updated : USERS_I18N.error);

        loadUsers();

    } catch (error) {
        console.error(error);
        await showUserNotice(USERS_I18N.error, USERS_I18N.error);
    }
}

async function resetPassword(id) {
    const password = await showUserPasswordPrompt(USERS_I18N.resetPrompt);

    if (!password) return;

    try {
        const res = await fetch(`${USERS_API}?action=reset_password`, {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({ id, password })
        });

        const data = await res.json();

        await showUserNotice(data.success ? USERS_I18N.updated : (data.message || USERS_I18N.error), data.success ? USERS_I18N.updated : USERS_I18N.error);

    } catch (error) {
        console.error(error);
        await showUserNotice(USERS_I18N.error, USERS_I18N.error);
    }
}

async function toggleStatus(id) {
    if (!await showUserConfirm(USERS_I18N.confirmToggle)) return;

    try {
        const res = await fetch(`${USERS_API}?action=toggle_status`, {
            method: "POST",
            headers: csrfHeaders({"Content-Type": "application/json"}),
            body: JSON.stringify({ id })
        });

        const data = await res.json();

        await showUserNotice(data.success ? USERS_I18N.updated : (data.message || USERS_I18N.error), data.success ? USERS_I18N.updated : USERS_I18N.error);

        loadUsers();

    } catch (error) {
        console.error(error);
        await showUserNotice(USERS_I18N.error, USERS_I18N.error);
    }
}

function escapeHtml(text) {
    return String(text)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

loadUsers();
</script>


<script src="<?php echo asset_path('js/responsive_tables.js'); ?>?m=<?php echo $responsiveTablesVersion; ?>"></script>
<script src="<?php echo asset_path('js/theme.js'); ?>?m=<?php echo $themeVersion; ?>"></script>
</body>
</html>
