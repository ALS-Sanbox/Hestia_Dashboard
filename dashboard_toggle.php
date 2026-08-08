<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "DASHBOARD";
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

header("Content-Type: application/json");

if (!verify_csrf($_POST, true)) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "bad token"]);
    exit();
}

$action = $_POST["action"] ?? "alt_dashboard";

// user_theme can be set by the user themselves (editing their own account);
// every other action changes system-wide server settings and is admin-only
if ($action !== "user_theme" && $_SESSION["userContext"] !== "admin") {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "forbidden"]);
    exit();
}

if ($action === "alt_dashboard") {
    $value = ($_POST["enabled"] ?? "") === "true" ? "true" : "false";
    exec(HESTIA_CMD . "v-change-sys-config-value ALT_DASHBOARD " . quoteshellarg($value), $output, $return_var);
    unset($output);
    echo json_encode(["ok" => $return_var === 0, "value" => $value]);
    exit();
}

require_once "/usr/local/hestia/plugins/theme-manager/hestia_theme_manager.php";
$theme_manager = new HestiaThemeManager();

if ($action === "dashboard_theme") {
    $theme = $_POST["theme"] ?? "";
    $available = $theme_manager->getAvailableThemes();
    $available[] = "original";

    if ($theme === "" || !in_array($theme, $available, true)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "invalid dashboard theme"]);
        exit();
    }

    exec(HESTIA_CMD . "hestia-theme apply " . quoteshellarg($theme), $output, $return_var);
    unset($output);
    echo json_encode(["ok" => $return_var === 0, "value" => $theme]);
    exit();
}

if ($action === "css_theme") {
    $css_theme = $_POST["theme"] ?? "";
    $current_dashboard_theme = $theme_manager->getCurrentTheme();

    if ($css_theme === "" || !$theme_manager->isValidCssThemeForDashboard($css_theme, $current_dashboard_theme)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "css theme not valid for current dashboard theme"]);
        exit();
    }

    exec(HESTIA_CMD . "hestia-theme css " . quoteshellarg($css_theme), $output, $return_var);
    unset($output);
    echo json_encode(["ok" => $return_var === 0, "value" => $css_theme]);
    exit();
}

if ($action === "user_theme") {
    $target_user = $_POST["user"] ?? "";
    $css_theme = $_POST["theme"] ?? "";
    $effective_user = !empty($_SESSION["look"]) ? $_SESSION["look"] : $_SESSION["user"];

    if ($_SESSION["userContext"] !== "admin" && $target_user !== $effective_user) {
        http_response_code(403);
        echo json_encode(["ok" => false, "error" => "forbidden"]);
        exit();
    }

    if ($_SESSION["POLICY_USER_CHANGE_THEME"] === "no") {
        http_response_code(403);
        echo json_encode(["ok" => false, "error" => "theme changes disabled by policy"]);
        exit();
    }

    $current_dashboard_theme = $theme_manager->getCurrentTheme();

    if (
        $target_user === "" ||
        $css_theme === "" ||
        !$theme_manager->isValidCssThemeForDashboard($css_theme, $current_dashboard_theme)
    ) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "invalid user or css theme"]);
        exit();
    }

    exec(
        HESTIA_CMD . "v-change-user-css-theme " . quoteshellarg($target_user) . " " . quoteshellarg($css_theme),
        $output,
        $return_var,
    );
    unset($output);
    echo json_encode(["ok" => $return_var === 0, "value" => $css_theme]);
    exit();
}

http_response_code(400);
echo json_encode(["ok" => false, "error" => "unknown action"]);
