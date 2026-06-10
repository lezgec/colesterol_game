<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../lang/translate.php';

require_role(["super_admin"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo t("users_management"); ?></title>
    <link rel="stylesheet" href="/colesterol_game/assets/css/style.css">
</head>
<body>

<div class="game-container admin-container">

    <div class="top-actions">
        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

        <a href="/colesterol_game/pages/admin_dashboard.php" class="logout-btn secondary-btn">
            <?php echo t("back_dashboard"); ?>
        </a>
    </div>

    <h1>👥 <?php echo t("users_management"); ?></h1>

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
const USERS_API = "/colesterol_game/backend/users/users_management.php";

let users = [];

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
    roleSuperAdmin: "<?php echo t('role_super_admin'); ?>"
};

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
            headers: {"Content-Type": "application/json"},
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

        row.innerHTML = `
            <td>${user.id}</td>

            <td>
                <input class="table-input" id="name-${user.id}" value="${escapeHtml(user.name)}">
            </td>

            <td>
                <input class="table-input" id="email-${user.id}" value="${escapeHtml(user.email)}">
            </td>

            <td>
                <select class="table-input" id="role-${user.id}">
                    <option value="player" ${user.role === "player" ? "selected" : ""}>${USERS_I18N.rolePlayer}</option>
                    <option value="teacher" ${user.role === "teacher" ? "selected" : ""}>${USERS_I18N.roleTeacher}</option>
                    <option value="super_admin" ${user.role === "super_admin" ? "selected" : ""}>${USERS_I18N.roleSuperAdmin}</option>
                </select>
            </td>

            <td>
                <select class="table-input" id="status-${user.id}">
                    <option value="active" ${user.status === "active" ? "selected" : ""}>${USERS_I18N.active}</option>
                    <option value="inactive" ${user.status === "inactive" ? "selected" : ""}>${USERS_I18N.inactive}</option>
                </select>
            </td>

            <td>${user.created_at}</td>

            <td>
                <button class="table-btn edit-btn" onclick="updateUser(${user.id})">
                    💾
                </button>

                <button class="table-btn" onclick="resetPassword(${user.id})">
                    🔑
                </button>

                <button class="table-btn delete-btn" onclick="toggleStatus(${user.id})">
                    🔄
                </button>
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
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        alert(data.success ? USERS_I18N.updated : (data.message || USERS_I18N.error));

        loadUsers();

    } catch (error) {
        console.error(error);
        alert(USERS_I18N.error);
    }
}

async function resetPassword(id) {
    const password = prompt(USERS_I18N.resetPrompt);

    if (!password) return;

    try {
        const res = await fetch(`${USERS_API}?action=reset_password`, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ id, password })
        });

        const data = await res.json();

        alert(data.success ? USERS_I18N.updated : (data.message || USERS_I18N.error));

    } catch (error) {
        console.error(error);
        alert(USERS_I18N.error);
    }
}

async function toggleStatus(id) {
    if (!confirm(USERS_I18N.confirmToggle)) return;

    try {
        const res = await fetch(`${USERS_API}?action=toggle_status`, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ id })
        });

        const data = await res.json();

        alert(data.success ? USERS_I18N.updated : (data.message || USERS_I18N.error));

        loadUsers();

    } catch (error) {
        console.error(error);
        alert(USERS_I18N.error);
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

</body>
</html>
