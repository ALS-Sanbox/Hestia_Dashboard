# Hestia Theme Manager - Installation & Uninstallation Guide

## **Installation Script Features:**

### 1. Patch File Handling
- Creates backups of original Hestia files before overwriting them
- **Apply Patches:**
  - `patch_files/web_index.php` → `/usr/local/hestia/web/index.php`
  - `patch_files/list_index.php` → `/usr/local/hestia/web/list/index.php`
  - `patch_files/main.php` → `/usr/local/hestia/web/inc/main.php`
  - `patch_files/login_index.php` → `/usr/local/hestia/web/login/index.php`
  - `patch_files/edit_server.php` → `/usr/local/hestia/web/templates/pages/edit_server.php`
  - `patch_files/panel.php` → `/usr/local/hestia/web/templates/includes/panel.php`

### 2. Dashboard Setup
- Creates `/usr/local/hestia/web/list/dashboard/` directory
- Copies `dashboard_index.php` to `/usr/local/hestia/web/list/dashboard/index.php`
- Sets proper permissions and ownership

### 3. Theme Interface Setup
- Creates `/usr/local/hestia/web/list/theme/` directory
- Copies `theme_index.php` to `/usr/local/hestia/web/list/theme/index.php`
- Sets proper permissions and ownership

### 4. List Themes Page
- Copies `list_themes.php` to `/usr/local/hestia/web/list/list_themes.php`
- Provides theme management interface accessible from the main list menu

### 5. CSS Theme Installation
- Installs custom CSS themes from `themes/*/css/` directories
- Copies CSS files to `/usr/local/hestia/web/css/themes/custom/`
- Skips `style.css` and `color_theme.css` files
- Automatically renames theme CSS files (e.g., `dark_theme.css` → `dark_color.css`)

### 6. File Verification
- Added `verify_patch_files()` function to ensure all required files exist before installation
- Checks for the required directory structure
- **Verifies:**
  - All patch files in `patch_files/` directory
  - `dashboard_index.php`
  - `theme_index.php`
  - `list_themes.php`

### 7. Enhanced Backup System
- Creates `$PLUGIN_DIR/backups/original-files/` for storing original patched files
- Maintains separate backups for theme files and patched system files
- **Backs up:**
  - `web/index.php` → `web_index.php`
  - `web/list/index.php` → `list_index.php`
  - `web/inc/main.php` → `main.php`
  - `web/login/index.php` → `login_index.php`
  - `web/templates/includes/panel.php` → `panel.php`

### 8. Backend Scripts Creation
- Creates `v-change-user-theme` for applying both template and CSS themes
- Creates `v-change-user-css-theme` for CSS-only theme changes
- Configures sudo permissions for web interface access

### 9. CLI Command Setup
- Installs `hestia-theme` wrapper script to `/usr/local/hestia/bin/`
- Provides command-line interface for theme management
- **Available commands:**
  - `hestia-theme list` - List available themes
  - `hestia-theme apply <theme>` - Apply template theme
  - `hestia-theme css <theme>` - Apply CSS theme
  - `hestia-theme current` - Show current themes
  - `hestia-theme status` - Show system status

---

## **Uninstallation Script Features:**

### 1. Original File Restoration
- `restore_original_patched_files()` function restores all backed-up original files
- Properly restores file permissions and ownership
- **Restores:**
  - `/usr/local/hestia/web/index.php`
  - `/usr/local/hestia/web/list/index.php`
  - `/usr/local/hestia/web/inc/main.php`
  - `/usr/local/hestia/web/login/index.php`
  - `/usr/local/hestia/web/templates/pages/edit_server.php`
  - `/usr/local/hestia/web/templates/includes/panel.php`

### 2. Dashboard & Interface Cleanup
- Removes `/usr/local/hestia/web/list/dashboard/` directory
- Removes `/usr/local/hestia/web/list/theme/` directory
- Removes `/usr/local/hestia/web/list/list_themes.php` file
- Cleans up empty directories

### 3. Custom CSS Theme Removal
- Removes all custom CSS theme files (`*_color.css`) from `/usr/local/hestia/web/css/themes/custom/`
- Removes empty custom themes directory
- Preserves system default themes

### 4. Theme Directory Cleanup
- Backs up custom themes to `/tmp/hestia-themes-backup-[timestamp]/` before removal
- Removes entire themes directory from `/usr/local/hestia/web/themes/`
- Allows users to restore themes after reinstallation

### 5. Backend Scripts Removal
- Removes `v-change-user-theme` script
- Removes `v-change-user-css-theme` script
- Removes sudo permissions configuration (`/etc/sudoers.d/hestia-theme-manager`)

### 6. Comprehensive Restoration
- Restores both theme files and patched system files
- Maintains the existing theme restoration functionality
- Attempts plugin-based restoration first, falls back to manual restoration if needed

### 7. CLI Command Removal
- Removes `hestia-theme` wrapper script from `/usr/local/hestia/bin/`
- Cleans up any symlinks

### 8. Log Cleanup
- Removes theme change log (`/var/log/hestia/theme-changes.log`)
- Removes log rotation configuration (`/etc/logrotate.d/hestia-theme-manager`)
- Cleans up plugin log files

### 9. Complete Cleanup
- Removes entire plugin directory (`/usr/local/hestia/plugins/theme-manager/`)
- Removes empty parent plugins directory if no other plugins exist
- Cleans up temporary files older than 1 day

### 10. Force Uninstall Option
- Provides `./uninstall.sh force` for non-interactive uninstallation
- Skips user confirmations
- Ideal for automated scripts or complete system resets

---

## **File Structure:**

```
Theme_Manager/
├── install.sh                    # Installation script (v2.0.6)
├── uninstall.sh                  # Uninstallation script (v2.0.6)
├── patch_files/                  # Patched Hestia files
│   ├── web_index.php             # Patched web/index.php
│   ├── list_index.php            # Patched web/list/index.php
│   ├── main.php                  # Patched web/inc/main.php
│   ├── login_index.php           # Patched web/login/index.php
│   ├── edit_server.php           # Patched web/templates/pages/edit_server.php
│   └── panel.php                 # Patched web/templates/includes/panel.php
├── dashboard_index.php           # Dashboard interface
├── theme_index.php               # Theme management interface
├── list_themes.php               # Theme list page
├── hestia-theme                  # CLI wrapper script
├── hestia_theme_manager.php      # Main plugin file
└── themes/                       # Optional custom themes
    └── [theme-name]/
        ├── theme.json            # Theme configuration
        ├── css/
        │   ├── dark_theme.css   # Theme-specific CSS
        │   └── light_theme.css
        └── ...                   # Theme template files
```

---

## **Installation:**

```bash
# Run as root
sudo bash install.sh
```

**What gets installed:**
- Plugin files in `/usr/local/hestia/plugins/theme-manager/`
- Patched Hestia files (with originals backed up)
- Dashboard at `/list/dashboard/`
- Theme interface at `/list/theme/`
- Theme list page at `/list/list_themes.php`
- Backend scripts in `/usr/local/hestia/bin/`
- CLI command `hestia-theme`
- Sudo permissions for web interface
- Log rotation configuration

---

## **Uninstallation:**

```bash
# Interactive uninstall (with confirmation)
sudo bash uninstall.sh

# Force uninstall (no confirmation)
sudo bash uninstall.sh force

# Show help
sudo bash uninstall.sh help
```

**What gets removed:**
- All patched files restored to originals
- Dashboard and theme interface directories
- `list_themes.php` file
- All custom CSS themes
- Themes directory (backed up to `/tmp/`)
- Backend scripts
- Sudo permissions configuration
- Theme change logs
- CLI command
- Plugin directory and all files
- Log rotation configuration

---

## **Web Interfaces:**

After installation, access these pages in your Hestia panel:

- **Dashboard:** `https://your-server/list/dashboard/`
- **Theme Manager:** `https://your-server/list/theme/`
- **Theme List:** `https://your-server/list/list_themes.php`

---

## **CLI Usage:**

```bash
# List available themes
hestia-theme list

# Apply a template theme
hestia-theme apply my-custom-theme

# Apply a CSS theme only
hestia-theme css dark

# Apply both template and CSS theme
hestia-theme apply my-custom-theme dark

# Show current active themes
hestia-theme current

# Show system status
hestia-theme status
```

---

## **Requirements:**

- Hestia Control Panel installed
- PHP 7.4 or higher
- Root access
- Bash shell

---

## **Safety Features:**

✅ **Backup Protection:** Original files are backed up before any modifications  
✅ **Verification:** All required files are verified before installation begins  
✅ **Rollback:** Uninstall script restores all original files  
✅ **Theme Backup:** Custom themes are backed up before uninstallation  
✅ **Permission Management:** Proper file ownership and permissions maintained  
✅ **Error Handling:** Installation stops if any required files are missing  

---

## **Logs:**

- **Plugin logs:** `/usr/local/hestia/plugins/theme-manager/logs/`
- **Theme changes:** `/var/log/hestia/theme-changes.log`
- **Installation backups:** `/usr/local/hestia/plugins/theme-manager/backups/`
- **Uninstall backups:** `/tmp/hestia-themes-backup-[timestamp]/`

---

## **Support:**

For issues or questions:
1. Check the logs in `/var/log/hestia/theme-changes.log`
2. Review plugin logs in `/usr/local/hestia/plugins/theme-manager/logs/`
3. Run `hestia-theme status` to check system configuration
4. Use `./uninstall.sh` to safely remove the plugin and restore original state