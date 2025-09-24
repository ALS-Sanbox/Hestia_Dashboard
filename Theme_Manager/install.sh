#!/bin/bash

# Hestia Theme Manager Installation Script
# Version: 2.0.2

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
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
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

# Function to check if Hestia is installed
check_hestia() {
    if [ ! -d "/usr/local/hestia" ]; then
        print_error "Hestia Control Panel not found. Please install Hestia first."
        exit 1
    fi
    
    if [ ! -d "$HESTIA_WEB_DIR/templates" ]; then
        print_error "Hestia web templates directory not found."
        exit 1
    fi
    
    print_status "Hestia Control Panel detected"
}

# Function to create plugin directory structure
create_directories() {
    print_status "Creating plugin directories..."
    
    mkdir -p "$PLUGIN_DIR"
    mkdir -p "$THEME_DIR"        # themes now go under web
    mkdir -p "$PLUGIN_DIR/backups"
    mkdir -p "$PLUGIN_DIR/config"
    mkdir -p "$PLUGIN_DIR/logs"
    mkdir -p "$BACKUP_DIR/original-files"  # For backing up original patched files
    
    # Create CSS themes directory for custom themes
    mkdir -p "$HESTIA_WEB_DIR/css/themes/custom"
    
    # Set permissions
    chown -R hestiaweb:hestiaweb "$PLUGIN_DIR"
    chown -R hestiaweb:hestiaweb "$THEME_DIR"
    chown -R hestiaweb:hestiaweb "$HESTIA_WEB_DIR/css/themes/custom"
    chmod -R 755 "$PLUGIN_DIR"
    chmod -R 755 "$THEME_DIR"
    chmod -R 755 "$HESTIA_WEB_DIR/css/themes/custom"
    
    print_status "Plugin directories created"
}

# Function to backup original files before patching
backup_original_files() {
    print_status "Backing up original Hestia files..."
    
    # Define files to backup with their source and destination paths
    declare -A FILES_TO_BACKUP=(
        ["/usr/local/hestia/web/index.php"]="$BACKUP_DIR/original-files/web_index.php"
        ["/usr/local/hestia/web/list/index.php"]="$BACKUP_DIR/original-files/list_index.php"
        ["/usr/local/hestia/web/inc/main.php"]="$BACKUP_DIR/original-files/main.php"
        ["/usr/local/hestia/web/login/index.php"]="$BACKUP_DIR/original-files/login_index.php"
    )
    
    for source_file in "${!FILES_TO_BACKUP[@]}"; do
        backup_file="${FILES_TO_BACKUP[$source_file]}"
        
        if [ -f "$source_file" ]; then
            # Create backup directory if it doesn't exist
            mkdir -p "$(dirname "$backup_file")"
            
            # Copy original file to backup location
            cp "$source_file" "$backup_file"
            print_status "Backed up: $(basename "$source_file")"
        else
            print_warning "Original file not found: $source_file"
        fi
    done
    
    print_status "Original files backed up"
}

# Function to apply patch files
apply_patch_files() {
    print_status "Applying patch files..."
    
    # Define patch files mapping: patch_file -> target_file
    declare -A PATCH_FILES=(
        ["$SCRIPT_DIR/patch_files/web_index.php"]="/usr/local/hestia/web/index.php"
        ["$SCRIPT_DIR/patch_files/list_index.php"]="/usr/local/hestia/web/list/index.php"
        ["$SCRIPT_DIR/patch_files/main.php"]="/usr/local/hestia/web/inc/main.php"
        ["$SCRIPT_DIR/patch_files/login_index.php"]="/usr/local/hestia/web/login/index.php"
    )
    
    for patch_file in "${!PATCH_FILES[@]}"; do
        target_file="${PATCH_FILES[$patch_file]}"
        
        if [ -f "$patch_file" ]; then
            # Create target directory if it doesn't exist
            mkdir -p "$(dirname "$target_file")"
            
            # Copy patch file to target location
            cp "$patch_file" "$target_file"
            
            # Set proper permissions
            chown hestiaweb:hestiaweb "$target_file"
            chmod 644 "$target_file"
            
            print_status "Applied patch: $(basename "$patch_file") -> $(basename "$target_file")"
        else
            print_error "Patch file not found: $patch_file"
            exit 1
        fi
    done
    
    print_status "All patch files applied successfully"
}

# Function to create dashboard folder and copy files
create_dashboard() {
    print_status "Creating dashboard folder and copying files..."
    
    # Create dashboard directory
    DASHBOARD_DIR="/usr/local/hestia/web/list/dashboard"
    mkdir -p "$DASHBOARD_DIR"
    
    # Copy dashboard index file
    if [ -f "$SCRIPT_DIR/dashboard_index.php" ]; then
        cp "$SCRIPT_DIR/dashboard_index.php" "$DASHBOARD_DIR/index.php"
        chown hestiaweb:hestiaweb "$DASHBOARD_DIR/index.php"
        chmod 644 "$DASHBOARD_DIR/index.php"
        print_status "Dashboard index.php created"
    else
        print_error "Dashboard index file not found: $SCRIPT_DIR/dashboard_index.php"
        exit 1
    fi
    
    # Copy dashboard CSS theme
    if [ -f "$SCRIPT_DIR/glass_color_theme.css" ]; then
        cp "$SCRIPT_DIR/glass_color_theme.css" "$HESTIA_WEB_DIR/css/themes/custom/"
        chown hestiaweb:hestiaweb "$HESTIA_WEB_DIR/css/themes/custom/glass_color_theme.css"
        chmod 644 "$HESTIA_WEB_DIR/css/themes/custom/glass_color_theme.css"
        print_status "Dashboard CSS theme copied"
    else
        print_error "Dashboard CSS theme not found: $SCRIPT_DIR/glass_color_theme.css"
        exit 1
    fi
    
    # Set proper permissions for dashboard directory
    chown -R hestiaweb:hestiaweb "$DASHBOARD_DIR"
    chmod -R 755 "$DASHBOARD_DIR"
    
    print_status "Dashboard setup completed"
}

# Function to copy plugin files
copy_plugin_files() {
    print_status "Installing plugin files..."
    
    # Copy main plugin file
    if [ -f "$SCRIPT_DIR/hestia_theme_manager.php" ]; then
        cp "$SCRIPT_DIR/hestia_theme_manager.php" "$PLUGIN_DIR/"
        chmod 755 "$PLUGIN_DIR/hestia_theme_manager.php"
    else
        print_error "Main plugin file not found"
        exit 1
    fi
    
    # Copy example theme if it exists
    if [ -d "$SCRIPT_DIR/themes" ]; then
        cp -r "$SCRIPT_DIR/themes/"* "$THEME_DIR/" 2>/dev/null || true
        print_status "Example themes copied"
    fi
    
    print_status "Plugin files installed"
}

# Function to run plugin installation
run_plugin_install() {
    print_status "Running plugin installation..."
    
    cd "$PLUGIN_DIR"
    php hestia_theme_manager.php install
    
    if [ $? -eq 0 ]; then
        print_status "Plugin installation completed successfully"
    else
        print_error "Plugin installation failed"
        exit 1
    fi
}

# Function to create CLI command wrapper script
create_cli_command() {
    print_status "Setting up CLI command..."
    
    # Remove existing symlink if it exists
    if [ -L "/usr/local/bin/hestia-theme" ]; then
        rm -f "/usr/local/bin/hestia-theme"
        print_status "Removed existing CLI symlink"
    fi
    
    # Create a proper wrapper script instead of symlink
    cat > "/usr/local/bin/hestia-theme" << 'EOF'
#!/bin/bash
php /usr/local/hestia/plugins/theme-manager/hestia_theme_manager.php "$@"
EOF
    
    # Make the wrapper script executable
    chmod +x "/usr/local/bin/hestia-theme"
    
    print_status "CLI command 'hestia-theme' created as wrapper script"
}

# Function to create example theme structure
create_example_theme() {
    print_status "Creating example theme structure..."
    
    EXAMPLE_THEME_DIR="$THEME_DIR/example-dark-theme"
    mkdir -p "$EXAMPLE_THEME_DIR/includes"
    mkdir -p "$EXAMPLE_THEME_DIR/pages"
    mkdir -p "$EXAMPLE_THEME_DIR/pages/login"
    
    # Create a simple example theme info file
    cat > "$EXAMPLE_THEME_DIR/theme_info.json" << 'EOF'
{
    "name": "Example Dark Theme",
    "version": "1.0.0",
    "description": "An example dark theme for Hestia Control Panel",
    "author": "Theme Manager Plugin",
    "created": "2024-01-01"
}
EOF
    
    # Create example README for theme developers
    cat > "$THEME_DIR/README.md" << 'EOF'
# Hestia Themes Directory

This directory contains custom themes for the Hestia Control Panel.

## Creating a New Theme

1. Create a new directory with your theme name (e.g., `my-awesome-theme`)
2. Copy the file structure from the original Hestia templates
3. Modify the files to match your theme design
4. Place your theme files in the same directory structure as Hestia templates:

```
my-awesome-theme/
├── footer.php
├── header.php
├── includes/
│   ├── app-footer.php
│   ├── css.php
│   ├── js.php
│   └── ... (other includes)
├── pages/
│   ├── add_user.php
│   ├── list_user.php
│   └── ... (other pages)
└── pages/login/
    ├── login.php
    └── ... (other login pages)
```

## Theme Structure Requirements

- Your theme must maintain the same file structure as the original Hestia templates
- PHP functionality should remain unchanged - only modify HTML/CSS/JS presentation
- Include all required files or the theme switcher will skip missing files
- Test thoroughly before deploying to production

## Installing Themes

1. Place your theme directory in `/usr/local/hestia/plugins/theme-manager/themes/`
2. Use the web interface at `/theme-manager.php` or CLI command `hestia-theme apply theme-name`
3. The plugin will automatically backup current files before applying your theme

## Backup and Restore

- Original files are automatically backed up during plugin installation
- Current theme is backed up before applying a new theme
- You can always restore the original Hestia theme
EOF
    
    print_status "Example theme structure created"
}

# Function to set up logrotate for plugin logs
setup_logrotate() {
    print_status "Setting up log rotation..."
    
    cat > "/etc/logrotate.d/hestia-theme-manager" << EOF
$PLUGIN_DIR/logs/*.log {
    weekly
    missingok
    rotate 4
    compress
    delaycompress
    notifempty
    copytruncate
}
EOF
    
    print_status "Log rotation configured"
}

# Function to display installation summary
show_summary() {
    echo
    echo "======================================"
    echo "  Hestia Theme Manager Installation"
    echo "           COMPLETED"
    echo "======================================"
    echo
    print_status "Installation directory: $PLUGIN_DIR"
    print_status "CLI command: hestia-theme [install|uninstall|apply|list|current]"
    echo
    print_status "Theme directory: $THEME_DIR"
    print_status "Dashboard directory: /usr/local/hestia/web/list/dashboard"
    print_status "Backup directory: $PLUGIN_DIR/backups"
    print_status "Log directory: $PLUGIN_DIR/logs"
    echo
    print_status "Patch files applied:"
    echo "  ✓ list/index.php - Modified with dashboard integration"
    echo "  ✓ inc/main.php - Updated with custom functionality"  
    echo "  ✓ login/index.php - Enhanced login page"
    echo "  ✓ Dashboard created at /list/dashboard/"
    echo "  ✓ Glass color theme installed"
    echo
    print_warning "Remember to:"
    echo "  1. Place your custom themes in: $THEME_DIR/"
    echo "  2. Test themes in a development environment first"
    echo "  3. Keep backups of your custom themes"
    echo "  4. Check logs if you encounter any issues"
    echo "  5. Original files are backed up and can be restored via uninstall"
    echo
    print_status "Installation completed successfully!"
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    # Check PHP version (minimum 7.4)
    PHP_VERSION=$(php -r "echo PHP_VERSION_ID;")
    if [ "$PHP_VERSION" -lt 70400 ]; then
        print_error "PHP 7.4 or higher is required"
        exit 1
    fi
    
    print_status "System requirements met"
}

# Function to backup existing plugin if it exists
backup_existing_plugin() {
    if [ -d "$PLUGIN_DIR" ]; then
        print_warning "Existing plugin installation found"
        BACKUP_NAME="theme-manager-backup-$(date +%Y%m%d-%H%M%S)"
        print_status "Creating backup: $BACKUP_NAME"
        mv "$PLUGIN_DIR" "/tmp/$BACKUP_NAME"
        print_status "Existing plugin backed up to /tmp/$BACKUP_NAME"
    fi
}

# Function to verify patch files exist
verify_patch_files() {
    print_status "Verifying patch files..."
    
    local missing_files=0
    
    # Check for patch files
    declare -a REQUIRED_PATCH_FILES=(
        "$SCRIPT_DIR/patch_files/list_index.php"
        "$SCRIPT_DIR/patch_files/main.php"
        "$SCRIPT_DIR/patch_files/login_index.php"
        "$SCRIPT_DIR/dashboard_index.php"
        "$SCRIPT_DIR/glass_color_theme.css"
    )
    
    for file in "${REQUIRED_PATCH_FILES[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Required file not found: $file"
            missing_files=$((missing_files + 1))
        fi
    done
    
    # Check for patch_files directory
    if [ ! -d "$SCRIPT_DIR/patch_files" ]; then
        print_error "patch_files directory not found: $SCRIPT_DIR/patch_files"
        missing_files=$((missing_files + 1))
    fi
    
    if [ $missing_files -gt 0 ]; then
        print_error "Missing $missing_files required file(s). Installation cannot continue."
        echo
        print_status "Required file structure:"
        echo "  $(dirname $SCRIPT_DIR)/"
        echo "  ├── install.sh"
        echo "  ├── patch_files/"
        echo "  │   ├── list_index.php"
        echo "  │   ├── main.php"
        echo "  │   └── login_index.php"
        echo "  ├── dashboard_index.php"
        echo "  ├── glass_color_theme.css"
        echo "  └── hestia_theme_manager.php"
        exit 1
    fi
    
    print_status "All required patch files found"
}

# Main installation function
main() {
    echo "======================================"
    echo "  Hestia Theme Manager Installer"
    echo "           Version 1.0.0"
    echo "======================================"
    echo
    
    # Run all checks and installation steps
    check_root
    check_requirements
    check_hestia
    verify_patch_files
    backup_existing_plugin
    create_directories
    backup_original_files
    apply_patch_files
    create_dashboard
    copy_plugin_files
    run_plugin_install
    create_cli_command
    create_example_theme
    setup_logrotate
    
    # Show installation summary
    show_summary
}

# Handle command line arguments
case "${1:-install}" in
    "install")
        main
        ;;
    "help"|"-h"|"--help")
        echo "Hestia Theme Manager Installer"
        echo
        echo "Usage: $0 [install|help]"
        echo
        echo "Commands:"
        echo "  install    Install the theme manager plugin (default)"
        echo "  help       Show this help message"
        echo
        echo "Required Files:"
        echo "  patch_files/list_index.php    - Dashboard-enabled list page"
        echo "  patch_files/main.php          - Modified main include"  
        echo "  patch_files/login_index.php   - Enhanced login page"
        echo "  dashboard_index.php           - Dashboard page"
        echo "  glass_color_theme.css         - Dashboard theme"
        echo "  hestia_theme_manager.php      - Main plugin file"
        echo
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac
