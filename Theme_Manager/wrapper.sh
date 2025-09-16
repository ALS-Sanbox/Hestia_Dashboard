#!/usr/bin/env php
<?php
/**
 * Hestia Theme Manager CLI Wrapper
 * This script provides a clean CLI interface for the theme manager
 */

// Check if we're running as root or have proper permissions
if (posix_getuid() !== 0) {
    echo "Error: This command requires root privileges. Use 'sudo hestia-theme' or run as root.\n";
    exit(1);
}

// Include the theme manager class
require_once '/usr/local/hestia/plugins/theme-manager/hestia_theme_manager.php';

// Initialize theme manager
try {
    $theme_manager = new HestiaThemeManager();
} catch (Exception $e) {
    echo "Error: Failed to initialize theme manager: " . $e->getMessage() . "\n";
    echo "Make sure the plugin is properly installed.\n";
    exit(1);
}

// Get command line arguments
$command = isset($argv[1]) ? $argv[1] : '';
$argument = isset($argv[2]) ? $argv[2] : '';

// Handle commands
switch ($command) {
    case 'install':
        echo "Installing Hestia Theme Manager...\n";
        $result = $theme_manager->install();
        if ($result) {
            echo "✅ Installation completed successfully\n";
            echo "Web interface available at: https://your-server:8083/theme-manager.php\n";
        } else {
            echo "❌ Installation failed\n";
            exit(1);
        }
        break;
        
    case 'uninstall':
        echo "Are you sure you want to uninstall the theme manager? This will restore original templates. (y/N): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirmation) === 'y' || strtolower($confirmation) === 'yes') {
            echo "Uninstalling Hestia Theme Manager...\n";
            $result = $theme_manager->uninstall();
            echo $result ? "✅ Uninstallation completed successfully\n" : "❌ Uninstallation failed\n";
        } else {
            echo "Uninstallation cancelled.\n";
        }
        break;
        
    case 'apply':
        if (empty($argument)) {
            echo "Usage: hestia-theme apply <theme_name>\n";
            echo "Available themes:\n";
            $themes = $theme_manager->getAvailableThemes();
            foreach ($themes as $theme) {
                echo "  - $theme\n";
            }
            echo "  - original (default)\n";
            exit(1);
        }
        
        echo "Applying theme '$argument'...\n";
        $result = $theme_manager->applyTheme($argument);
        if ($result) {
            echo "✅ Theme '$argument' applied successfully\n";
            echo "Clear your browser cache to see changes.\n";
        } else {
            echo "❌ Failed to apply theme '$argument'\n";
            exit(1);
        }
        break;
        
    case 'list':
        $themes = $theme_manager->getAvailableThemes();
        $current = $theme_manager->getCurrentTheme();
        
        echo "Available themes:\n";
        echo ($current === 'original' ? '🔵 ' : '⚪ ') . "original (default Hestia theme)\n";
        
        foreach ($themes as $theme) {
            $indicator = ($current === $theme) ? '🔵 ' : '⚪ ';
            echo "$indicator$theme\n";
        }
        
        if (empty($themes)) {
            echo "\nNo custom themes installed.\n";
            echo "To install themes, place them in: /usr/local/hestia/web/themes/\n";
        }
        break;
        
    case 'current':
        $current = $theme_manager->getCurrentTheme();
        echo "Current theme: $current\n";
        break;
        
    case 'status':
        $status = $theme_manager->getThemeStatus();
        echo "📋 Hestia Theme Manager Status\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Current theme: " . $status['current_theme'] . "\n";
        echo "Templates mode: " . ($status['is_symlink'] ? 'symlink 🔗' : 'directory 📁') . "\n";
        echo "Templates exist: " . ($status['templates_exists'] ? 'yes ✅' : 'no ❌') . "\n";
        
        if ($status['is_symlink']) {
            echo "Symlink target: " . $status['symlink_target'] . "\n";
            echo "Target valid: " . ($status['symlink_valid'] ? 'yes ✅' : 'no ❌') . "\n";
        }
        
        echo "Available themes: " . count($status['available_themes']) . "\n";
        
        if (!empty($status['available_themes'])) {
            echo "\nInstalled themes:\n";
            foreach ($status['available_themes'] as $theme) {
                echo "  - $theme\n";
            }
        }
        break;
        
    case 'backup':
        echo "Creating backup of current templates...\n";
        $backup_name = 'manual_backup_' . date('Y-m-d_H-i-s');
        // This would need to be implemented in the theme manager class
        echo "Backup functionality would be implemented here.\n";
        break;
        
    case 'web':
        // Open web interface (if possible)
        $hostname = gethostname();
        $port = '8083'; // Default Hestia port
        echo "Web interface available at:\n";
        echo "https://$hostname:$port/theme-manager.php\n";
        echo "https://your-server-ip:$port/theme-manager.php\n";
        break;
        
    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;
        
    default:
        echo "Hestia Theme Manager v1.0.0\n";
        echo "Usage: hestia-theme <command> [arguments]\n";
        echo "\nCommands:\n";
        echo "  install    Install the theme manager plugin\n";
        echo "  uninstall  Uninstall the theme manager plugin\n";
        echo "  apply      Apply a theme (usage: hestia-theme apply <theme_name>)\n";
        echo "  list       List all available themes\n";
        echo "  current    Show currently active theme\n";
        echo "  status     Show detailed system status\n";
        echo "  web        Show web interface URL\n";
        echo "  help       Show this help message\n";
        echo "\nFor more information, use: hestia-theme help\n";
        break;
}

function showHelp() {
    echo "🎨 Hestia Theme Manager CLI v1.0.0\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\nDESCRIPTION:\n";
    echo "  Manage themes for Hestia Control Panel using symlinks for efficient switching.\n";
    echo "  Allows you to install custom themes and switch between them without modifying\n";
    echo "  the original Hestia templates.\n";
    
    echo "\nUSAGE:\n";
    echo "  hestia-theme <command> [arguments]\n";
    
    echo "\nCOMMANDS:\n";
    echo "  install                     Install the theme manager plugin\n";
    echo "  uninstall                   Uninstall plugin and restore original templates\n";
    echo "  apply <theme_name>          Apply a specific theme\n";
    echo "  list                        List all available themes (shows current)\n";
    echo "  current                     Show currently active theme\n";
    echo "  status                      Show detailed system status and health\n";
    echo "  web                         Show web interface URL\n";
    echo "  help                        Show this detailed help\n";
    
    echo "\nEXAMPLES:\n";
    echo "  hestia-theme install        # Install the plugin\n";
    echo "  hestia-theme list           # See available themes\n";
    echo "  hestia-theme apply dark     # Switch to 'dark' theme\n";
    echo "  hestia-theme apply original # Switch back to original Hestia theme\n";
    echo "  hestia-theme status         # Check system health\n";
    
    echo "\nTHEME STRUCTURE:\n";
    echo "  Custom themes should be placed in:\n";
    echo "  /usr/local/hestia/web/themes/your-theme-name/\n";
    echo "  \n";
    echo "  The theme directory should contain the same structure as:\n";
    echo "  /usr/local/hestia/web/templates/\n";
    
    echo "\nHOW IT WORKS:\n";
    echo "  - Original templates are backed up during installation\n";
    echo "  - Custom themes use symlinks for efficient switching\n";
    echo "  - No modification of original Hestia files\n";
    echo "  - Easy restoration to original theme\n";
    
    echo "\nWEB INTERFACE:\n";
    echo "  Access the web interface at: https://your-server:8083/theme-manager.php\n";
    echo "  The web interface provides a visual way to manage themes.\n";
    
    echo "\nFILES AND DIRECTORIES:\n";
    echo "  Plugin:      /usr/local/hestia/plugins/theme-manager/\n";
    echo "  Themes:      /usr/local/hestia/web/themes/\n";
    echo "  Templates:   /usr/local/hestia/web/templates/ (managed)\n";
    echo "  Backups:     /usr/local/hestia/plugins/theme-manager/backups/\n";
    echo "  Config:      /usr/local/hestia/plugins/theme-manager/config/\n";
    echo "  Logs:        /usr/local/hestia/plugins/theme-manager/logs/\n";
    
    echo "\nNOTES:\n";
    echo "  - Requires root privileges\n";
    echo "  - Clear browser cache after theme changes\n";
    echo "  - Backup important customizations before switching themes\n";
    echo "  - Use 'original' theme name to restore default Hestia appearance\n";
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}
?>
