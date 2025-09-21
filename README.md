# This nightly build 9/21/2025 V2.0.1
Changes Made:
1. Main Plugin File (hestia_theme_manager.php)

Removed the entire createThemeInterface() method that generated the HTML web interface
Updated the install() method to skip creating the web interface
The plugin now operates purely through CLI commands

# **Installation Instructions**

1. Download and extract the Theme_Manager folder

   **ZIP version:**
   ```bash
   wget https://github.com/ALS-Sanbox/Hestia_Dashboard/releases/download/v2.0.0/Theme_Manager.zip
   unzip Theme_Manager.zip
   ```
   
   **TAR.GZ version:**
   ```bash
   wget https://github.com/ALS-Sanbox/Hestia_Dashboard/releases/download/v2.0.0/Theme_Manager.tar.gz
   tar -xzf Theme_Manager.tar.gz
   ```

2. Enter the extracted folder:
   ```bash
   cd Theme_Manager
   ```

3. Run the installation script:
   ```bash
   bash install.sh
   ```

4. Set Glass Theme as active:
   ```bash
   hestia-theme apply glass_theme
   ```

## **What's Next?**

After installation, you can run `hestia-theme status` to see the current theme status and `hestia-theme list` to view currently available themes.

### Usage
```bash
php hestia_theme_manager.php [install|uninstall|apply|css|list|list-css|current|status]
```

### Commands:
- `install` - Install the theme manager
- `uninstall` - Uninstall and restore original
- `apply <theme> [css]` - Apply template theme with optional CSS theme
- `css <theme>` - Apply only CSS theme
- `list` - List available template themes
- `list-css` - List available CSS themes
- `current` - Show current active themes
- `status` - Show detailed system status

### To Uninstall
```bash 
bash uninstall.sh
```

## **Theme Creation**

1. Make a copy of the glass theme
2. Make changes as desired
3. **Important:** I separated the CSS into two files. The one in the theme folder controls everything except the color settings. I created a separate CSS file and placed it in the `/usr/local/hestia/css/themes/custom` folder and use that filename in the theme.json file to load the color settings. This is done to allow for different color themes.

## **Installation Script Features**

### 1. Patch File Handling
- Creates backups of original Hestia files before overwriting them
- Applies patches:
  - `patch_files/list_index.php` → `/usr/local/hestia/web/list/index.php`
  - `patch_files/main.php` → `/usr/local/hestia/web/inc/main.php`
  - `patch_files/login_index.php` → `/usr/local/hestia/web/login/index.php`

### 2. Dashboard Setup
- Creates `/usr/local/hestia/web/list/dashboard/` directory
- Copies `dashboard_index.php` to `/usr/local/hestia/web/list/dashboard/index.php`
- Copies `glass_color_theme.css` to `/usr/local/hestia/web/css/themes/custom/`

### 3. File Verification
- Added `verify_patch_files()` function to ensure all required files exist before installation
- Checks for the required directory structure

### 4. Enhanced Backup System
- Creates `$PLUGIN_DIR/backups/original-files/` for storing original patched files
- Maintains separate backups for theme files and patched system files

## **Uninstallation Script Features**

### 1. Original File Restoration
- `restore_original_patched_files()` function restores the backed-up original files
- Properly restores file permissions and ownership

### 2. Dashboard Cleanup
- Removes `/usr/local/hestia/web/list/dashboard/` directory
- Removes custom CSS theme files
- Cleans up empty directories

### 3. Comprehensive Restoration
- Restores both theme files and patched system files
- Maintains the existing theme restoration functionality

## **File Structure**
```
Theme_Manager/
├── install.sh (modified)
├── uninstall.sh (modified)
├── patch_files/
│   ├── list_index.php
│   ├── main.php
│   └── login_index.php
├── dashboard_index.php
├── glass_color_theme.css
├── hestia_theme_manager.php
└── themes/ (optional)
```

## **Work In Progress (WIP)**

1. Add the ability to change the dashboard (skin) via GUI
2. Add the ability to add themes through GUI
