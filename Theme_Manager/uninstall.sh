#!/bin/bash

# Hestia Theme Manager Uninstallation Script
# Version: 2.0.4

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_DIR="/usr/local/hestia/plugins/theme-manager"
HESTIA_WEB_DIR="/usr/local/hestia/web"
THEME_DIR="$HESTIA_WEB_DIR/themes"
BIN_DIR="/usr/local/hestia/bin"
BACKUP_DIR="$PLUGIN_DIR/backups"
SUDOERS_FILE="/etc/sudoers.d/hestia-theme-manager"

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if script is run as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root"
        exit 1
    fi
}

# Function to check if plugin is installed
check_plugin_installed() {
    if [ ! -d "$PLUGIN_DIR" ]; then
        print_error "Theme Manager plugin is not installed"
        exit 1
    fi
    
    print_status "Theme Manager plugin found"
}

# Function to confirm uninstallation
confirm_uninstall() {
    echo
    print_warning "This will:"
    echo "  - Restore the original Hestia theme"
    echo "  - Restore original patched files:"
    echo "    • web/index.php"
    echo "    • web/list/index.php" 
    echo "    • web/inc/main.php"
    echo "    • web/login/index.php"
    echo "  - Remove dashboard folder (/list/dashboard/)"
    echo "  - Remove theme folder (/list/theme/)"
    echo "  - Remove all custom CSS themes (*_color.css files)"
    echo "  - Remove themes directory ($THEME_DIR)"
    echo "  - Remove all custom themes"
    echo "  - Remove backend scripts (v-change-user-theme, v-change-user-css-theme)"
    echo "  - Remove sudo permissions configuration"
    echo "  - Remove theme change log"
    echo "  - Remove CLI command (hestia-theme)"
    echo "  - Delete plugin files and configurations"
    echo "  - Remove log rotation configuration"
    echo
    
    read -p "Are you sure you want to uninstall the Theme Manager plugin? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Uninstallation cancelled"
        exit 0
    fi
}

# Function to backup themes before uninstall
backup_themes() {
    if [ -d "$THEME_DIR" ]; then
        print_status "Backing up custom themes..."
        
        BACKUP_THEMES_DIR="/tmp/hestia-themes-backup-$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$BACKUP_THEMES_DIR"
        cp -r "$THEME_DIR" "$BACKUP_THEMES_DIR/"
        
        print_status "Themes backed up to: $BACKUP_THEMES_DIR"
        echo "  You can restore these themes after reinstalling the plugin"
    fi
}

# Function to restore original patched files
restore_original_patched_files() {
    print_status "Restoring original patched files..."
    
    # Define files to restore with their backup and target paths (matches install script exactly)
    declare -A FILES_TO_RESTORE=(
        ["$BACKUP_DIR/original-files/web_index.php"]="/usr/local/hestia/web/index.php"
        ["$BACKUP_DIR/original-files/list_index.php"]="/usr/local/hestia/web/list/index.php"
        ["$BACKUP_DIR/original-files/main.php"]="/usr/local/hestia/web/inc/main.php"
        ["$BACKUP_DIR/original-files/login_index.php"]="/usr/local/hestia/web/login/index.php"
    )
    
    local restored_files=0
    
    for backup_file in "${!FILES_TO_RESTORE[@]}"; do
        target_file="${FILES_TO_RESTORE[$backup_file]}"
        
        if [ -f "$backup_file" ]; then
            # Restore the original file
            cp "$backup_file" "$target_file"
            
            # Set proper permissions
            chown hestiaweb:hestiaweb "$target_file"
            chmod 644 "$target_file"
            
            print_status "Restored: $(basename "$target_file")"
            restored_files=$((restored_files + 1))
        else
            print_warning "Backup file not found: $backup_file"
        fi
    done
    
    if [ $restored_files -gt 0 ]; then
        print_status "Original patched files restored successfully"
    else
        print_warning "No original files were restored - backups may not exist"
    fi
}

# Function to remove dashboard and theme folders
remove_dashboard_and_theme_folders() {
    print_status "Removing dashboard and theme folders..."
    
    # Remove dashboard directory (created by install script)
    DASHBOARD_DIR="/usr/local/hestia/web/list/dashboard"
    if [ -d "$DASHBOARD_DIR" ]; then
        rm -rf "$DASHBOARD_DIR"
        print_status "Dashboard folder removed: $DASHBOARD_DIR"
    fi
    
    # Remove theme directory (created by install script - duplicate function name bug fix)
    THEME_INTERFACE_DIR="/usr/local/hestia/web/list/theme"
    if [ -d "$THEME_INTERFACE_DIR" ]; then
        rm -rf "$THEME_INTERFACE_DIR"
        print_status "Theme folder removed: $THEME_INTERFACE_DIR"
    fi
}

# Function to remove all custom CSS themes
remove_custom_css_themes() {
    print_status "Removing custom CSS themes..."
    
    # Remove all custom CSS themes (installed by theme system)
    CUSTOM_THEMES_DIR="$HESTIA_WEB_DIR/css/themes/custom"
    if [ -d "$CUSTOM_THEMES_DIR" ]; then
        local removed_count=0
        
        # Remove all *_color.css files (theme CSS files installed by new system)
        for css_file in "$CUSTOM_THEMES_DIR"/*_color.css; do
            if [ -f "$css_file" ]; then
                rm "$css_file"
                print_status "Removed theme CSS: $(basename "$css_file")"
                removed_count=$((removed_count + 1))
            fi
        done
        
        if [ $removed_count -eq 0 ]; then
            print_status "No custom CSS theme files found to remove"
        else
            print_status "Removed $removed_count custom CSS theme files"
        fi
        
        # Remove custom themes directory if empty
        if [ -z "$(ls -A $CUSTOM_THEMES_DIR 2>/dev/null)" ]; then
            rmdir "$CUSTOM_THEMES_DIR"
            print_status "Empty custom themes directory removed"
        fi
    fi
}

# Function to remove themes directory (created by install script)
remove_themes_directory() {
    print_status "Removing themes directory..."
    
    if [ -d "$THEME_DIR" ]; then
        rm -rf "$THEME_DIR"
        print_status "Themes directory removed: $THEME_DIR"
    fi
}

# Function to remove backend scripts
remove_backend_scripts() {
    print_status "Removing backend scripts..."
    
    local removed_count=0
    
    # Remove v-change-user-theme script
    if [ -f "$BIN_DIR/v-change-user-theme" ]; then
        rm "$BIN_DIR/v-change-user-theme"
        print_status "Removed: v-change-user-theme"
        removed_count=$((removed_count + 1))
    fi
    
    # Remove v-change-user-css-theme script
    if [ -f "$BIN_DIR/v-change-user-css-theme" ]; then
        rm "$BIN_DIR/v-change-user-css-theme"
        print_status "Removed: v-change-user-css-theme"
        removed_count=$((removed_count + 1))
    fi
    
    if [ $removed_count -eq 0 ]; then
        print_status "No backend scripts found to remove"
    else
        print_status "Removed $removed_count backend scripts"
    fi
}

# Function to remove sudo permissions configuration
remove_sudo_permissions() {
    print_status "Removing sudo permissions configuration..."
    
    if [ -f "$SUDOERS_FILE" ]; then
        rm "$SUDOERS_FILE"
        print_status "Sudo permissions configuration removed: $SUDOERS_FILE"
    else
        print_status "No sudo permissions file found"
    fi
}

# Function to remove theme change log
remove_theme_log() {
    print_status "Removing theme change log..."
    
    if [ -f "/var/log/hestia/theme-changes.log" ]; then
        rm "/var/log/hestia/theme-changes.log"
        print_status "Theme change log removed"
    else
        print_status "No theme change log found"
    fi
}

# Function to restore original theme using plugin
restore_original_theme() {
    print_status "Restoring original Hestia theme..."
    
    cd "$PLUGIN_DIR"
    if [ -f "hestia_theme_manager.php" ]; then
        php hestia_theme_manager.php uninstall 2>/dev/null
        
        if [ $? -eq 0 ]; then
            print_status "Original theme restored successfully"
        else
            print_warning "Failed to restore original theme using plugin"
            print_status "Attempting manual restore..."
            manual_restore_original
        fi
    else
        print_warning "Plugin file not found, attempting manual restore..."
        manual_restore_original
    fi
}

# Function to manually restore original theme
manual_restore_original() {
    BACKUP_DIR_ORIG="$PLUGIN_DIR/backups/original"
    
    if [ -d "$BACKUP_DIR_ORIG" ]; then
        print_status "Manually restoring original files..."
        
        # Define the template files to restore
        declare -a TEMPLATE_FILES=(
            "footer.php"
            "header.php"
            "includes/app-footer.php"
            "includes/extra-ns-fields.php"
            "includes/login-footer.php"
            "includes/title.php"
            "includes/css.php"
            "includes/js.php"
            "includes/panel.php"
            "includes/email-settings-panel.php"
            "includes/jump-to-top-link.php"
            "includes/password-requirements.php"
        )
        
        # Restore template files
        for file in "${TEMPLATE_FILES[@]}"; do
            if [ -f "$BACKUP_DIR_ORIG/$file" ]; then
                cp "$BACKUP_DIR_ORIG/$file" "/usr/local/hestia/web/templates/$file"
                chmod 644 "/usr/local/hestia/web/templates/$file"
            fi
        done
        
        # Restore page files
        if [ -d "$BACKUP_DIR_ORIG/pages" ]; then
            find "$BACKUP_DIR_ORIG/pages" -name "*.php" -type f | while read -r file; do
                rel_path=${file#$BACKUP_DIR_ORIG/}
                target="/usr/local/hestia/web/templates/$rel_path"
                cp "$file" "$target"
                chmod 644 "$target"
            done
        fi
        
        print_status "Manual restore completed"
    else
        print_warning "Original backup not found - original files may not be restored"
    fi
}

# Function to remove CLI command (matches install script - removes wrapper script)
remove_cli_command() {
    print_status "Removing CLI command..."
    
    # Remove the wrapper script (created by install script at /usr/local/hestia/bin/hestia-theme)
    CLI_WRAPPER="$BIN_DIR/hestia-theme"
    if [ -f "$CLI_WRAPPER" ]; then
        rm "$CLI_WRAPPER"
        print_status "CLI wrapper script removed: $CLI_WRAPPER"
    elif [ -L "$CLI_WRAPPER" ]; then
        rm "$CLI_WRAPPER"
        print_status "CLI symlink removed: $CLI_WRAPPER"
    else
        print_status "No CLI command found to remove"
    fi
}

# Function to remove logrotate configuration
remove_logrotate() {
    print_status "Removing log rotation configuration..."
    
    if [ -f "/etc/logrotate.d/hestia-theme-manager" ]; then
        rm "/etc/logrotate.d/hestia-theme-manager"
        print_status "Log rotation configuration removed"
    else
        print_status "No log rotation configuration found"
    fi
}

# Function to remove plugin directory and all subdirectories
remove_plugin_directory() {
    print_status "Removing plugin directory..."
    
    if [ -d "$PLUGIN_DIR" ]; then
        rm -rf "$PLUGIN_DIR"
        print_status "Plugin directory removed: $PLUGIN_DIR"
    fi
    
    # Remove empty parent directory if no other plugins exist
    PLUGINS_DIR="/usr/local/hestia/plugins"
    if [ -d "$PLUGINS_DIR" ] && [ -z "$(ls -A $PLUGINS_DIR)" ]; then
        rmdir "$PLUGINS_DIR"
        print_status "Empty plugins directory removed: $PLUGINS_DIR"
    fi
}

# Function to clean up any remaining files
cleanup_remaining_files() {
    print_status "Cleaning up any remaining files..."
    
    # Remove any temporary files that might have been created
    find /tmp -name "*hestia-theme*" -type f -mtime +1 -delete 2>/dev/null || true
    
    print_status "Cleanup completed"
}

# Function to display uninstallation summary
show_summary() {
    echo
    echo "======================================"
    echo "  Hestia Theme Manager Uninstallation"
    echo "           COMPLETED"
    echo "======================================"
    echo
    print_status "✓ Original Hestia theme restored"
    print_status "✓ Original patched files restored:"
    echo "    - web/index.php"
    echo "    - web/list/index.php"
    echo "    - web/inc/main.php" 
    echo "    - web/login/index.php"
    print_status "✓ Dashboard folder removed (/list/dashboard/)"
    print_status "✓ Theme folder removed (/list/theme/)"
    print_status "✓ All custom CSS themes removed (*_color.css files)"
    print_status "✓ Themes directory removed ($THEME_DIR)"
    print_status "✓ Backend scripts removed (v-change-user-theme, v-change-user-css-theme)"
    print_status "✓ Sudo permissions configuration removed"
    print_status "✓ Theme change log removed"
    print_status "✓ Plugin files removed"
    print_status "✓ CLI command removed (hestia-theme)"
    print_status "✓ Configuration files removed"
    print_status "✓ Log rotation configuration removed"
    echo
    
    if [ -d "/tmp" ]; then
        BACKUP_DIRS=$(find /tmp -maxdepth 1 -name "*hestia-themes-backup*" -type d 2>/dev/null | wc -l)
        if [ "$BACKUP_DIRS" -gt 0 ]; then
            print_warning "Custom themes backed up in /tmp/"
            echo "  Look for directories named 'hestia-themes-backup-*'"
            echo "  You can restore these after reinstalling the plugin"
            echo
        fi
    fi
    
    print_status "Theme Manager plugin has been completely uninstalled"
    echo
    print_status "Your Hestia Control Panel has been restored to its original state"
}

# Function to handle force uninstall
force_uninstall() {
    print_warning "Force uninstall mode - skipping confirmations"
    
    # Skip backup in force mode but still restore original files
    restore_original_patched_files
    remove_dashboard_and_theme_folders
    remove_custom_css_themes
    remove_themes_directory
    remove_backend_scripts
    remove_sudo_permissions
    remove_theme_log
    remove_cli_command
    remove_logrotate
    
    # Try to restore original theme if possible
    if [ -d "$PLUGIN_DIR/backups/original" ]; then
        manual_restore_original
    else
        print_warning "Original backup not found - manual theme restoration may be required"
    fi
    
    remove_plugin_directory
    cleanup_remaining_files
    
    print_status "Force uninstallation completed"
}

# Main uninstallation function
main() {
    echo "======================================"
    echo "  Hestia Theme Manager Uninstaller"
    echo "           Version 2.0.4"
    echo "======================================"
    echo
    
    # Run all checks and uninstallation steps
    check_root
    check_plugin_installed
    confirm_uninstall
    backup_themes
    restore_original_patched_files
    remove_dashboard_and_theme_folders
    remove_custom_css_themes
    remove_themes_directory
    restore_original_theme
    remove_backend_scripts
    remove_sudo_permissions
    remove_theme_log
    remove_cli_command
    remove_logrotate
    remove_plugin_directory
    cleanup_remaining_files
    
    # Show uninstallation summary
    show_summary
}

# Handle command line arguments
case "${1:-uninstall}" in
    "uninstall")
        main
        ;;
    "force")
        check_root
        if [ -d "$PLUGIN_DIR" ]; then
            force_uninstall
        else
            print_error "Plugin not found"
            exit 1
        fi
        ;;
    "help"|"-h"|"--help")
        echo "Hestia Theme Manager Uninstaller v2.0.4"
        echo
        echo "Usage: $0 [uninstall|force|help]"
        echo
        echo "Commands:"
        echo "  uninstall  Uninstall the theme manager plugin (default)"
        echo "  force      Force uninstall without confirmations"
        echo "  help       Show this help message"
        echo
        echo "The uninstaller will:"
        echo "  - Restore original Hestia theme"
        echo "  - Restore original patched files:"
        echo "    • web/index.php"
        echo "    • web/list/index.php"
        echo "    • web/inc/main.php"
        echo "    • web/login/index.php"
        echo "  - Remove dashboard folder (/list/dashboard/)"
        echo "  - Remove theme folder (/list/theme/)"
        echo "  - Remove themes directory ($THEME_DIR)"
        echo "  - Remove all custom CSS themes (*_color.css files)"
        echo "  - Remove backend scripts (v-change-user-theme, v-change-user-css-theme)"
        echo "  - Remove sudo permissions configuration"
        echo "  - Remove theme change log"
        echo "  - Backup custom themes to /tmp/"
        echo "  - Remove all plugin files and configurations"
        echo "  - Clean up CLI command (hestia-theme wrapper script)"
        echo "  - Remove log rotation configuration"
        echo
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac
