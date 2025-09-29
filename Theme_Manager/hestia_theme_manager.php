<?php
/**
 * Hestia Theme Manager Plugin
 * Version: 2.0.3
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
	
	// 🔽 Safe accessors for private properties
		public function getThemePath() {
			return $this->theme_path;
		}

		public function getHestiaPath() {
			return $this->hestia_path;
		}

		public function getTemplatesPath() {
			return $this->templates_path;
		}

		public function getCssThemesPath() {
			return $this->css_themes_path;
		}

		public function getCssCustomThemesPath() {
			return $this->css_custom_themes_path;
		}
		
		public function getThemeNameFromSource($source){
			return $this->extractThemeNameFromSource($source);
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
	
	public function installTheme($source, $theme_name = null, $options = []) {
		try {
			$this->log("Starting theme installation from: $source");
			
			// Validate source exists
			if (!file_exists($source)) {
				throw new Exception("Source file or directory not found: $source");
			}
			
			// Determine theme name
			if (empty($theme_name)) {
				$theme_name = $this->extractThemeNameFromSource($source);
			}
			
			// Validate theme name
			if (!$this->isValidThemeName($theme_name)) {
				throw new Exception("Invalid theme name: $theme_name");
			}
			
			// Check if theme already exists
			$target_dir = $this->theme_path . '/' . $theme_name;
			if (is_dir($target_dir) && !($options['overwrite'] ?? false)) {
				throw new Exception("Theme '$theme_name' already exists. Use overwrite option to replace.");
			}
			
			// Create backup if overwriting
			if (is_dir($target_dir) && ($options['overwrite'] ?? false)) {
				$this->backupExistingTheme($theme_name);
			}
			
			// Install based on source type
			if (is_dir($source)) {
				$result = $this->installThemeFromDirectory($source, $theme_name, $options);
			} elseif (pathinfo($source, PATHINFO_EXTENSION) === 'zip') {
				$result = $this->installThemeFromZip($source, $theme_name, $options);
			} else {
				throw new Exception("Unsupported source type. Must be directory or .zip file");
			}
			
			if ($result) {
				// Install CSS theme if present
				$this->installThemeCssFiles($theme_name);
				
				// Update theme registry
				$this->registerInstalledTheme($theme_name);
				
				$this->log("Theme '$theme_name' installed successfully");
				return true;
			}
			
			return false;
			
		} catch (Exception $e) {
			$this->log("Failed to install theme: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Install theme from directory
	 */
	private function installThemeFromDirectory($source_dir, $theme_name, $options = []) {
		$target_dir = $this->theme_path . '/' . $theme_name;
		
		// Remove existing if overwriting
		if (is_dir($target_dir)) {
			$this->removeDirectory($target_dir);
		}
		
		// Copy theme directory
		if (!$this->copyDirectory($source_dir, $target_dir)) {
			throw new Exception("Failed to copy theme directory");
		}
		
		// Validate theme structure
		if (!$this->validateThemeStructure($target_dir)) {
			$this->removeDirectory($target_dir);
			throw new Exception("Invalid theme structure - missing required files");
		}
		
		// Set proper permissions
		$this->setThemePermissions($target_dir);
		
		return true;
	}

	/**
	 * Install theme from ZIP file
	 */
	private function installThemeFromZip($zip_path, $theme_name, $options = []) {
		if (!class_exists('ZipArchive')) {
			throw new Exception("ZipArchive class not available. Install php-zip extension.");
		}
		
		$zip = new ZipArchive();
		$result = $zip->open($zip_path);
		
		if ($result !== TRUE) {
			throw new Exception("Failed to open ZIP file: " . $this->getZipError($result));
		}
		
		$temp_dir = sys_get_temp_dir() . '/hestia_theme_' . uniqid();
		$target_dir = $this->theme_path . '/' . $theme_name;
		
		try {
			// Extract to temporary directory
			if (!$zip->extractTo($temp_dir)) {
				throw new Exception("Failed to extract ZIP file");
			}
			$zip->close();
			
			// Find theme root in extracted files
			$theme_root = $this->findThemeRootInExtraction($temp_dir);
			if (!$theme_root) {
				throw new Exception("Could not locate theme files in ZIP archive");
			}
			
			// Remove existing if overwriting
			if (is_dir($target_dir)) {
				$this->removeDirectory($target_dir);
			}
			
			// Move theme to final location
			if (!rename($theme_root, $target_dir)) {
				throw new Exception("Failed to move theme to final location");
			}
			
			// Validate theme structure
			if (!$this->validateThemeStructure($target_dir)) {
				$this->removeDirectory($target_dir);
				throw new Exception("Invalid theme structure - missing required files");
			}
			
			// Set proper permissions
			$this->setThemePermissions($target_dir);
			
			return true;
			
		} finally {
			// Clean up temporary directory
			if (is_dir($temp_dir)) {
				$this->removeDirectory($temp_dir);
			}
		}
	}

	/**
	 * Uninstall a theme
	 */
	public function uninstallTheme($theme_name, $options = []) {
		try {
			$this->log("Starting theme uninstallation: $theme_name");
			
			// Validate theme name
			if (!$this->isValidThemeName($theme_name)) {
				throw new Exception("Invalid theme name: $theme_name");
			}
			
			// Prevent uninstalling 'original' theme
			if ($theme_name === 'original') {
				throw new Exception("Cannot uninstall the original theme");
			}
			
			$theme_dir = $this->theme_path . '/' . $theme_name;
			
			// Check if theme exists
			if (!is_dir($theme_dir)) {
				throw new Exception("Theme '$theme_name' is not installed");
			}
			
			// Check if theme is currently active
			if ($this->getCurrentTheme() === $theme_name) {
				if ($options['force'] ?? false) {
					$this->log("Switching to original theme before uninstalling active theme");
					$this->applyTheme('original');
				} else {
					throw new Exception("Cannot uninstall active theme '$theme_name'. Switch to another theme first or use force option.");
				}
			}
			
			// Create backup if requested
			if ($options['backup'] ?? false) {
				$this->createThemeBackupBeforeUninstall($theme_name);
			}
			
			// Remove theme CSS files
			$this->removeThemeCssFiles($theme_name);
			
			// Remove theme directory
			if (!$this->removeDirectory($theme_dir)) {
				throw new Exception("Failed to remove theme directory");
			}
			
			// Unregister theme
			$this->unregisterInstalledTheme($theme_name);
			
			$this->log("Theme '$theme_name' uninstalled successfully");
			return true;
			
		} catch (Exception $e) {
			$this->log("Failed to uninstall theme '$theme_name': " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get list of installed themes with metadata
	 */
	public function getInstalledThemesWithMetadata() {
		$themes = [];
		$theme_dirs = $this->getAvailableThemes();
		
		foreach ($theme_dirs as $theme_name) {
			$theme_info = $this->getThemeMetadata($theme_name);
			$themes[$theme_name] = $theme_info;
		}
		
		return $themes;
	}

	/**
	 * Get theme metadata from theme.json
	 */
	public function getThemeMetadata($theme_name) {
		$theme_dir = $this->theme_path . '/' . $theme_name;
		$config_file = $theme_dir . '/theme.json';
		
		$default_info = [
			'name' => ucwords(str_replace(['-', '_'], ' ', $theme_name)),
			'description' => 'Custom Hestia theme',
			'version' => '1.0.0',
			'author' => 'Unknown',
			'css_theme' => 'default',
			'installed_date' => 'Unknown',
			'has_css' => false,
			'file_count' => 0,
			'size' => 0
		];
		
		if (file_exists($config_file)) {
			$config = json_decode(file_get_contents($config_file), true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$default_info = array_merge($default_info, $config);
			}
		}
		
		// Add calculated metadata
		if (is_dir($theme_dir)) {
			$default_info['has_css'] = file_exists($theme_dir . '/css/color_theme.css');
			$default_info['file_count'] = $this->countThemeFiles($theme_dir);
			$default_info['size'] = $this->getDirectorySize($theme_dir);
		}
		
		return $default_info;
	}

	/**
	 * Validate theme name
	 */
	private function isValidThemeName($name) {
		// Check for valid characters (alphanumeric, dash, underscore)
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
			return false;
		}
		
		// Check length
		if (strlen($name) < 2 || strlen($name) > 50) {
			return false;
		}
		
		// Reserved names
		$reserved = ['original', 'default', 'admin', 'root', 'system', 'hestia'];
		if (in_array(strtolower($name), $reserved)) {
			return false;
		}
		
		return true;
	}

	/**
	 * Extract theme name from source path
	 */
	private function extractThemeNameFromSource($source) {
		$basename = basename($source);
		
		// Remove .zip extension if present
		if (pathinfo($basename, PATHINFO_EXTENSION) === 'zip') {
			$basename = pathinfo($basename, PATHINFO_FILENAME);
		}
		
		// Clean up name
		$name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
		$name = trim($name, '_-');
		
		return $name;
	}

	/**
	 * Validate theme structure
	 */
	private function validateThemeStructure($theme_dir) {
		// Required files/directories
		$required_items = [
			'header.php',
			'footer.php',
			'includes',
			'pages'
		];
		
		foreach ($required_items as $item) {
			$path = $theme_dir . '/' . $item;
			if (!file_exists($path)) {
				$this->log("Missing required theme file/directory: $item");
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Install theme CSS files
	 */
	private function installThemeCssFiles($theme_name) {
		$theme_dir = $this->theme_path . '/' . $theme_name;
		$css_file = $theme_dir . '/css/color_theme.css';
		
		if (file_exists($css_file)) {
			$target_css_file = $this->css_custom_themes_path . '/' . $theme_name . '_color.css';
			
			// Ensure custom themes directory exists
			if (!is_dir($this->css_custom_themes_path)) {
				mkdir($this->css_custom_themes_path, 0755, true);
			}
			
			copy($css_file, $target_css_file);
			chmod($target_css_file, 0644);
			$this->log("Installed CSS file: {$theme_name}_color.css");
		}
	}

	/**
	 * Remove theme CSS files
	 */
	private function removeThemeCssFiles($theme_name) {
		$css_file = $this->css_custom_themes_path . '/' . $theme_name . '_color.css';
		
		if (file_exists($css_file)) {
			unlink($css_file);
			$this->log("Removed CSS file: {$theme_name}_color.css");
		}
	}

	/**
	 * Set proper permissions for theme files
	 */
	private function setThemePermissions($theme_dir) {
		// Set directory permissions
		chmod($theme_dir, 0755);
		
		// Set file permissions recursively
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($theme_dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);
		
		foreach ($iterator as $item) {
			if ($item->isDir()) {
				chmod($item, 0755);
			} else {
				chmod($item, 0644);
			}
		}
		
		// Set ownership
		$this->setOwnership($theme_dir, 'hestiaweb', 'hestiaweb');
	}

	/**
	 * Set ownership recursively
	 */
	private function setOwnership($path, $user, $group) {
		if (function_exists('chown') && function_exists('chgrp')) {
			if (is_dir($path)) {
				chown($path, $user);
				chgrp($path, $group);
				
				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
				);
				
				foreach ($iterator as $item) {
					chown($item, $user);
					chgrp($item, $group);
				}
			} else {
				chown($path, $user);
				chgrp($path, $group);
			}
		}
	}

	/**
	 * Register installed theme
	 */
	private function registerInstalledTheme($theme_name) {
		$config_file = $this->plugin_path . '/config/config.json';
		
		if (file_exists($config_file)) {
			$config = json_decode(file_get_contents($config_file), true);
		} else {
			$config = [];
		}
		
		if (!isset($config['installed_themes'])) {
			$config['installed_themes'] = [];
		}
		
		$config['installed_themes'][$theme_name] = [
			'installed_date' => date('Y-m-d H:i:s'),
			'version' => '1.0.0'
		];
		
		file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
	}

	/**
	 * Unregister installed theme
	 */
	private function unregisterInstalledTheme($theme_name) {
		$config_file = $this->plugin_path . '/config/config.json';
		
		if (file_exists($config_file)) {
			$config = json_decode(file_get_contents($config_file), true);
			if (isset($config['installed_themes'][$theme_name])) {
				unset($config['installed_themes'][$theme_name]);
				file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
			}
		}
	}

	/**
	 * Backup existing theme before overwrite
	 */
	private function backupExistingTheme($theme_name) {
		$theme_dir = $this->theme_path . '/' . $theme_name;
		$backup_name = $theme_name . '_backup_' . date('Y-m-d_H-i-s');
		$backup_path = $this->backup_path . '/' . $backup_name;
		
		if (is_dir($theme_dir)) {
			$this->copyDirectory($theme_dir, $backup_path);
			$this->log("Created backup of existing theme: $backup_name");
		}
	}

	/**
	 * Create theme backup before uninstall
	 */
	private function createThemeBackupBeforeUninstall($theme_name) {
		$theme_dir = $this->theme_path . '/' . $theme_name;
		$backup_name = $theme_name . '_uninstall_backup_' . date('Y-m-d_H-i-s');
		$backup_path = $this->backup_path . '/' . $backup_name;
		
		if (is_dir($theme_dir)) {
			$this->copyDirectory($theme_dir, $backup_path);
			$this->log("Created uninstall backup: $backup_name");
		}
	}

	/**
	 * Find theme root in extracted ZIP
	 */
	private function findThemeRootInExtraction($temp_dir) {
		// Look for theme structure in temp directory
		if ($this->validateThemeStructure($temp_dir)) {
			return $temp_dir;
		}
		
		// Look in subdirectories (common in GitHub zips)
		$dirs = glob($temp_dir . '/*', GLOB_ONLYDIR);
		foreach ($dirs as $dir) {
			if ($this->validateThemeStructure($dir)) {
				return $dir;
			}
		}
		
		return null;
	}

	/**
	 * Count files in theme directory
	 */
	private function countThemeFiles($theme_dir) {
		if (!is_dir($theme_dir)) {
			return 0;
		}
		
		$count = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($theme_dir, RecursiveDirectoryIterator::SKIP_DOTS)
		);
		
		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$count++;
			}
		}
		
		return $count;
	}

	/**
	 * Get directory size in bytes
	 */
	private function getDirectorySize($theme_dir) {
		if (!is_dir($theme_dir)) {
			return 0;
		}
		
		$size = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($theme_dir, RecursiveDirectoryIterator::SKIP_DOTS)
		);
		
		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$size += $file->getSize();
			}
		}
		
		return $size;
	}

	/**
	 * Format file size for display
	 */
	public function formatFileSize($bytes) {
		$units = ['B', 'KB', 'MB', 'GB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		
		$bytes /= pow(1024, $pow);
		
		return round($bytes, 2) . ' ' . $units[$pow];
	}

	/**
	 * Get ZIP error message
	 */
	private function getZipError($error_code) {
		$errors = [
			ZipArchive::ER_OK => 'No error',
			ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
			ZipArchive::ER_RENAME => 'Renaming temporary file failed',
			ZipArchive::ER_CLOSE => 'Closing zip archive failed',
			ZipArchive::ER_SEEK => 'Seek error',
			ZipArchive::ER_READ => 'Read error',
			ZipArchive::ER_WRITE => 'Write error',
			ZipArchive::ER_CRC => 'CRC error',
			ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
			ZipArchive::ER_NOENT => 'No such file',
			ZipArchive::ER_EXISTS => 'File already exists',
			ZipArchive::ER_OPEN => 'Can\'t open file',
			ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
			ZipArchive::ER_ZLIB => 'Zlib error',
			ZipArchive::ER_MEMORY => 'Memory allocation failure',
			ZipArchive::ER_CHANGED => 'Entry has been changed',
			ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
			ZipArchive::ER_EOF => 'Premature EOF',
			ZipArchive::ER_INVAL => 'Invalid argument',
			ZipArchive::ER_NOZIP => 'Not a zip archive',
			ZipArchive::ER_INTERNAL => 'Internal error',
			ZipArchive::ER_INCONS => 'Zip archive inconsistent',
			ZipArchive::ER_REMOVE => 'Can\'t remove file',
			ZipArchive::ER_DELETED => 'Entry has been deleted'
		];
		
		return $errors[$error_code] ?? "Unknown error ($error_code)";
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
     * Log messages
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
				
			case 'install-theme':
				if (isset($argv[2])) {
					$source = $argv[2];
					$theme_name = isset($argv[3]) ? $argv[3] : null;
					$overwrite = in_array('--overwrite', $argv) || in_array('-f', $argv);
					
					echo "Installing theme from: $source\n";
					if ($theme_name) {
						echo "Theme name: $theme_name\n";
					}
					if ($overwrite) {
						echo "Overwrite mode enabled\n";
					}
					
					$options = ['overwrite' => $overwrite];
					$result = $theme_manager->installTheme($source, $theme_name, $options);
					
					if ($result) {
						echo "✅ Theme installed successfully\n";
						if (!$theme_name) {
							$theme_name = $theme_manager->getThemeNameFromSource($source);
						}
						echo "Use 'hestia-theme apply $theme_name' to activate it\n";
					} else {
						echo "❌ Theme installation failed\n";
						exit(1);
					}
				} else {
					echo "Usage: php hestia_theme_manager.php install-theme <source> [theme_name] [--overwrite|-f]\n";
					echo "Examples:\n";
					echo "  install-theme /path/to/theme-directory my-theme\n";
					echo "  install-theme /path/to/theme.zip custom-theme --overwrite\n";
					echo "  install-theme /path/to/theme.zip (auto-detect name)\n";
					exit(1);
				}
				break;

			case 'uninstall-theme':
				if (isset($argv[2])) {
					$theme_name = $argv[2];
					$force = in_array('--force', $argv) || in_array('-f', $argv);
					$backup = in_array('--backup', $argv) || in_array('-b', $argv);
					
					echo "Uninstalling theme: $theme_name\n";
					if ($force) echo "Force mode enabled\n";
					if ($backup) echo "Backup mode enabled\n";
					
					// Confirm unless force mode
					if (!$force) {
						echo "Are you sure you want to uninstall theme '$theme_name'? (y/N): ";
						$handle = fopen("php://stdin", "r");
						$confirmation = trim(fgets($handle));
						fclose($handle);
						
						if (strtolower($confirmation) !== 'y' && strtolower($confirmation) !== 'yes') {
							echo "Uninstallation cancelled\n";
							exit(0);
						}
					}
					
					$options = ['force' => $force, 'backup' => $backup];
					$result = $theme_manager->uninstallTheme($theme_name, $options);
					
					if ($result) {
						echo "✅ Theme '$theme_name' uninstalled successfully\n";
					} else {
						echo "❌ Theme uninstallation failed\n";
						exit(1);
					}
				} else {
					echo "Usage: php hestia_theme_manager.php uninstall-theme <theme_name> [--force|-f] [--backup|-b]\n";
					echo "Examples:\n";
					echo "  uninstall-theme my-theme\n";
					echo "  uninstall-theme old-theme --force --backup\n";
					exit(1);
				}
				break;

		case 'list-installed':
			$themes = $theme_manager->getInstalledThemesWithMetadata();
			$current = $theme_manager->getCurrentTheme();
			
			echo "📦 Installed Themes with Metadata\n";
			echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
			
			// Show original theme
			$indicator = ($current === 'original') ? '🔵 ACTIVE' : '⚪';
			echo "$indicator original (Hestia Default Theme)\n";
			echo "    Description: Default Hestia Control Panel theme\n";
			echo "    Type: Built-in\n";
			echo "\n";
			
			if (empty($themes)) {
				echo "No custom themes installed.\n";
				echo "\nUse 'hestia-theme install-theme <source>' to install themes.\n";
			} else {
				foreach ($themes as $theme_name => $info) {
					$indicator = ($current === $theme_name) ? '🔵 ACTIVE' : '⚪';
					echo "$indicator $theme_name\n";
					echo "    Name: " . $info['name'] . "\n";
					echo "    Description: " . $info['description'] . "\n";
					echo "    Version: " . $info['version'] . "\n";
					echo "    Author: " . $info['author'] . "\n";
					echo "    Installed: " . ($info['installed_date'] ?? 'Unknown') . "\n";
					echo "    Files: " . $info['file_count'] . " files, " . $theme_manager->formatFileSize($info['size']) . "\n";
					echo "    CSS Theme: " . ($info['has_css'] ? $info['css_theme'] : 'None') . "\n";
					echo "\n";
				}
			}
			break;

		case 'theme-info':
			if (isset($argv[2])) {
				$theme_name = $argv[2];
				
				if ($theme_name === 'original') {
					echo "📋 Theme Information: original\n";
					echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
					echo "Name: Hestia Default Theme\n";
					echo "Type: Built-in\n";
					echo "Description: Default Hestia Control Panel theme\n";
					echo "Status: " . ($theme_manager->getCurrentTheme() === 'original' ? 'Active' : 'Available') . "\n";
				} else {
					$info = $theme_manager->getThemeMetadata($theme_name);
					$current = $theme_manager->getCurrentTheme();
					
					echo "📋 Theme Information: $theme_name\n";
					echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
					echo "Name: " . $info['name'] . "\n";
					echo "Description: " . $info['description'] . "\n";
					echo "Version: " . $info['version'] . "\n";
					echo "Author: " . $info['author'] . "\n";
					echo "CSS Theme: " . $info['css_theme'] . "\n";
					echo "Has Custom CSS: " . ($info['has_css'] ? 'Yes' : 'No') . "\n";
					echo "File Count: " . $info['file_count'] . " files\n";
					echo "Size: " . $theme_manager->formatFileSize($info['size']) . "\n";
					echo "Installed: " . ($info['installed_date'] ?? 'Unknown') . "\n";
					echo "Status: " . ($current === $theme_name ? 'Active' : 'Available') . "\n";
				}
			} else {
				echo "Usage: php hestia_theme_manager.php theme-info <theme_name>\n";
				echo "Example: theme-info my-custom-theme\n";
				exit(1);
			}
			break;

		case 'backup-theme':
			if (isset($argv[2])) {
				$theme_name = $argv[2];
				$backup_name = isset($argv[3]) ? $argv[3] : $theme_name . '_manual_backup_' . date('Y-m-d_H-i-s');
				
				echo "Creating backup of theme: $theme_name\n";
				
				if ($theme_name === 'original') {
					// Backup current templates
					$result = $theme_manager->backupCurrentTemplates($backup_name);
				} else {
					$theme_dir = $theme_manager->theme_path . '/' . $theme_name;
					if (!is_dir($theme_dir)) {
						echo "❌ Theme '$theme_name' not found\n";
						exit(1);
					}
					$backup_path = $theme_manager->backup_path . '/' . $backup_name;
					$result = $theme_manager->copyDirectory($theme_dir, $backup_path);
				}
				
				if ($result) {
					echo "✅ Theme backup created: $backup_name\n";
					echo "Backup location: " . $theme_manager->backup_path . "/$backup_name\n";
				} else {
					echo "❌ Backup creation failed\n";
					exit(1);
				}
			} else {
				echo "Usage: php hestia_theme_manager.php backup-theme <theme_name> [backup_name]\n";
				echo "Examples:\n";
				echo "  backup-theme my-theme\n";
				echo "  backup-theme original my-original-backup\n";
				exit(1);
			}
			break;

		case 'restore-backup':
			if (isset($argv[2])) {
				$backup_name = $argv[2];
				$backup_path = $theme_manager->backup_path . '/' . $backup_name;
				
				if (!is_dir($backup_path)) {
					echo "❌ Backup '$backup_name' not found\n";
					exit(1);
				}
				
				echo "Available backups:\n";
				$backups = glob($theme_manager->backup_path . '/*');
				foreach ($backups as $backup) {
					$name = basename($backup);
					echo "  - $name\n";
				}
				echo "\nThis feature would restore a backup. Implementation depends on backup type.\n";
				echo "Manual restoration: copy from $backup_path to theme directory\n";
			} else {
				echo "Usage: php hestia_theme_manager.php restore-backup <backup_name>\n";
				echo "List backups with: ls " . $theme_manager->backup_path . "/\n";
				exit(1);
			}
			break;

		case 'list-backups':
			$backup_dir = $theme_manager->backup_path;
			if (!is_dir($backup_dir)) {
				echo "No backup directory found\n";
				exit(1);
			}
			
			$backups = glob($backup_dir . '/*');
			if (empty($backups)) {
				echo "No backups found\n";
			} else {
				echo "📦 Available Backups\n";
				echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
				foreach ($backups as $backup) {
					if (is_dir($backup)) {
						$name = basename($backup);
						$size = $theme_manager->formatFileSize($theme_manager->getDirectorySize($backup));
						$date = date('Y-m-d H:i:s', filemtime($backup));
						echo "$name\n";
						echo "    Created: $date\n";
						echo "    Size: $size\n";
						echo "\n";
					}
				}
			}
			break;

		case 'validate-theme':
			if (isset($argv[2])) {
				$theme_name = $argv[2];
				
				if ($theme_name === 'original') {
					echo "✅ Original theme is always valid\n";
				} else {
					$theme_dir = $theme_manager->getThemePath() . '/' . $theme_name;
					
					if (!is_dir($theme_dir)) {
						echo "❌ Theme directory not found: $theme_dir\n";
						exit(1);
					}
					
					echo "🔍 Validating theme: $theme_name\n";
					echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
					
					$valid = true;
					$required_items = [
						'header.php' => 'file',
						'footer.php' => 'file',
						'includes' => 'directory',
						'pages' => 'directory'
					];
					
					foreach ($required_items as $item => $type) {
						$path = $theme_dir . '/' . $item;
						if (file_exists($path)) {
							$actual_type = is_dir($path) ? 'directory' : 'file';
							if ($actual_type === $type) {
								echo "✅ $item ($type)\n";
							} else {
								echo "❌ $item (expected $type, found $actual_type)\n";
								$valid = false;
							}
						} else {
							echo "❌ $item (missing)\n";
							$valid = false;
						}
					}
					
					// Check for theme.json
					$theme_json = $theme_dir . '/theme.json';
					if (file_exists($theme_json)) {
						$json_valid = json_decode(file_get_contents($theme_json), true) !== null;
						echo ($json_valid ? "✅" : "⚠️") . " theme.json (" . ($json_valid ? "valid" : "invalid JSON") . ")\n";
					} else {
						echo "⚠️ theme.json (optional, not found)\n";
					}
					
					// Check for CSS
					$css_file = $theme_dir . '/css/color_theme.css';
					if (file_exists($css_file)) {
						echo "✅ css/color_theme.css (found)\n";
					} else {
						echo "⚠️ css/color_theme.css (optional, not found)\n";
					}
					
					echo "\n";
					if ($valid) {
						echo "✅ Theme validation passed\n";
					} else {
						echo "❌ Theme validation failed - missing required files\n";
						exit(1);
					}
				}
			} else {
				echo "Usage: php hestia_theme_manager.php validate-theme <theme_name>\n";
				echo "Example: validate-theme my-custom-theme\n";
				exit(1);
			}
			break;
	
            case 'debug':
                // New debug command to help troubleshoot issues
                $status = $theme_manager->getThemeStatus();
                echo "=== THEME MANAGER DEBUG INFO ===\n";
                echo "Plugin Path: " . $theme_manager->plugin_path . "\n";
                echo "Templates Path: " . $theme_manager->getTemplatesPath() . "\n";
                echo "Theme Path: " . $theme_manager->getThemePath() . "\n";
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
                if (is_dir($theme_manager->getThemePath())) {
                    $themes = scandir($theme_manager->getThemePath());
                    echo "Themes directory contents:\n";
                    foreach ($themes as $item) {
                        if ($item !== '.' && $item !== '..') {
                            $path = $theme_manager->getThemePath() . '/' . $item;
                            echo "  - $item " . (is_dir($path) ? '[DIR]' : '[FILE]') . "\n";
                        }
                    }
                } else {
                    echo "Themes directory does not exist: " . $theme_manager->getThemePath() . "\n";
                }
                echo "\nPermissions:\n";
                echo "- Templates path perms: " . (file_exists($theme_manager->getTemplatesPath()) ? decoct(fileperms($theme_manager->getTemplatesPath()) & 0777) : 'N/A') . "\n";
                echo "- Theme path perms: " . (file_exists($theme_manager->getThemePath()) ? decoct(fileperms($theme_manager->getThemePath()) & 0777) : 'N/A') . "\n";
                break;
                
            default:
                echo "Usage: php hestia_theme_manager.php [install|uninstall|apply|css|list|list-css|current|status|debug]\n";
        }
    } else {
        echo "Hestia Theme Manager v2.0.1 (Enhanced with Theme Installation)\n";
		echo "Usage: php hestia_theme_manager.php [command] [arguments]\n";
		echo "\nCore Commands:\n";
		echo "  install              - Install the theme manager plugin\n";
		echo "  uninstall            - Uninstall plugin and restore original\n";
		echo "  apply <theme> [css]  - Apply template theme with optional CSS theme\n";
		echo "  css <theme>          - Apply only CSS theme\n";
		echo "  list                 - List available template themes\n";
		echo "  list-css             - List available CSS themes\n";
		echo "  current              - Show current active themes\n";
		echo "  status               - Show detailed system status\n";
		echo "  debug                - Show debug information\n";
		echo "\nTheme Management:\n";
		echo "  install-theme <source> [name] [--overwrite]  - Install theme from ZIP/directory\n";
		echo "  uninstall-theme <name> [--force] [--backup] - Uninstall a theme\n";
		echo "  list-installed       - List installed themes with metadata\n";
		echo "  theme-info <name>    - Show detailed theme information\n";
		echo "  validate-theme <name> - Validate theme structure\n";
		echo "\nBackup Management:\n";
		echo "  backup-theme <name> [backup_name]  - Create theme backup\n";
		echo "  list-backups         - List available backups\n";
		echo "  restore-backup <name> - Restore from backup\n";
		echo "\nExamples:\n";
		echo "  hestia-theme install-theme /path/to/theme.zip my-theme\n";
		echo "  hestia-theme install-theme /path/to/theme-dir --overwrite\n";
		echo "  hestia-theme uninstall-theme old-theme --backup\n";
		echo "  hestia-theme list-installed\n";
		echo "  hestia-theme validate-theme my-theme\n";
    }
}

?>
