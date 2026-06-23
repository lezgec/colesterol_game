<?php
require_once __DIR__ . '/../config/countries.php';

if (!function_exists("render_user_menu")) {
    function render_user_menu(): void {
        if (!is_logged_in()) {
            return;
        }

        $role = current_user_role();
        $name = trim((string)($_SESSION["user_name"] ?? ""));
        $email = trim((string)($_SESSION["user_email"] ?? ""));
        $displayName = $name !== "" ? $name : ($email !== "" ? $email : "Usuario");
        $initial = strtoupper(substr($displayName, 0, 1));
        $lang = current_lang();
        $countryFlag = "";

        if (isset($_SESSION["user_id"])) {
            global $conn;

            if (!isset($conn) || !($conn instanceof mysqli)) {
                require __DIR__ . '/../config/db.php';
            }

            if (isset($conn) && $conn instanceof mysqli) {
                $stmt = $conn->prepare("SELECT country FROM users WHERE id = ? LIMIT 1");

                if ($stmt) {
                    $userId = (int)$_SESSION["user_id"];
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $country = country_display($row["country"] ?? "", $lang);
                    $countryFlag = $country["flag"] ?? "";
                }
            }
        }

        if ($role === "player") {
            $profileUrl = "/colesterol_game/pages/player_profile.php";
            $dashboardUrl = "/colesterol_game/pages/player_dashboard.php";
            $roleLabel = $lang === "en" ? "Player" : "Jugador";
        } else {
            $profileUrl = "/colesterol_game/pages/teacher_profile.php";
            $dashboardUrl = "/colesterol_game/pages/admin_dashboard.php";
            $roleLabel = $role === "super_admin"
                ? ($lang === "en" ? "Super admin" : "Superadmin")
                : ($lang === "en" ? "Teacher" : "Docente");
        }

        ?>
        <details class="user-menu">
            <summary>
                <span class="user-menu-avatar">
                    <?php echo htmlspecialchars($countryFlag !== "" ? $countryFlag : $initial); ?>
                </span>
                <span class="user-menu-copy">
                    <strong><?php echo htmlspecialchars($displayName); ?></strong>
                    <small><?php echo htmlspecialchars($roleLabel); ?></small>
                </span>
                <span class="user-menu-chevron" aria-hidden="true">v</span>
            </summary>

            <nav class="user-menu-panel" aria-label="<?php echo $lang === "en" ? "User menu" : "Menu de usuario"; ?>">
                <a href="<?php echo htmlspecialchars($dashboardUrl); ?>">
                    <?php echo $lang === "en" ? "Dashboard" : "Panel"; ?>
                </a>
                <a href="<?php echo htmlspecialchars($profileUrl); ?>">
                    <?php echo $lang === "en" ? "Profile and badges" : "Perfil e insignias"; ?>
                </a>
                <a href="/colesterol_game/pages/logout.php" class="user-menu-danger">
                    <?php echo t("logout"); ?>
                </a>
            </nav>
        </details>
        <?php
    }
}
?>
