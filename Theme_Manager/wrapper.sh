#!/bin/bash

# Hestia Theme Manager CLI Wrapper
# Version: 2.0.3
# This script provides a clean interface for the Hestia Theme Manager

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_DIR="/usr/local/hestia/plugins/theme-manager"
PHP_SCRIPT="$PLUGIN_DIR/hestia_theme_manager.php"

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

# Function to check if plugin is installed
check_plugin() {
    if [ ! -f "$PHP_SCRIPT" ]; then
        print_error "Theme Manager plugin not found"
        echo "Expected location: $PHP_SCRIPT"
        echo "Please install the plugin first using the install script"
        exit 1
    fi
}

# Function to check permissions
check_permissions() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This command requires root privileges"
        echo "Please run with sudo or as root"
        exit 1
    fi
}

# Function to show usage
show_usage() {
    echo "Hestia Theme Manager CLI v2.0.3"
    echo "Usage: hestia-theme [command] [arguments]"
    echo ""
    echo "Core Commands:"
    echo "  install                          Install the theme manager plugin"
    echo "  uninstall                        Uninstall plugin and restore original"
    echo "  apply <theme> [css]              Apply template theme with optional CSS"
    echo "  css <theme>                      Apply only CSS theme"
    echo "  list                             List available template themes"
    echo "  list-css                         List available CSS themes"
    echo "  current                          Show current active themes"
    echo "  status                           Show detailed system status"
    echo "  debug                            Show debug information"
    echo ""
    echo "Theme Management:"
    echo "  install-theme <source> [name] [-f] Install theme from ZIP/directory"
    echo "  uninstall-theme <name> [-f] [-b]   Uninstall a theme"
    echo "  list-installed                     List installed themes with metadata"
    echo "  theme-info <name>                  Show detailed theme information"
    echo "  validate-theme <name>              Validate theme structure"
    echo ""
    echo "Backup Management:"
    echo "  backup-theme <name> [backup_name]  Create theme backup"
    echo "  list-backups                       List available backups"
    echo "  restore-backup <name>              Restore from backup"
    echo ""
    echo "Options:"
    echo "  -f, --force      Force operation (overwrite/uninstall active theme)"
    echo "  -b, --backup     Create backup before uninstall"
    echo "  -h, --help       Show this help message"
    echo ""
    echo "Examples:"
    echo "  hestia-theme list"
    echo "  hestia-theme apply dark-theme dark"
    echo "  hestia-theme install-theme /path/to/theme.zip my-theme"
    echo "  hestia-theme install-theme /path/to/theme-dir --force"
    echo "  hestia-theme uninstall-theme old-theme --backup"
    echo ""
}

# Function to show detailed help
show_help() {
    echo -e "${BLUE}🎨 Hestia Theme Manager${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "DESCRIPTION:"
    echo "  Advanced theme management system for Hestia Control Panel."
    echo "  Supports custom theme installation, CSS theme switching, and safe"
    echo "  theme management using symlinks for efficient switching."
    echo ""
    echo "THEME INSTALLATION:"
    echo "  Themes can be installed from:"
    echo "  • ZIP archives (theme.zip)"
    echo "  • Directory paths (/path/to/theme)"
    echo "  • GitHub repositories (clone first, then install directory)"
    echo ""
    echo "  Theme Structure Required:"
    echo "    theme-name/"
    echo "    ├── theme.json (recommended)"
    echo "    ├── header.php"
    echo "    ├── footer.php"
    echo "    ├── includes/"
    echo "    │   ├── css.php"
    echo "    │   ├── js.php"
    echo "    │   └── ..."
    echo "    ├── pages/"
    echo "    │   ├── list_user.php"
    echo "    │   └── ..."
    echo "    └── css/"
    echo "        └── color_theme.css (custom CSS)"
    echo ""
    echo "THEME CONFIGURATION (theme.json):"
    echo '  {'
    echo '    "name": "My Custom Theme",'
    echo '    "description": "A beautiful custom theme",'
    echo '    "version": "1.0.0",'
    echo '    "css_theme": "dark",'
    echo '    "author": "Your Name"'
    echo '  }'
    echo ""
    echo "BACKUP SYSTEM:"
    echo "  • Automatic backups before theme changes"
    echo "  • Manual theme backups on demand"
    echo "  • Original files always preserved"
    echo "  • Easy restoration to previous states"
    echo ""
    echo "DIRECTORIES:"
    echo "  Plugin:    $PLUGIN_DIR"
    echo "  Themes:    /usr/local/hestia/web/themes/"
    echo "  Backups:   $PLUGIN_DIR/backups/"
    echo "  Logs:      $PLUGIN_DIR/logs/"
    echo ""
    show_usage
}

# Main logic
case "${1:-help}" in
    "install"|"uninstall"|"apply"|"css"|"list"|"list-css"|"current"|"status"|"debug")
        check_permissions
        check_plugin
        php "$PHP_SCRIPT" "$@"
        ;;
    
    "install-theme")
        check_permissions
        check_plugin
        
        # Convert bash flags to PHP arguments
        args=("$@")
        php_args=()
        
        for arg in "${args[@]}"; do
            case "$arg" in
                "-f"|"--force")
                    php_args+=("--overwrite")
                    ;;
                *)
                    php_args+=("$arg")
                    ;;
            esac
        done
        
        php "$PHP_SCRIPT" "${php_args[@]}"
        ;;
    
    "uninstall-theme")
        check_permissions
        check_plugin
        
        # Convert bash flags to PHP arguments  
        args=("$@")
        php_args=()
        
        for arg in "${args[@]}"; do
            case "$arg" in
                "-f"|"--force")
                    php_args+=("--force")
                    ;;
                "-b"|"--backup")
                    php_args+=("--backup")
                    ;;
                *)
                    php_args+=("$arg")
                    ;;
            esac
        done
        
        php "$PHP_SCRIPT" "${php_args[@]}"
        ;;
    
    "list-installed"|"theme-info"|"validate-theme"|"backup-theme"|"restore-backup"|"list-backups")
        check_permissions
        check_plugin
        php "$PHP_SCRIPT" "$@"
        ;;
    
    "help"|"--help"|"-h")
        show_help
        ;;
    
    "version"|"--version"|"-v")
        echo "Hestia Theme Manager CLI v2.0.3"
        ;;
    
    *)
        if [ -z "$1" ]; then
            show_usage
        else
            print_error "Unknown command: $1"
            echo ""
            echo "Use 'hestia-theme --help' for detailed information"
            echo "Use 'hestia-theme' for basic usage"
            exit 1
        fi
        ;;
esac
