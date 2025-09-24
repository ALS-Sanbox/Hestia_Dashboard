<?php
/**
 * Hestia Theme Manager Plugin (Enhanced) - FIXED VERSION
 * Version: 2.0.2
 * Description: Allows switching between different UI themes for Hestia Control Panel using symlinks and CSS themes
 * Author: Custom Plugin
 */

class HestiaThemeManager {
    
    public $plugin_path;
    private $theme_path;
    public $backup_path;
    private $hestia_path;
    private $templates_path;
    private $css_themes_path;
    private $css_custom_themes_path;
    private $current_theme;
    private $current_css_theme;
    private $available_themes;

    // Define patched system files that need restoration
    private $patched_files = [
        '/usr/local/hestia/web/list/index.php',
        '/usr/local/hestia/web/inc/main.php',
        '/usr/local/hestia/web/login/index.php'
    ];
    
    public function __construct() {
        $this->plugin_path = '/usr/local/hestia/plugins/theme-manager';
        $this->backup_path = $this->plugin_path . '/backups';
        $this->hestia_path = '/usr/local/hestia';
        $this->theme_path  = '/usr/local/hestia/web/themes';
        $this->templates_path = '/usr/local/hestia/web/templates';
        $this->css_themes_path = '/usr/local/hestia/web/css/themes';
        $this->css_custom_themes_path = '/usr/local/hestia/web/css/themes/custom';
        $this->loadConfig();
    }
    
    /**
     * Install the theme manager plugin
     */
    public function install() {
        try {
            // Create plugin directories
            $this->createDirectories();
            
            // Create original templates backup
            $this->createOriginalTemplatesBackup();
            
            // Create config file
            $this->createConfigFile();
            
            // Create theme management interface
            $this->createThemeInterface();
            
            $this->log("Theme Manager Plugin installed successfully");
            return true;
            
        } catch (Exception $e) {
            $this->log("Installation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Uninstall the theme manager plugin
     */
    public function uninstall() {
        try {
            // Restore original templates
            $this->restoreOriginalTemplates();
            
            // Restore original patched files
            $this->restoreOriginalPatchedFiles();
            
            // Reset CSS theme to default
            $this->setCssTheme('default');
            
            $this->log("Theme Manager Plugin uninstalled successfully");
            return true;
            
        } catch (Exception $e) {
            $this->log("Uninstallation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Apply a complete theme (templates + CSS)
     */
    public function applyTheme($theme_name, $css_theme = null) {
        try {
            $this->log("Starting applyTheme: $theme_name" . ($css_theme ? " with CSS: $css_theme" : ""));
            
            if (!$this->isValidTheme($theme_name)) {
                throw new Exception("Invalid theme: " . $theme_name);
            }
            
            // Backup current templates before applying new theme
            $this->backupCurrentTemplates($theme_name . '_backup_' . date('Y-m-d_H-i-s'));
            
            if ($theme_name === 'original') {
                // Restore original templates
                $this->restoreOriginalTemplates();
            } else {
                // Apply theme using symlinks
                $this->applyThemeSymlinks($theme_name);
            }
            
            // Apply CSS theme if specified, or try to auto-detect
            if ($css_theme !== null) {
                $this->setCssTheme($css_theme);
            } else {
                // Try to auto-detect CSS theme from theme config
                $auto_css_theme = $this->getThemeCssTheme($theme_name);
                if ($auto_css_theme) {
                    $this->setCssTheme($auto_css_theme);
                }
            }
            
            // Update current theme in config
            $this->updateCurrentTheme($theme_name, $css_theme);
            
            $this->log("Theme '$theme_name' applied successfully" . ($css_theme ? " with CSS theme '$css_theme'" : ""));
            return true;
            
        } catch (Exception $e) {
            $this->log("Failed to apply theme '$theme_name': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set CSS theme for Hestia Control Panel
     */
    public function setCssTheme($css_theme) {
        try {
            // Update the theme in the Hestia configuration
            $config_file = '/usr/local/hestia/conf/hestia.conf';
            $temp_file = $config_file . '.tmp';
            
            $updated = false;
            
            if (file_exists($config_file)) {
                $lines = file($config_file, FILE_IGNORE_NEW_LINES);
                $output = [];
                
                foreach ($lines as $line) {
                    if (strpos($line, 'THEME=') === 0) {
                        $output[] = "THEME='$css_theme'";
                        $updated = true;
                    } else {
                        $output[] = $line;
                    }
                }
                
                if (!$updated) {
                    $output[] = "THEME='$css_theme'";
                }
                
                file_put_contents($temp_file, implode("\n", $output) . "\n");
                rename($temp_file, $config_file);
            } else {
                // Create new config file
                file_put_contents($config_file, "THEME='$css_theme'\n");
            }
            
            // Also set in session if we're in a web context
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['THEME'] = $css_theme;
                $_SESSION['userTheme'] = $css_theme;
            }
            
            $this->current_css_theme = $css_theme;
            $this->log("CSS theme set to: $css_theme");
            return true;
            
        } catch (Exception $e) {
            $this->log("Failed to set CSS theme '$css_theme': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available CSS themes
     */
    public function getAvailableCssThemes() {
        $css_themes = ['default'];
        
        // Get built-in themes
        if (is_dir($this->css_themes_path)) {
            $files = glob($this->css_themes_path . '/*.min.css');
            foreach ($files as $file) {
                $theme_name = basename($file, '.min.css');
                if ($theme_name !== 'default') {
                    $css_themes[] = $theme_name;
                }
            }
        }
        
        // Get custom themes
        if (is_dir($this->css_custom_themes_path)) {
            $files = array_merge(
                glob($this->css_custom_themes_path . '/*.css'),
                glob($this->css_custom_themes_path . '/*.min.css')
            );
            foreach ($files as $file) {
                $theme_name = basename($file, '.css');
                $theme_name = basename($theme_name, '.min');
                if (!in_array($theme_name, $css_themes)) {
                    $css_themes[] = $theme_name;
                }
            }
        }
        
        return $css_themes;
    }
    
    /**
     * Get current CSS theme
     */
    public function getCurrentCssTheme() {
        // Try to get from session first
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!empty($_SESSION["userTheme"])) {
                return $_SESSION["userTheme"];
            }
            if (!empty($_SESSION["THEME"])) {
                return $_SESSION["THEME"];
            }
        }

        // Try to get from Hestia config
        $config_file = '/usr/local/hestia/conf/hestia.conf';
        if (file_exists($config_file)) {
            $content = file_get_contents($config_file);
            if (preg_match("/^THEME='([^']+)'/m", $content, $matches)) {
                return $matches[1];
            }
        }

        return 'default';
    }

    /**
     * Get theme's recommended CSS theme from theme.json
     */
    private function getThemeCssTheme($theme_name) {
        if ($theme_name === 'original') {
            return 'default';
        }

        $theme_config_file = $this->theme_path . '/' . $theme_name . '/theme.json';
        if (file_exists($theme_config_file)) {
            $json_contents = file_get_contents($theme_config_file);
            $this->log("Contents of theme.json for $theme_name: " . $json_contents);
            $config = json_decode($json_contents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log("JSON decode error for theme $theme_name: " . json_last_error_msg());
                return null;
            }
            return $config['css_theme'] ?? null;
        }

        $this->log("theme.json not found at: $theme_config_file");
        return null;
    }
    
    /**
     * Create theme configuration file
     */
    public function createThemeConfig($theme_name, $config_data) {
        $theme_dir = $this->theme_path . '/' . $theme_name;
        $config_file = $theme_dir . '/theme.json';
        
        $default_config = [
            'name' => ucwords(str_replace(['-', '_'], ' ', $theme_name)),
            'description' => 'Custom theme for Hestia Control Panel',
            'version' => '1.0.0',
            'css_theme' => 'default',
            'author' => 'Custom',
            'created' => date('Y-m-d H:i:s')
        ];
        
        $config = array_merge($default_config, $config_data);
        
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        return true;
    }
    
    /**
     * Apply theme using symlinks - FIXED VERSION
     */
    private function applyThemeSymlinks($theme_name) {
        $theme_templates_path = $this->theme_path . '/' . $theme_name;
        
        $this->log("Applying theme symlinks for: $theme_name");
        $this->log("Theme path: $theme_templates_path");
        $this->log("Templates path: $this->templates_path");
        
        if (!is_dir($theme_templates_path)) {
            throw new Exception("Theme directory not found: $theme_templates_path");
        }
        
        // Check what currently exists at templates path
        if (file_exists($this->templates_path)) {
            if (is_link($this->templates_path)) {
                $this->log("Found existing symlink, removing");
                if (!unlink($this->templates_path)) {
                    throw new Exception("Failed to remove existing templates symlink");
                }
            } elseif (is_dir($this->templates_path)) {
                $this->log("Found existing directory, backing up and removing");
                // Move current templates to backup
                $backup_name = 'current_' . date('Y-m-d_H-i-s');
                $this->backupCurrentTemplates($backup_name);
                
                // Remove the directory
                if (!$this->removeDirectory($this->templates_path)) {
                    throw new Exception("Failed to remove existing templates directory");
                }
            } else {
                $this->log("Found existing file (not directory or symlink), removing");
                if (!unlink($this->templates_path)) {
                    throw new Exception("Failed to remove existing templates file");
                }
            }
        }
        
        // Verify the templates path doesn't exist before creating symlink
        if (file_exists($this->templates_path)) {
            throw new Exception("Templates path still exists after cleanup: " . $this->templates_path);
        }
        
        $this->log("Creating symlink from $this->templates_path to $theme_templates_path");
        
        // Create symlink to theme directory
        if (!symlink($theme_templates_path, $this->templates_path)) {
            $error = error_get_last();
            throw new Exception("Failed to create symlink: " . ($error['message'] ?? 'Unknown error'));
        }
        
        // Verify the symlink was created correctly
        if (!is_link($this->templates_path)) {
            throw new Exception("Symlink was not created properly");
        }
        
        $target = readlink($this->templates_path);
        if ($target !== $theme_templates_path) {
            throw new Exception("Symlink target mismatch. Expected: $theme_templates_path, Got: $target");
        }
        
        $this->log("Successfully created symlink from templates to theme: $theme_name");
    }
    
    /**
     * Get list of available themes
     */
    public function getAvailableThemes() {
        $themes = [];
        $themes_dir = $this->theme_path;
        
        if (is_dir($themes_dir)) {
            $dirs = scandir($themes_dir);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($themes_dir . '/' . $dir)) {
                    $themes[] = $dir;
                }
            }
        }
        
        return $themes;
    }
    
    /**
     * Get current active theme
     */
    public function getCurrentTheme() {
        return $this->current_theme;
    }
    
    /**
     * Get theme status and information
     */
    public function getThemeStatus() {
        $status = [];
        $status['current_theme'] = $this->current_theme;
        $status['current_css_theme'] = $this->getCurrentCssTheme();
        $status['is_symlink'] = is_link($this->templates_path);
        
        if ($status['is_symlink']) {
            $status['symlink_target'] = readlink($this->templates_path);
            $status['symlink_valid'] = is_dir($status['symlink_target']);
        }
        
        $status['available_themes'] = $this->getAvailableThemes();
        $status['available_css_themes'] = $this->getAvailableCssThemes();
        $status['templates_exists'] = file_exists($this->templates_path);
        
        return $status;
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        $dirs = [
            $this->plugin_path,
            $this->backup_path,
            $this->plugin_path . '/config',
            $this->plugin_path . '/logs',
            $this->theme_path,
            $this->css_custom_themes_path
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception("Failed to create directory: $dir");
                }
            }
        }
    }
    
    /**
     * Create backup of original templates directory
     */
    private function createOriginalTemplatesBackup() {
        $original_backup_path = $this->backup_path . '/original-templates';
        
        if (!is_dir($original_backup_path) && is_dir($this->templates_path)) {
            // Copy entire templates directory
            if (!$this->copyDirectory($this->templates_path, $original_backup_path)) {
                throw new Exception("Failed to create backup of original templates");
            }
            $this->log("Created backup of original templates directory");
        }
    }
    
    /**
     * Backup current templates directory
     */
    private function backupCurrentTemplates($backup_name) {
        $backup_path = $this->backup_path . '/' . $backup_name;
        
        if (!is_dir($backup_path)) {
            if (is_link($this->templates_path)) {
                // If it's a symlink, we just record what it pointed to
                $target = readlink($this->templates_path);
                if (!file_put_contents($backup_path . '_symlink_target.txt', $target)) {
                    $this->log("Warning: Failed to record symlink target for backup: $backup_name");
                } else {
                    $this->log("Recorded symlink target for backup: $backup_name");
                }
            } elseif (is_dir($this->templates_path)) {
                // Copy the actual directory
                if (!$this->copyDirectory($this->templates_path, $backup_path)) {
                    $this->log("Warning: Failed to create backup of current templates: $backup_name");
                } else {
                    $this->log("Created backup of current templates: $backup_name");
                }
            }
        }
    }
    
    /**
     * Restore original templates directory
     */
    private function restoreOriginalTemplates() {
        $original_backup_path = $this->backup_path . '/original-templates';
        
        if (is_dir($original_backup_path)) {
            // Remove current templates (symlink or directory)
            if (is_link($this->templates_path)) {
                unlink($this->templates_path);
                $this->log("Removed templates symlink");
            } elseif (is_dir($this->templates_path)) {
                $this->removeDirectory($this->templates_path);
                $this->log("Removed templates directory");
            }
            
            // Restore original templates
            if (!$this->copyDirectory($original_backup_path, $this->templates_path)) {
                throw new Exception("Failed to restore original templates directory");
            }
            $this->log("Restored original templates directory");
        } else {
            $this->log("Warning: Original templates backup not found");
        }
    }
    
    /**
     * Restore original patched files (integration with install script)
     */
    private function restoreOriginalPatchedFiles() {
        $patched_backup_path = $this->backup_path . '/original-files';
        
        if (is_dir($patched_backup_path)) {
            // Define mapping of backup files to target files
            $file_mapping = [
                'list_index.php' => '/usr/local/hestia/web/list/index.php',
                'main.php' => '/usr/local/hestia/web/inc/main.php',
                'login_index.php' => '/usr/local/hestia/web/login/index.php'
            ];
            
            foreach ($file_mapping as $backup_file => $target_file) {
                $backup_path = $patched_backup_path . '/' . $backup_file;
                
                if (file_exists($backup_path)) {
                    copy($backup_path, $target_file);
                    chmod($target_file, 0644);
                    $this->log("Restored original file: " . basename($target_file));
                }
            }
        }
    }
    
    /**
     * Copy directory recursively - IMPROVED VERSION
     */
    private function copyDirectory($source, $destination) {
        if (!is_dir($source)) {
            $this->log("Source directory does not exist: $source");
            return false;
        }
        
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                $this->log("Failed to create destination directory: $destination");
                return false;
            }
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                
                if ($item->isDir()) {
                    if (!is_dir($target) && !mkdir($target, 0755, true)) {
                        $this->log("Failed to create directory: $target");
                        return false;
                    }
                } else {
                    if (!copy($item, $target)) {
                        $this->log("Failed to copy file: $item to $target");
                        return false;
                    }
                    chmod($target, 0644);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->log("Error copying directory: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create config file
     */
    private function createConfigFile() {
        $config = [
            'current_theme' => 'original',
            'current_css_theme' => 'default',
            'installed_themes' => [],
            'installation_date' => date('Y-m-d H:i:s'),
            'version' => '1.1.0',
            'symlink_mode' => true
        ];
        
        $config_file = $this->plugin_path . '/config/config.json';
        if (!file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT))) {
            throw new Exception("Failed to create config file");
        }
    }
    
    /**
     * Load configuration
     */
    private function loadConfig() {
        $config_file = $this->plugin_path . '/config/config.json';
        
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->current_theme = $config['current_theme'] ?? 'original';
                $this->current_css_theme = $config['current_css_theme'] ?? 'default';
                $this->available_themes = $config['installed_themes'] ?? [];
            } else {
                $this->log("Error loading config: " . json_last_error_msg());
                $this->current_theme = 'original';
                $this->current_css_theme = 'default';
                $this->available_themes = [];
            }
        } else {
            $this->current_theme = 'original';
            $this->current_css_theme = 'default';
            $this->available_themes = [];
        }
    }
    
    /**
     * Update current theme in config
     */
    private function updateCurrentTheme($theme_name, $css_theme = null) {
        $config_file = $this->plugin_path . '/config/config.json';
        
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log("Error reading config for update: " . json_last_error_msg());
                return false;
            }
        } else {
            $config = [];
        }
        
        $config['current_theme'] = $theme_name;
        if ($css_theme !== null) {
            $config['current_css_theme'] = $css_theme;
        }
        $config['last_updated'] = date('Y-m-d H:i:s');
        
        if (!file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT))) {
            $this->log("Failed to update config file");
            return false;
        }
        
        $this->current_theme = $theme_name;
        if ($css_theme !== null) {
            $this->current_css_theme = $css_theme;
        }
        
        return true;
    }
    
    /**
     * Check if theme is valid
     */
    private function isValidTheme($theme_name) {
        if ($theme_name === 'original') {
            return true;
        }
        
        $theme_path = $this->theme_path . '/' . $theme_name;
        $is_valid = is_dir($theme_path);
        
        $this->log("Validating theme '$theme_name' at path '$theme_path': " . ($is_valid ? 'VALID' : 'INVALID'));
        
        return $is_valid;
    }
    
    /**
     * Remove directory recursively - IMPROVED VERSION
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return true; // Already removed or doesn't exist
        }
        
        try {
            $files = scandir($dir);
            if ($files === false) {
                $this->log("Failed to scan directory: $dir");
                return false;
            }
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filepath = $dir . '/' . $file;
                    if (is_dir($filepath)) {
                        if (!$this->removeDirectory($filepath)) {
                            return false;
                        }
                    } else {
                        if (!unlink($filepath)) {
                            $this->log("Failed to remove file: $filepath");
                            return false;
                        }
                    }
                }
            }
            
            if (!rmdir($dir)) {
                $this->log("Failed to remove directory: $dir");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->log("Error removing directory $dir: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create theme management interface - FIXED VERSION
     */
    private function createThemeInterface() {
        $interface_content = '<?php
/**
 * Hestia Theme Manager Web Interface (Enhanced) - FIXED VERSION
 */
require_once "/usr/local/hestia/plugins/theme-manager/hestia_theme_manager.php";

$theme_manager = new HestiaThemeManager();
$error_message = "";
$success_message = "";

// Handle POST requests
if ($_POST) {
    if (isset($_POST["apply_theme"])) {
        $theme_name = $_POST["theme_name"] ?? "";
        $css_theme = !empty($_POST["css_theme"]) ? $_POST["css_theme"] : null;
        
        if (empty($theme_name)) {
            $error_message = "No theme name provided";
        } else {
            $result = $theme_manager->applyTheme($theme_name, $css_theme);
            if ($result) {
                $success_message = "Theme \'" . htmlspecialchars($theme_name) . "\' applied successfully" . 
                                 ($css_theme ? " with CSS theme \'" . htmlspecialchars($css_theme) . "\'" : "");
            } else {
                $error_message = "Failed to apply theme \'" . htmlspecialchars($theme_name) . "\'";
            }
        }
    } elseif (isset($_POST["set_css_theme"])) {
        $css_theme = $_POST["css_theme"] ?? "";
        if (empty($css_theme)) {
            $error_message = "No CSS theme provided";
        } else {
            $result = $theme_manager->setCssTheme($css_theme);
            if ($result) {
                $success_message = "CSS theme \'" . htmlspecialchars($css_theme) . "\' applied successfully";
            } else {
                $error_message = "Failed to apply CSS theme \'" . htmlspecialchars($css_theme) . "\'";
            }
        }
    }
}

$current_theme = $theme_manager->getCurrentTheme();
$current_css_theme = $theme_manager->getCurrentCssTheme();
$available_themes = $theme_manager->getAvailableThemes();
$available_css_themes = $theme_manager->getAvailableCssThemes();
$status = $theme_manager->getThemeStatus();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hestia Theme Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .theme-item { padding: 15px; border: 1px solid #ddd; margin: 10px 0; border-radius: 5px; }
        .current { background-color: #e8f5e8; border-color: #28a745; }
        .status-info { background: #e3f2fd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .css-theme-section { background: #fff3e0; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status-detail { font-family: monospace; font-size: 12px; margin: 5px 0; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; margin: 5px; }
        button:hover { background: #005a8b; }
        button.css-theme-btn { background: #ff9800; }
        button.css-theme-btn:hover { background: #f57c00; }
        .message { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin: 2px; }
        .badge.symlink { background: #17a2b8; }
        .badge.directory { background: #28a745; }
        .badge.css-theme { background: #ff9800; }
        .info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 10px; align-items: center; }
        .theme-controls { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .theme-controls select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .section-header { color: #666; font-weight: bold; margin: 20px 0 10px 0; border-bottom: 2px solid #eee; padding-bottom: 5px; }
        .debug-info { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Hestia Theme Manager</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <div class="status-info">
            <h3>System Status</h3>
            <div class="info-grid">
                <strong>Template Theme:</strong>
                <span><?= htmlspecialchars($current_theme) ?> 
                    <?php if ($status["is_symlink"]): ?>
                        <span class="badge symlink">SYMLINK</span>
                    <?php else: ?>
                        <span class="badge directory">DIRECTORY</span>
                    <?php endif; ?>
                </span>
                
                <strong>CSS Theme:</strong>
                <span><?= htmlspecialchars($current_css_theme) ?> <span class="badge css-theme">CSS</span></span>
                
                <strong>Templates Status:</strong>
                <span><?= $status["templates_exists"] ? "✅ EXISTS" : "❌ MISSING" ?></span>
                
                <?php if ($status["is_symlink"]): ?>
                <strong>Symlink Target:</strong>
                <code><?= htmlspecialchars($status["symlink_target"]) ?></code>
                
                <strong>Target Valid:</strong>
                <span><?= $status["symlink_valid"] ? "✅ VALID" : "❌ BROKEN" ?></span>
                <?php endif; ?>
                
                <strong>Available Themes:</strong>
                <span><?= count($available_themes) ?> template themes, <?= count($available_css_themes) ?> CSS themes</span>
            </div>
            
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Templates Path: <?= htmlspecialchars($theme_manager->templates_path) ?><br>
                Theme Path: <?= htmlspecialchars($theme_manager->theme_path) ?><br>
                Plugin Path: <?= htmlspecialchars($theme_manager->plugin_path) ?>
            </div>
        </div>
        
        <!-- CSS Theme Quick Selection -->
        <div class="css-theme-section">
            <h3>🎨 Quick CSS Theme Selection</h3>
            <p>Change only the CSS theme without affecting templates:</p>
            <form method="post" style="display: inline-flex; align-items: center; gap: 10px;">
                <select name="css_theme">
                    <?php foreach ($available_css_themes as $css_theme): ?>
                        <option value="<?= htmlspecialchars($css_theme) ?>" <?= $css_theme === $current_css_theme ? "selected" : "" ?>>
                            <?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $css_theme))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="set_css_theme" class="css-theme-btn">Apply CSS Theme</button>
            </form>
        </div>
        
        <div class="section-header">Template Theme Management</div>
        
        <form method="post">
            <div class="theme-item <?= $current_theme === "original" ? "current" : "" ?>">
                <strong>🏠 Original Hestia Theme</strong>
                <br><small>Default Hestia Control Panel theme (uses real templates directory)</small>
                <?php if ($current_theme !== "original"): ?>
                    <div class="theme-controls">
                        <select name="css_theme">
                            <?php foreach ($available_css_themes as $css_theme): ?>
                                <option value="<?= htmlspecialchars($css_theme) ?>" <?= $css_theme === "default" ? "selected" : "" ?>>
                                    <?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $css_theme))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="theme_name" value="original">
                        <button type="submit" name="apply_theme" onclick="return confirm(\'Restore original Hestia theme?\')">
                            🔄 Restore Original Theme
                        </button>
                    </div>
                <?php else: ?>
                    <br><br><span class="badge">ACTIVE</span>
                <?php endif; ?>
            </div>
        </form>
        
        <?php foreach ($available_themes as $theme): ?>
            <?php 
            // Try to get theme config
            $theme_config_file = "/usr/local/hestia/web/themes/$theme/theme.json";
            $theme_config = file_exists($theme_config_file) ? json_decode(file_get_contents($theme_config_file), true) : [];
            if (json_last_error() !== JSON_ERROR_NONE) {
                $theme_config = [];
            }
            $recommended_css_theme = $theme_config["css_theme"] ?? "default";
            ?>
            <form method="post">
                <div class="theme-item <?= $current_theme === $theme ? "current" : "" ?>">
                    <strong>🎨 <?= htmlspecialchars($theme_config["name"] ?? ucwords(str_replace(["-", "_"], " ", $theme))) ?></strong>
                    <br><small>Custom theme: <code><?= htmlspecialchars($theme) ?></code> (uses symlink)</small>
                    <br><small>Location: <code>/usr/local/hestia/web/themes/<?= htmlspecialchars($theme) ?></code></small>
                    <?php if (!empty($theme_config["description"])): ?>
                        <br><small><?= htmlspecialchars($theme_config["description"]) ?></small>
                    <?php endif; ?>
                    
                    <?php if ($current_theme !== $theme): ?>
                        <div class="theme-controls">
                            <select name="css_theme">
                                <?php foreach ($available_css_themes as $css_theme): ?>
                                    <option value="<?= htmlspecialchars($css_theme) ?>" <?= $css_theme === $recommended_css_theme ? "selected" : "" ?>>
                                        <?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $css_theme))) ?>
                                        <?= $css_theme === $recommended_css_theme ? " (recommended)" : "" ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="theme_name" value="<?= htmlspecialchars($theme) ?>">
                            <button type="submit" name="apply_theme" onclick="return confirm(\'Apply <?= htmlspecialchars($theme) ?> theme?\')">
                                ✨ Apply Theme
                            </button>
                        </div>
                    <?php else: ?>
                        <br><br><span class="badge">ACTIVE</span>
                    <?php endif; ?>
                </div>
            </form>
        <?php endforeach; ?>
        
        <?php if (empty($available_themes)): ?>
            <div class="theme-item">
                <em>📁 No custom themes installed.</em>
                <br><br>
                To add themes:
                <ol>
                    <li>Create a directory in <code>/usr/local/hestia/web/themes/your-theme-name/</code></li>
                    <li>Copy the Hestia templates structure to your theme directory</li>
                    <li>Optionally create a <code>theme.json</code> config file with theme metadata</li>
                    <li>Modify the files to customize your theme</li>
                    <li>Apply the theme using this interface or CLI</li>
                </ol>
                <br>
                <strong>Theme Configuration Example (theme.json):</strong>
                <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px;">{
    "name": "My Custom Theme",
    "description": "A beautiful custom theme for Hestia",
    "version": "1.0.0",
    "css_theme": "dark",
    "author": "Your Name"
}</pre>
            </div>
        <?php endif; ?>
        
        <hr>
        <h3>📋 CLI Commands</h3>
        <div style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; font-family: monospace;">
            <div>hestia-theme list &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; # List available template themes</div>
            <div>hestia-theme list-css &nbsp; &nbsp; &nbsp; &nbsp; # List available CSS themes</div>
            <div>hestia-theme current &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; # Show current themes</div>
            <div>hestia-theme apply theme &nbsp; &nbsp; &nbsp; # Apply template theme</div>
            <div>hestia-theme apply theme css &nbsp; # Apply template + CSS theme</div>
            <div>hestia-theme css theme &nbsp; &nbsp; &nbsp; &nbsp; # Apply only CSS theme</div>
            <div>hestia-theme status &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; # Show detailed status</div>
        </div>
        
        <hr>
        <div style="font-size: 12px; color: #666; text-align: center; margin-top: 20px;">
            Hestia Theme Manager v2.0.1 | Template switching via symlinks + CSS theme support
        </div>
    </div>
</body>
</html>';
        
        if (!file_put_contents('/usr/local/hestia/web/theme-manager.php', $interface_content)) {
            throw new Exception("Failed to create theme interface file");
        }
        chmod('/usr/local/hestia/web/theme-manager.php', 0644);
    }
    
    /**
     * Log messages - ENHANCED VERSION
     */
    private function log($message) {
        $log_file = $this->plugin_path . '/logs/theme-manager.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message" . PHP_EOL;
        
        // Ensure logs directory exists
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also output to console if we're in CLI mode
        if (php_sapi_name() === 'cli') {
            echo $log_entry;
        }
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $theme_manager = new HestiaThemeManager();
    
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'install':
                $result = $theme_manager->install();
                echo $result ? "Installation completed successfully\n" : "Installation failed\n";
                break;
                
            case 'uninstall':
                $result = $theme_manager->uninstall();
                echo $result ? "Uninstallation completed successfully\n" : "Uninstallation failed\n";
                break;
                
            case 'apply':
                if (isset($argv[2])) {
                    $css_theme = isset($argv[3]) ? $argv[3] : null;
                    $result = $theme_manager->applyTheme($argv[2], $css_theme);
                    echo $result ? "Theme applied successfully\n" : "Failed to apply theme\n";
                } else {
                    echo "Usage: php hestia_theme_manager.php apply <theme_name> [css_theme]\n";
                }
                break;
                
            case 'css':
                if (isset($argv[2])) {
                    $result = $theme_manager->setCssTheme($argv[2]);
                    echo $result ? "CSS theme applied successfully\n" : "Failed to apply CSS theme\n";
                } else {
                    echo "Usage: php hestia_theme_manager.php css <css_theme_name>\n";
                }
                break;
                
            case 'list':
                $themes = $theme_manager->getAvailableThemes();
                echo "Available template themes:\n";
                foreach ($themes as $theme) {
                    echo "- $theme\n";
                }
                echo "- original (default)\n";
                break;
                
            case 'list-css':
                $css_themes = $theme_manager->getAvailableCssThemes();
                echo "Available CSS themes:\n";
                foreach ($css_themes as $css_theme) {
                    echo "- $css_theme\n";
                }
                break;
                
            case 'current':
                echo "Current template theme: " . $theme_manager->getCurrentTheme() . "\n";
                echo "Current CSS theme: " . $theme_manager->getCurrentCssTheme() . "\n";
                break;
                
            case 'status':
                $status = $theme_manager->getThemeStatus();
                echo "Theme Manager Status:\n";
                echo "- Template theme: " . $status['current_theme'] . "\n";
                echo "- CSS theme: " . $status['current_css_theme'] . "\n";
                echo "- Templates mode: " . ($status['is_symlink'] ? 'symlink' : 'directory') . "\n";
                if ($status['is_symlink']) {
                    echo "- Symlink target: " . $status['symlink_target'] . "\n";
                    echo "- Target valid: " . ($status['symlink_valid'] ? 'yes' : 'no') . "\n";
                }
                echo "- Available template themes: " . count($status['available_themes']) . "\n";
                echo "- Available CSS themes: " . count($status['available_css_themes']) . "\n";
                break;
                
            case 'debug':
                // New debug command to help troubleshoot issues
                $status = $theme_manager->getThemeStatus();
                echo "=== THEME MANAGER DEBUG INFO ===\n";
                echo "Plugin Path: " . $theme_manager->plugin_path . "\n";
                echo "Templates Path: " . $theme_manager->templates_path . "\n";
                echo "Theme Path: " . $theme_manager->theme_path . "\n";
                echo "Backup Path: " . $theme_manager->backup_path . "\n";
                echo "\nCurrent State:\n";
                echo "- Current Theme: " . $status['current_theme'] . "\n";
                echo "- Current CSS Theme: " . $status['current_css_theme'] . "\n";
                echo "- Templates Exists: " . ($status['templates_exists'] ? 'YES' : 'NO') . "\n";
                echo "- Is Symlink: " . ($status['is_symlink'] ? 'YES' : 'NO') . "\n";
                if ($status['is_symlink']) {
                    echo "- Symlink Target: " . $status['symlink_target'] . "\n";
                    echo "- Target Valid: " . ($status['symlink_valid'] ? 'YES' : 'NO') . "\n";
                }
                echo "\nDirectory Contents:\n";
                if (is_dir($theme_manager->theme_path)) {
                    $themes = scandir($theme_manager->theme_path);
                    echo "Themes directory contents:\n";
                    foreach ($themes as $item) {
                        if ($item !== '.' && $item !== '..') {
                            $path = $theme_manager->theme_path . '/' . $item;
                            echo "  - $item " . (is_dir($path) ? '[DIR]' : '[FILE]') . "\n";
                        }
                    }
                } else {
                    echo "Themes directory does not exist: " . $theme_manager->theme_path . "\n";
                }
                echo "\nPermissions:\n";
                echo "- Templates path perms: " . (file_exists($theme_manager->templates_path) ? decoct(fileperms($theme_manager->templates_path) & 0777) : 'N/A') . "\n";
                echo "- Theme path perms: " . (file_exists($theme_manager->theme_path) ? decoct(fileperms($theme_manager->theme_path) & 0777) : 'N/A') . "\n";
                break;
                
            default:
                echo "Usage: php hestia_theme_manager.php [install|uninstall|apply|css|list|list-css|current|status|debug]\n";
        }
    } else {
        echo "Hestia Theme Manager v2.0.1 (Enhanced with CSS Theme Support)\n";
        echo "Usage: php hestia_theme_manager.php [install|uninstall|apply|css|list|list-css|current|status|debug]\n";
        echo "\nCommands:\n";
        echo "  install              - Install the theme manager\n";
        echo "  uninstall            - Uninstall and restore original\n";
        echo "  apply <theme> [css]  - Apply template theme with optional CSS theme\n";
        echo "  css <theme>          - Apply only CSS theme\n";
        echo "  list                 - List available template themes\n";
        echo "  list-css             - List available CSS themes\n";
        echo "  current              - Show current active themes\n";
        echo "  status               - Show detailed system status\n";
        echo "  debug                - Show debug information\n";
    }
}

?>
