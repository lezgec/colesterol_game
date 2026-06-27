<?php
require_once __DIR__ . '/../lang/translate.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui_icons.php';
require_once __DIR__ . '/../backend/users/profile_helpers.php';

if (current_session_is_active()) {
    header("Location: " . redirect_after_login_by_role($_SESSION["user_role"] ?? "player"));
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
$styleVersion = filemtime(__DIR__ . '/../assets/css/style.css');
$passwordPolicyJsVersion = filemtime(__DIR__ . '/../assets/js/password_policy.js');
$registerJsVersion = filemtime(__DIR__ . '/../assets/js/register.js');
$uiIconsJsVersion = filemtime(__DIR__ . '/../assets/js/ui_icons.js');
$themeVersion = filemtime(__DIR__ . '/../assets/js/theme.js');
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo t("register"); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin">

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>?v=<?php echo $styleVersion; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset_path('icons/icon.svg'); ?>">

</head>
<body>

<div class="game-container auth-container register-auth-container">

    <div class="top-actions">

        <div class="language-pill">
            <a href="?lang=es">ES</a>
            <span>|</span>
            <a href="?lang=en">EN</a>
        </div>

    </div>

    <h1><?php echo t("register"); ?></h1>

    <p>
        <?php echo t("register_description"); ?>
    </p>

    <?php if (isset($_GET["logout"])): ?>

        <p style="color:#4caf50; text-align:center; font-weight:bold;">

            <?php echo t("session_closed_successfully"); ?>
        </p>

    <?php endif; ?>

    <form id="register-form" enctype="multipart/form-data">

        <div class="register-layout">
            <section class="register-panel register-required-panel">
                <h2><?php echo t("required_data"); ?></h2>
                <p class="register-role-note"><?php echo t("register_role_public_note"); ?></p>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name"><?php echo t("first_name"); ?> <span class="required-mark">*</span></label>
                <input type="text" id="first_name" name="first_name" maxlength="80" required>
            </div>

            <div class="form-group">
                        <label for="last_name"><?php echo t("last_name"); ?> <span class="required-mark">*</span></label>
                <input type="text" id="last_name" name="last_name" maxlength="80" required>
            </div>
        </div>

        <div class="form-group">
                    <label for="email"><?php echo t("email"); ?> <span class="required-mark">*</span></label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
                    <label for="password"><?php echo t("password"); ?> <span class="required-mark">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="10"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="password_confirmation">
                        <?php echo t("confirm_password"); ?> <span class="required-mark">*</span>
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        minlength="10"
                        autocomplete="new-password"
                    >
                    <ul id="password-policy-hint" class="password-policy-hint"></ul>
                </div>
            </section>

            <section class="register-panel register-optional-panel">
                <div class="optional-section-heading">
                    <span><?php echo current_lang() === "en" ? "Optional" : "Opcional"; ?></span>
                    <h2><?php echo current_lang() === "en" ? "Avatar and profile" : "Avatar y perfil"; ?></h2>
                    <p>
                        <?php echo current_lang() === "en"
                            ? "You can complete this now or edit it later from your profile."
                            : "Puedes completarlo ahora o editarlo luego desde tu perfil."; ?>
                    </p>
                </div>

                <div class="form-group">
                    <label><?php echo t("choose_avatar"); ?></label>
            <div class="avatar-picker" id="avatar-picker">
                <?php foreach (profile_avatar_options() as $key => $avatar): ?>
                    <label class="avatar-choice">
                        <input
                            type="radio"
                            name="avatar_key"
                            value="<?php echo htmlspecialchars($key); ?>"
                            <?php echo $key === "pulse" ? "checked" : ""; ?>
                        >
                        <span class="profile-avatar avatar-<?php echo htmlspecialchars($key); ?>">
                            <?php echo ui_icon($avatar["icon"] ?? "heart", "ui-icon profile-avatar-svg"); ?>
                        </span>
                        <em><?php echo htmlspecialchars($avatar["label"]); ?></em>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

                <div class="form-group">
                    <label for="avatar_file"><?php echo current_lang() === "en" ? "Upload your avatar" : "Subir tu avatar"; ?></label>
                    <input type="file" id="avatar_file" name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <p class="field-hint">
                        <?php echo current_lang() === "en"
                            ? "JPG, PNG, WebP or GIF. Maximum 2 MB. Square images look better."
                            : "JPG, PNG, WebP o GIF. Maximo 2 MB. Se ve mejor si la imagen es cuadrada."; ?>
                    </p>
                </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="country"><?php echo t("country"); ?></label>
                <select id="country" name="country">
                    <option value=""><?php echo t("select_country"); ?></option>
                    <?php foreach (app_countries() as $code => $country): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>">
                            <?php echo htmlspecialchars($country["flag"] . " " . $country[current_lang() === "en" ? "en" : "es"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="city"><?php echo t("city"); ?></label>
                <input type="text" id="city" name="city" maxlength="80">
            </div>
        </div>

        <div class="form-group">
            <label for="institution"><?php echo t("university_or_workplace"); ?></label>
            <input type="text" id="institution" name="institution" maxlength="140">
        </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="age"><?php echo t("age"); ?></label>
                        <input type="number" id="age" name="age" min="5" max="120">
                    </div>

                    <div class="form-group">
                        <label for="career"><?php echo t("career"); ?></label>
                        <input type="text" id="career" name="career" maxlength="140">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="education_level"><?php echo t("education_level"); ?></label>
                        <input type="text" id="education_level" name="education_level" maxlength="80">
                    </div>

                    <div class="form-group">
                        <label for="occupation"><?php echo t("occupation"); ?></label>
                        <input type="text" id="occupation" name="occupation" maxlength="120">
                    </div>
                </div>
            </section>
        </div>

        <button type="submit" class="primary-btn">
            <?php echo t("register"); ?>
        </button>

    </form>

    <p id="register-message"></p>

    <p style="margin-top:20px; text-align:center;">

        <?php echo t("already_account"); ?>
        <a href="<?php echo app_path('pages/login.php'); ?>">

            <?php echo t("login_link"); ?>
        </a>

    </p>

</div>

<script>
window.CSRF_TOKEN = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";
window.APP_BASE_PATH = "<?php echo htmlspecialchars(app_base_path(), ENT_QUOTES, 'UTF-8'); ?>";
const REGISTER_I18N = {
    missingName: "<?php echo t("register_missing_name"); ?>",
    passwordPolicy: "<?php echo t("register_password_policy_error"); ?>",
    passwordMismatch: "<?php echo t("register_password_mismatch"); ?>",
    loading: "<?php echo t("register_loading"); ?>",
    success: "<?php echo t("register_success"); ?>",
    failed: "<?php echo t("register_failed"); ?>",
    connectionError: "<?php echo t("connection_error"); ?>"
};
</script>
<script src="<?php echo asset_path('js/ui_icons.js'); ?>?v=<?php echo $uiIconsJsVersion; ?>"></script>
<script src="<?php echo asset_path('js/http.js'); ?>?v=<?php echo filemtime(__DIR__ . '/../assets/js/http.js'); ?>"></script>
<script src="<?php echo asset_path('js/password_policy.js'); ?>?v=<?php echo $passwordPolicyJsVersion; ?>"></script>
<script src="<?php echo asset_path('js/register.js'); ?>?v=<?php echo $registerJsVersion; ?>"></script>

<script src="<?php echo asset_path('js/theme.js'); ?>?v=<?php echo $themeVersion; ?>"></script>
</body>
</html>
