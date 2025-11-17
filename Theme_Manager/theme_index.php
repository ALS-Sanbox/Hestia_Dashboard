<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "THEMES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Include theme manager
require_once "/usr/local/hestia/plugins/theme-manager/hestia_theme_manager.php";

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize error handling like Hestia
if (!isset($_SESSION["error_msg"])) {
    $_SESSION["error_msg"] = "";
}
if (!isset($_SESSION["ok_msg"])) {
    $_SESSION["ok_msg"] = "";
}

$theme_manager = new HestiaThemeManager();

// Get logged-in user
$user = $_SESSION['user'] ?? null;
if (!$user) {
    die("No user logged in.");
}

// Get current user (in Hestia context, this would be from session)
$v_username = $user; // Use the logged-in user

// Enable error logging for debugging
error_log("=== Theme Manager Request ===");
error_log("POST data: " . print_r($_POST, true));

// Handle POST requests following Hestia pattern
if ($_POST && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear previous messages
    $_SESSION["error_msg"] = "";
    $_SESSION["ok_msg"] = "";
    
	// Update Dashboard theme only (quick change)
	if (isset($_POST["set_dashboard_theme"]) && empty($_SESSION["error_msg"])) {
		$new_dashboard_theme = $_POST["dashboard_theme"] ?? "";

		error_log("Applying Dashboard theme only: $new_dashboard_theme");

		if (empty($new_dashboard_theme)) {
			$_SESSION["error_msg"] = "Please select a Dashboard theme";
			error_log("Error: Empty Dashboard theme");
		} else {
			$current_theme = $theme_manager->getCurrentTheme();
			error_log("Current Dashboard theme: $current_theme");

			if ($new_dashboard_theme !== $current_theme) {
				try {
					error_log("Calling applyTheme via exec...");

					// Escape only for shell command
					$escaped_theme = escapeshellarg($new_dashboard_theme);
					$cmd = HESTIA_CMD . "hestia-theme apply $escaped_theme";

					exec($cmd . " 2>&1", $output, $result);
					error_log("setDashboardTheme result: " . ($result === 0 ? 'SUCCESS' : 'FAILED'));
					error_log("Command output: " . implode("\n", $output));

					if ($result === 0) {
						$_SESSION["DASHBOARD"] = $new_dashboard_theme;
						$_SESSION["ok_msg"] = "Dashboard theme applied successfully: $new_dashboard_theme";

						error_log("Session updated successfully");
						header("Location: /list/themes/");
						exit;
					} else {
						$error_msg = "Failed to apply Dashboard theme. Check theme manager logs for details.";
						$_SESSION["error_msg"] = $error_msg;
						error_log("Error: $error_msg");
					}
				} catch (Exception $e) {
					$error_msg = "Error applying Dashboard theme: " . $e->getMessage();
					$_SESSION["error_msg"] = $error_msg;
					error_log("Exception: $error_msg");
					error_log("Stack trace: " . $e->getTraceAsString());
				}
			} else {
				$_SESSION["ok_msg"] = "Dashboard theme is already active";
				error_log("Dashboard theme unchanged - already active");
			}
		}
	}
    // Update CSS theme only (quick change)
    elseif (isset($_POST["set_css_theme"]) && empty($_SESSION["error_msg"])) {
        $new_css_theme = $_POST["css_theme"] ?? "";
        
        error_log("Applying CSS theme only: $new_css_theme");
        
        // Validate input
        if (empty($new_css_theme)) {
            $_SESSION["error_msg"] = "Please select a CSS theme";
            error_log("Error: Empty CSS theme");
        } else {
            $current_css_theme = $theme_manager->getCurrentCssTheme();
            
            error_log("Current CSS theme: $current_css_theme");
            
            if ($new_css_theme !== $current_css_theme) {
                try {
                    error_log("Calling setCssTheme method...");
                    
                    // Use the theme manager's setCssTheme method
                    $result = $theme_manager->setCssTheme($new_css_theme);
                    
                    error_log("setCssTheme result: " . ($result ? 'SUCCESS' : 'FAILED'));
                    
                    if ($result) {
                        // Update session variables
                        $_SESSION["userCssTheme"] = $new_css_theme;
                        $_SESSION["THEME"] = $new_css_theme;
                        $_SESSION["ok_msg"] = "CSS theme applied successfully: $new_css_theme";
                        
                        error_log("Session updated successfully");
                        
                        // Redirect to refresh with new CSS theme
                        header("Location: /list/themes/");
                        exit;
                    } else {
                        $error_msg = "Failed to apply CSS theme. Check theme manager logs for details.";
                        $_SESSION["error_msg"] = $error_msg;
                        error_log("Error: $error_msg");
                    }
                } catch (Exception $e) {
                    $error_msg = "Error applying CSS theme: " . $e->getMessage();
                    $_SESSION["error_msg"] = $error_msg;
                    error_log("Exception: $error_msg");
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            } else {
                $_SESSION["ok_msg"] = "CSS theme is already active";
                error_log("CSS theme unchanged - already active");
            }
        }
    }
}

// Get current themes and available options
try {
    $current_theme = $theme_manager->getCurrentTheme();
    $current_css_theme = $theme_manager->getCurrentCssTheme();
    $available_themes = $theme_manager->getAvailableThemes();
    $available_css_themes = $theme_manager->getAvailableCssThemes();
    $status = $theme_manager->getThemeStatus();
    
    error_log("Loaded themes - Current: $current_theme, CSS: $current_css_theme");
    error_log("Available themes: " . implode(", ", $available_themes));
    error_log("Available CSS themes: " . implode(", ", $available_css_themes));
    
    // Ensure 'original' is always in the list
    if (!in_array('original', $available_themes)) {
        array_unshift($available_themes, 'original');
    }
    
    // Sort themes alphabetically
    sort($available_themes);
    sort($available_css_themes);
    
    // Update status with current info
    $status["current_theme"] = $current_theme;
    $status["current_css_theme"] = $current_css_theme;
    
} catch (Exception $e) {
    $error_msg = "Error loading theme information: " . $e->getMessage();
    $_SESSION["error_msg"] = $error_msg;
    error_log("Exception loading themes: $error_msg");
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $current_theme = 'original';
    $current_css_theme = 'default';
    $available_themes = ['original'];
    $available_css_themes = ['default'];
    $status = [];
}

/*/ Check user permissions for theme changes
if (!isset($_SESSION["POLICY_USER_CHANGE_THEME"])) {
    // Default to allowing theme changes for admins
    $_SESSION["POLICY_USER_CHANGE_THEME"] = ($_SESSION['userContext'] === 'admin') ? 'yes' : 'no';
}*/

error_log("=== End Theme Manager Request ===");

// Render page
render_page($user, $TAB, "list_themes");
?>
