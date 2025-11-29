#!/bin/bash

# Hestia Theme Manager Installation Script
# Version: 2.0.6

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
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
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
    mkdir -p "$THEME_DIR"
    mkdir -p "$PLUGIN_DIR/backups"
    mkdir -p "$PLUGIN_DIR/config"
    mkdir -p "$PLUGIN_DIR/logs"
    mkdir -p "$BACKUP_DIR/original-files"
    mkdir -p "$HESTIA_WEB_DIR/css/themes/custom"
    mkdir -p /var/log/hestia
    
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

    declare -A FILES_TO_BACKUP=(
        ["/usr/local/hestia/web/index.php"]="$BACKUP_DIR/original-files/web_index.php"
        ["/usr/local/hestia/web/list/index.php"]="$BACKUP_DIR/original-files/list_index.php"
        ["/usr/local/hestia/web/inc/main.php"]="$BACKUP_DIR/original-files/main.php"
        ["/usr/local/hestia/web/login/index.php"]="$BACKUP_DIR/original-files/login_index.php"
		["/usr/local/hestia/web/templates/pages/edit_server.php"]="$BACKUP_DIR/original-files/edit_server.php"
		["/usr/local/hestia/web/templates/includes/panel.php"]="$BACKUP_DIR/original-files/panel.php"
    )
    
    for source_file in "${!FILES_TO_BACKUP[@]}"; do
        backup_file="${FILES_TO_BACKUP[$source_file]}"
        
        if [ -f "$source_file" ]; then
            mkdir -p "$(dirname "$backup_file")"
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

    declare -A PATCH_FILES=(
        ["$SCRIPT_DIR/patch_files/web_index.php"]="/usr/local/hestia/web/index.php"
        ["$SCRIPT_DIR/patch_files/list_index.php"]="/usr/local/hestia/web/list/index.php"
        ["$SCRIPT_DIR/patch_files/main.php"]="/usr/local/hestia/web/inc/main.php"
        ["$SCRIPT_DIR/patch_files/login_index.php"]="/usr/local/hestia/web/login/index.php"
		["$SCRIPT_DIR/patch_files/edit_server.php"]="/usr/local/hestia/web/templates/pages/edit_server.php"
		["$SCRIPT_DIR/patch_files/panel.php"]="/usr/local/hestia/web/templates/includes/panel.php"
    )
    
    for patch_file in "${!PATCH_FILES[@]}"; do
        target_file="${PATCH_FILES[$patch_file]}"
        
        if [ -f "$patch_file" ]; then
            mkdir -p "$(dirname "$target_file")"
            cp "$patch_file" "$target_file"
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
    
    DASHBOARD_DIR="/usr/local/hestia/web/list/dashboard"
    mkdir -p "$DASHBOARD_DIR"
    
    if [ -f "$SCRIPT_DIR/dashboard_index.php" ]; then
        cp "$SCRIPT_DIR/dashboard_index.php" "$DASHBOARD_DIR/index.php"
        chown hestiaweb:hestiaweb "$DASHBOARD_DIR/index.php"
        chmod 644 "$DASHBOARD_DIR/index.php"
        print_status "Dashboard index.php created"
    else
        print_error "Dashboard index file not found: $SCRIPT_DIR/dashboard_index.php"
        exit 1
    fi
    
    chown -R hestiaweb:hestiaweb "$DASHBOARD_DIR"
    chmod -R 755 "$DASHBOARD_DIR"
    
    print_status "Dashboard setup completed"
}

# Function to create theme folder and copy files
create_theme() {
    print_status "Creating theme folder and copying files..."
    
    THEME_DIR="/usr/local/hestia/web/list/themes"
    mkdir -p "$THEME_DIR"
    
    if [ -f "$SCRIPT_DIR/theme_index.php" ]; then
        cp "$SCRIPT_DIR/theme_index.php" "$THEME_DIR/index.php"
        chown hestiaweb:hestiaweb "$THEME_DIR/index.php"
        chmod 644 "$THEME_DIR/index.php"
        print_status "Theme index.php created"
    else
        print_error "Theme index file not found: $SCRIPT_DIR/theme_index.php"
        exit 1
    fi
    
    chown -R hestiaweb:hestiaweb "$THEME_DIR"
    chmod -R 755 "$THEME_DIR"
    
    print_status "Dashboard setup completed"
}

# Function to copy list_themes.php to web/list directory
copy_list_themes() {
    print_status "Copying list_themes.php to web/list directory..."
    
    LIST_DIR="/usr/local/hestia/web/list"
    
    if [ -f "$SCRIPT_DIR/list_themes.php" ]; then
        cp "$SCRIPT_DIR/list_themes.php" "$LIST_DIR/list_themes.php"
        chown hestiaweb:hestiaweb "$LIST_DIR/list_themes.php"
        chmod 644 "$LIST_DIR/list_themes.php"
        print_status "list_themes.php copied successfully"
    else
        print_error "list_themes.php file not found: $SCRIPT_DIR/list_themes.php"
        exit 1
    fi
}

# Function to copy plugin files
copy_plugin_files() {
    print_status "Installing plugin files..."
    
    if [ -f "$SCRIPT_DIR/hestia_theme_manager.php" ]; then
        cp "$SCRIPT_DIR/hestia_theme_manager.php" "$PLUGIN_DIR/"
        chmod 755 "$PLUGIN_DIR/hestia_theme_manager.php"
    else
        print_error "Main plugin file not found"
        exit 1
    fi
    
    if [ -d "$SCRIPT_DIR/themes" ]; then
        cp -r "$SCRIPT_DIR/themes/"* "$THEME_DIR/" 2>/dev/null || true
        print_status "Themes from installation directory copied"
    fi
    
    print_status "Plugin files installed"
}

# Function to install theme CSS files
install_theme_css_files() {
    print_status "Installing theme CSS files..."
    
    local css_files_copied=0
    
    if [ ! -d "$SCRIPT_DIR/themes" ]; then
        print_status "No themes directory found, skipping CSS installation"
        return
    fi
    
    for theme_dir in "$SCRIPT_DIR/themes"/*; do
        if [ -d "$theme_dir" ]; then
            theme_name=$(basename "$theme_dir")
            css_dir="$theme_dir/css"
            
            if [ ! -d "$css_dir" ]; then
                print_status "No CSS directory found for theme: $theme_name"
                continue
            fi
            
            # Find all CSS files in the theme's css directory
            find "$css_dir" -maxdepth 1 -type f -name "*.css" | while read -r css_file; do
                filename=$(basename "$css_file")
                css_name="${filename%.css}"
                
                # Skip style.css, and color_theme.css
                if [ "$filename" = "style.css" ] || \
                   [ "$filename" = "color_theme.css" ]; then
                    print_status "Skipping CSS file: $filename"
                    continue
                fi
                
                # Copy to custom themes directory
                target_css_file="$HESTIA_WEB_DIR/css/themes/custom/${css_name}.css"
                
                if cp "$css_file" "$target_css_file"; then
                    chown hestiaweb:hestiaweb "$target_css_file"
                    chmod 644 "$target_css_file"
                    print_status "Installed CSS theme: ${css_name}.css"
                    css_files_copied=$((css_files_copied + 1))
                else
                    print_warning "Failed to copy CSS file: $filename"
                fi
            done
        fi
    done
    
    if [ $css_files_copied -eq 0 ]; then
        print_status "No theme CSS files were found to install"
    else
        print_status "Installed $css_files_copied theme CSS files"
    fi
}

# Function to create backend scripts for web interface
create_backend_scripts() {
    print_status "Creating backend scripts for web interface..."
    
    # Create v-change-user-theme script
    cat > "$BIN_DIR/v-change-user-theme" << 'EOF'
#!/bin/bash
# Backend script for web interface to change themes

if [ $# -lt 3 ]; then
    echo "Error: Usage: v-change-user-theme USER TEMPLATE_THEME CSS_THEME"
    exit 1
fi

USER="$1"
TEMPLATE_THEME="$2"
CSS_THEME="$3"

# Verify user exists
if [ ! -d "/usr/local/hestia/data/users/$USER" ]; then
    echo "Error: User $USER does not exist"
    exit 1
fi

# Log the operation
echo "[$(date)] Applying theme for user $USER: Template=$TEMPLATE_THEME, CSS=$CSS_THEME" >> /var/log/hestia/theme-changes.log

# Use the hestia-theme wrapper to apply theme
/usr/local/hestia/bin/hestia-theme apply "$TEMPLATE_THEME" "$CSS_THEME" 2>&1

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    # Update user's theme preference
    USER_CONF="/usr/local/hestia/data/users/$USER/user.conf"
    if [ -f "$USER_CONF" ]; then
        if grep -q "^THEME=" "$USER_CONF"; then
            sed -i "s|^THEME=.*|THEME='$CSS_THEME'|" "$USER_CONF"
        else
            echo "THEME='$CSS_THEME'" >> "$USER_CONF"
        fi
        
        if grep -q "^TEMPLATE_THEME=" "$USER_CONF"; then
            sed -i "s|^TEMPLATE_THEME=.*|TEMPLATE_THEME='$TEMPLATE_THEME'|" "$USER_CONF"
        else
            echo "TEMPLATE_THEME='$TEMPLATE_THEME'" >> "$USER_CONF"
        fi
    fi
    
    echo "OK"
    exit 0
else
    echo "Error: Failed to apply theme"
    exit $EXIT_CODE
fi
EOF
    
    # Create v-change-user-css-theme script
    cat > "$BIN_DIR/v-change-user-css-theme" << 'EOF'
#!/bin/bash
# info: updates user CSS theme (backward compatible)
# options: USER CSS_THEME
#
# example: v-change-user-css-theme admin dark
#
# Changes only the CSS theme for a specified user.

#----------------------------------------------------------#
#                Variables & Functions                     #
#----------------------------------------------------------#

user="$1"
css_theme="$2"

# Includes
# shellcheck source=/etc/hestiacp/hestia.conf
source /etc/hestiacp/hestia.conf
# shellcheck source=/usr/local/hestia/func/main.sh
source "$HESTIA/func/main.sh"
# shellcheck source=/usr/local/hestia/conf/hestia.conf
source_conf "$HESTIA/conf/hestia.conf"

#----------------------------------------------------------#
#                    Verifications                         #
#----------------------------------------------------------#

# Validate arguments
if [ -z "$user" ] || [ -z "$css_theme" ]; then
    echo "Error: Usage: v-change-user-css-theme USER CSS_THEME"
    exit 1
fi

# Validate input formats
is_format_valid 'user' 'theme'
is_common_format_valid "$css_theme" "theme"
is_object_valid 'user' 'USER' "$user"
is_object_unsuspended 'user' 'USER' "$user"

# Check demo mode
check_hestia_demo_mode

#----------------------------------------------------------#
#                Theme Manager Compatibility               #
#----------------------------------------------------------#

LOG_FILE="/var/log/hestia/theme-changes.log"
PLUGIN_WRAPPER="/usr/local/hestia/bin/hestia-theme"
USER_CONF="/usr/local/hestia/data/users/$user/user.conf"

# Verify user config exists
if [ ! -f "$USER_CONF" ]; then
    echo "Error: User $user configuration not found."
    exit 1
fi

# Try to apply CSS theme through the new plugin system
if [ -x "$PLUGIN_WRAPPER" ]; then
    echo "[$(date)] Applying CSS theme for user $user: CSS=$css_theme" >> "$LOG_FILE"
    "$PLUGIN_WRAPPER" css "$css_theme" 2>&1
    EXIT_CODE=$?
else
    echo "[$(date)] hestia-theme wrapper not found, skipping plugin call" >> "$LOG_FILE"
    EXIT_CODE=0
fi

#----------------------------------------------------------#
#                       Action                             #
#----------------------------------------------------------#

if [ $EXIT_CODE -eq 0 ]; then
    # Ensure THEME key exists in user.conf
    if grep -q "^THEME=" "$USER_CONF"; then
        sed -i "s|^THEME=.*|THEME='$css_theme'|" "$USER_CONF"
    else
        echo "THEME='$css_theme'" >> "$USER_CONF"
    fi

    # Log the operation via Hestia
    $BIN/v-log-action "$user" "Info" "System" "Applied CSS theme to user interface (CSS: $css_theme)."

    echo "OK"
    exit 0
else
    echo "Error: Failed to apply CSS theme"
    exit $EXIT_CODE
fi

EOF
    
    # Make scripts executable
    chmod 755 "$BIN_DIR/v-change-user-theme"
    chmod 755 "$BIN_DIR/v-change-user-css-theme"
    chown root:root "$BIN_DIR/v-change-user-theme"
    chown root:root "$BIN_DIR/v-change-user-css-theme"
    
    print_status "Backend scripts created"
}

# Function to configure sudo permissions
configure_sudo_permissions() {
    print_status "Configuring sudo permissions for web interface..."
    
    # Determine web server user
    if id "hestiaweb" &>/dev/null; then
        WEB_USER="hestiaweb"
    elif id "www-data" &>/dev/null; then
        WEB_USER="www-data"
    else
        print_warning "Could not determine web server user, defaulting to www-data"
        WEB_USER="www-data"
    fi
    
    print_status "Detected web server user: $WEB_USER"
    
    # Create sudoers file
    cat > "$SUDOERS_FILE" << EOF
# Hestia Theme Manager - Allow web user to execute theme change scripts
$WEB_USER ALL=(root) NOPASSWD: /usr/local/hestia/bin/v-change-user-theme
$WEB_USER ALL=(root) NOPASSWD: /usr/local/hestia/bin/v-change-user-css-theme
EOF
    
    chmod 440 "$SUDOERS_FILE"
    
    # Validate sudoers file
    if visudo -c -f "$SUDOERS_FILE" &>/dev/null; then
        print_status "Sudo permissions configured successfully"
    else
        print_error "Sudoers configuration validation failed"
        rm -f "$SUDOERS_FILE"
        exit 1
    fi
}

# Function to create theme change log
create_theme_log() {
    print_status "Setting up theme change logging..."
    
    touch /var/log/hestia/theme-changes.log
    chmod 644 /var/log/hestia/theme-changes.log
    chown hestiaweb:hestiaweb /var/log/hestia/theme-changes.log
    
    print_status "Theme change log created: /var/log/hestia/theme-changes.log"
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
    
    # Copy the wrapper script if it exists in the installation directory
    if [ -f "$SCRIPT_DIR/hestia-theme" ]; then
        cp "$SCRIPT_DIR/hestia-theme" "$BIN_DIR/hestia-theme"
        chmod +x "$BIN_DIR/hestia-theme"
        print_status "CLI wrapper 'hestia-theme' installed"
    else
        # Create a basic wrapper if the full wrapper isn't provided
        cat > "$BIN_DIR/hestia-theme" << 'EOF'
#!/bin/bash
php /usr/local/hestia/plugins/theme-manager/hestia_theme_manager.php "$@"
EOF
        chmod +x "$BIN_DIR/hestia-theme"
        print_status "CLI command 'hestia-theme' created"
    fi
}

# Function to create theme development guide
create_theme_guide() {
    print_status "Creating theme development guide..."
    
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
├── theme.json (recommended config file)
├── footer.php
├── header.php
├── css/
│   └── color_theme.css (theme CSS file)
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

## Theme Configuration (theme.json)

```json
{
    "name": "My Custom Theme",
    "description": "A beautiful custom theme for Hestia",
    "version": "1.0.0",
    "css_theme": "dark",
    "author": "Your Name"
}
```

## Managing Themes

Use CLI commands or the web interface at /list/themes/

### CLI Commands:
```bash
hestia-theme list              # List available themes
hestia-theme apply theme-name  # Apply a theme
hestia-theme current           # Show current theme
```
EOF
    
    print_status "Theme development guide created"
}

# Function to set up logrotate for plugin logs
setup_logrotate() {
    print_status "Setting up log rotation..."
    
    cat > "/etc/logrotate.d/hestia-theme-manager" << EOF
$PLUGIN_DIR/logs/*.log /var/log/hestia/theme-changes.log {
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
    print_status "Theme directory: $THEME_DIR"
    print_status "Backend scripts: $BIN_DIR/v-change-user-theme, v-change-user-css-theme"
    echo
    print_status "Web Interface:"
    echo "  Access at: https://your-server/list/themes/"
    echo "  Dashboard at: https://your-server/list/dashboard/"
    echo
    print_status "CLI Commands:"
    echo "  hestia-theme list              - List available themes"
    echo "  hestia-theme apply <theme>     - Apply template theme"
    echo "  hestia-theme css <theme>       - Apply CSS theme"
    echo "  hestia-theme current           - Show current themes"
    echo "  hestia-theme status            - Show system status"
    echo
    print_status "Logs:"
    echo "  Theme manager: $PLUGIN_DIR/logs/"
    echo "  Theme changes: /var/log/hestia/theme-changes.log"
    echo
    print_status "Test the installation:"
    echo "  sudo -u hestiaweb $BIN_DIR/v-change-user-theme admin original default"
    echo
    print_status "Installation completed successfully!"
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed or not in PATH"
        exit 1
    fi
    
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
    
    declare -a REQUIRED_PATCH_FILES=(
        "$SCRIPT_DIR/patch_files/list_index.php"
        "$SCRIPT_DIR/patch_files/main.php"
        "$SCRIPT_DIR/patch_files/login_index.php"
        "$SCRIPT_DIR/dashboard_index.php"
        "$SCRIPT_DIR/theme_index.php"
    )
    
    for file in "${REQUIRED_PATCH_FILES[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Required file not found: $file"
            missing_files=$((missing_files + 1))
        fi
    done
    
    if [ ! -d "$SCRIPT_DIR/patch_files" ]; then
        print_error "patch_files directory not found: $SCRIPT_DIR/patch_files"
        missing_files=$((missing_files + 1))
    fi
    
    if [ $missing_files -gt 0 ]; then
        print_error "Missing $missing_files required file(s). Installation cannot continue."
        exit 1
    fi
    
    print_status "All required patch files found"
}

# Main installation function
main() {
    echo "======================================"
    echo "  Hestia Theme Manager Installer"
    echo "      Version 2.0.6"
    echo "======================================"
    echo

    check_root
    check_requirements
    check_hestia
    verify_patch_files
    backup_existing_plugin
    create_directories
    backup_original_files
    apply_patch_files
    create_dashboard
	create_theme
	copy_list_themes
    copy_plugin_files
    install_theme_css_files
    create_backend_scripts
    configure_sudo_permissions
    create_theme_log
    run_plugin_install
    create_cli_command
    create_theme_guide
    setup_logrotate
    
    show_summary
}

# Handle command line arguments
case "${1:-install}" in
    "install")
        main
        ;;
    "help"|"-h"|"--help")
        echo "Hestia Theme Manager Installer v2.0.6"
        echo
        echo "Usage: $0 [install|help]"
        echo
        echo "This installer sets up:"
        echo "  - Theme manager plugin"
        echo "  - CLI interface (hestia-theme command)"
        echo "  - Web interface (/list/themes/)"
        echo "  - Backend scripts for web interface"
        echo "  - Dashboard (/list/dashboard/)"
        echo
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Use '$0 help' for usage information"
        exit 1
        ;;
esac
