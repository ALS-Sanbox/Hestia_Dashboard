<?php
use function Hestiacp\quoteshellarg\quoteshellarg;
$TAB = "DASHBOARD";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Get logged-in user
$user = $_SESSION['user'] ?? null;
if (!$user) {
    die("No user logged in.");
}

// Data
exec(HESTIA_CMD . "v-list-user-stats $user json", $output, $return_var);
$panel[$user] = json_decode(implode("", $output), true);
unset($output);

// Render page
render_page($user, $template, "list_dashboard");
?>
