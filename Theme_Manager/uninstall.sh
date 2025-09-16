#!/bin/bash

# Hestia Theme Manager Uninstallation Script
# Version: 2.0.0

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_DIR="/usr/local/hestia/plugins/theme-manager"
HESTIA_WEB_DIR="/usr/local/hestia/web"
BACKUP_DIR="$PLUGIN_DIR/backups"

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
    echo "  - Restore original patched files (list/index.php, inc/main.php, login/index.php)"
    echo "  - Remove dashboard folder and files"
    echo "  - Remove custom CSS themes"
    echo "  - Remove all custom themes"
    echo "  - Delete plugin files and configurations"
    echo "  - Remove web interface and CLI command"
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
    if [ -d "$PLUGIN_DIR/themes" ]; then
        print_status "Backing up custom themes..."
        
        BACKUP_DIR="/tmp/hestia-themes-backup-$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        cp -r "$PLUGIN_DIR/themes" "$BACKUP_DIR/"
        
        print_status "Themes backed up to: $BACKUP_DIR"
        echo "  You can restore these themes after reinstalling the plugin"
    fi
}

# Function to restore original patched files
restore_original_patched_files() {
    print_status "Restoring original patched files..."
    
    # Define files to restore with their backup and target paths
    declare -A FILES_TO_RESTORE=(
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

# Function to remove dashboard folder
remove_dashboard() {
    print_status "Removing dashboard folder..."
    
    DASHBOARD_DIR="/usr/local/hestia/web/list/dashboard"
    if [ -d "$DASHBOARD_DIR" ]; then
        rm -rf "$DASHBOARD_DIR"
        print_status "Dashboard folder removed"
    fi
    
    # Remove custom CSS theme
    CSS_THEME="/usr/local/hestia/web/css/themes/custom/glass_color_theme.css"
    if [ -f "$CSS_THEME" ]; then
        rm "$CSS_THEME"
        print_status "Custom CSS theme removed"
    fi
    
    # Remove custom themes directory if empty
    CUSTOM_THEMES_DIR="/usr/local/hestia/web/css/themes/custom"
    if [ -d "$CUSTOM_THEMES_DIR" ] && [ -z "$(ls -A $CUSTOM_THEMES_DIR)" ]; then
        rmdir "$CUSTOM_THEMES_DIR"
        print_status "Empty custom themes directory removed"
    fi
}

# Function to restore original theme
restore_original_theme() {
    print_status "Restoring original Hestia theme..."
    
    cd "$PLUGIN_DIR"
    if [ -f "hestia_theme_manager.php" ]; then
        php hestia_theme_manager.php uninstall
        
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

# Function to remove web interface (placeholder for future implementation)
remove_web_interface() {
    print_status "Removing web interface..."
    
    # Remove any web interface files that might have been created
    WEB_INTERFACE_FILE="/usr/local/hestia/web/theme-manager.php"
    if [ -f "$WEB_INTERFACE_FILE" ]; then
        rm "$WEB_INTERFACE_FILE"
        print_status "Web interface file removed"
    fi
}

# Function to remove CLI command
remove_cli_command() {
    print_status "Removing CLI command..."
    
    if [ -L "/usr/local/bin/hestia-theme" ]; then
        rm "/usr/local/bin/hestia-theme"
        print_status "CLI command removed"
    fi
}

# Function to remove logrotate configuration
remove_logrotate() {
    print_status "Removing log rotation configuration..."
    
    if [ -f "/etc/logrotate.d/hestia-theme-manager" ]; then
        rm "/etc/logrotate.d/hestia-theme-manager"
        print_status "Log rotation configuration removed"
    fi
}

# Function to remove plugin directory
remove_plugin_directory() {
    print_status "Removing plugin directory..."
    
    if [ -d "$PLUGIN_DIR" ]; then
        rm -rf "$PLUGIN_DIR"
        print_status "Plugin directory removed"
    fi
    
    # Remove empty parent directory if no other plugins exist
    PLUGINS_DIR="/usr/local/hestia/plugins"
    if [ -d "$PLUGINS_DIR" ] && [ -z "$(ls -A $PLUGINS_DIR)" ]; then
        rmdir "$PLUGINS_DIR"
        print_status "Empty plugins directory removed"
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
    echo "    - list/index.php"
    echo "    - inc/main.php" 
    echo "    - login/index.php"
    print_status "✓ Dashboard folder removed"
    print_status "✓ Custom CSS themes removed"
    print_status "✓ Plugin files removed"
    print_status "✓ Web interface removed"
    print_status "✓ CLI command removed"
    print_status "✓ Configuration files removed"
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
    remove_dashboard
    remove_web_interface
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
    echo "           Version 1.0.0"
    echo "======================================"
    echo
    
    # Run all checks and uninstallation steps
    check_root
    check_plugin_installed
    confirm_uninstall
    backup_themes
    restore_original_patched_files
    remove_dashboard
    restore_original_theme
    remove_web_interface
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
        echo "Hestia Theme Manager Uninstaller"
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
        echo "    • list/index.php"
        echo "    • inc/main.php"
        echo "    • login/index.php"
        echo "  - Remove dashboard folder and custom CSS"
        echo "  - Backup custom themes to /tmp/"
        echo "  - Remove all plugin files and configurations"
        echo "  - Clean up web interface and CLI command"
        echo
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac
