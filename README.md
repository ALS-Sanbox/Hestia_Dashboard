📦 Hestia Theme Manager — Installation & Uninstallation Guide (v2.0.6)

A complete theme management system for Hestia Control Panel, enabling custom dashboard themes, CSS color themes, a user-friendly web interface, and advanced CLI theme operations.

Version 2.0.6 includes major improvements to patch verification, CSS installation, backup handling, CLI wrappers, and installation safety.

✨ Features Overview
✔ Installation Script (install.sh) — v2.0.6
1. Robust Patch File Handling

Backs up all original Hestia files before patching

Applies patched versions of:

web_index.php → /usr/local/hestia/web/index.php

list_index.php → /usr/local/hestia/web/list/index.php

main.php → /usr/local/hestia/web/inc/main.php

login_index.php → /usr/local/hestia/web/login/index.php

edit_server.php → /usr/local/hestia/web/templates/pages/edit_server.php

panel.php → /usr/local/hestia/web/templates/includes/panel.php

2. Dashboard System Setup

Creates: /usr/local/hestia/web/list/dashboard/

Copies: dashboard_index.php → index.php

Applies correct ownership + permissions

3. Theme Manager Page Setup

Creates: /usr/local/hestia/web/list/theme/

Copies: theme_index.php → index.php

4. List Themes Interface

Copies: list_themes.php → /usr/local/hestia/web/list/list_themes.php

Appears in the main list menu

5. CSS Theme Installer (Improved in 2.0.6)

Installs all CSS theme files from:
themes/*/css/*.css

Skips:

style.css

color_theme.css

Installs everything else, including multi-file themes

No subfolders — places all into:

/usr/local/hestia/web/css/themes/custom/

6. Pre-Install Verification

verify_patch_files() ensures:

All required patch files exist

Required directories exist

Dashboard index and theme index exist

Prevents partial installs or breaks

7. Enhanced Backup System

Backs up original Hestia files to:

/usr/local/hestia/plugins/theme-manager/backups/original-files/


Backed-up files:

web/index.php

web/list/index.php

web/inc/main.php

web/login/index.php

templates/includes/panel.php

templates/pages/edit_server.php

8. Backend Scripts

Creates:

v-change-user-theme (template + CSS)

v-change-user-css-theme (CSS only)

Both:

Log operations to /var/log/hestia/theme-changes.log

Update user.conf properly

Have matching sudo rules

9. CLI Wrapper Setup

Installs:

/usr/local/hestia/bin/hestia-theme


Supports:

list

apply <theme>

css <theme>

status

current

list-css

install-theme

uninstall-theme

and more…

10. Theme Developer Guide

Creates:

/usr/local/hestia/web/themes/README.md


Contains instructions for theme builders.

11. Log Rotation

Adds:

/etc/logrotate.d/hestia-theme-manager

12. Safety & Requirement Checks

Must be root

Must have PHP 7.4+

Must detect Hestia installation

Stops on missing files

Graceful error handling

🗑 Uninstallation Script (uninstall.sh) — v2.0.6
1. Restoration of Original System Files

Restores all files backed up in:

backups/original-files/


Restores:

web/index.php

web/list/index.php

web/inc/main.php

web/login/index.php

templates/includes/panel.php

templates/pages/edit_server.php

2. Removes Installed Interfaces

/list/dashboard/

/list/theme/

/list/list_themes.php

3. CSS Theme Cleanup

Removes all custom CSS themes:

*_color.css
*.css (installed from custom themes)


Directory removed if empty:

/usr/local/hestia/web/css/themes/custom/

4. Theme Directory Cleanup

Before deletion, backs up themes to:

/tmp/hestia-themes-backup-YYYYMMDD-HHMMSS/

5. Removes Backend Scripts

v-change-user-theme

v-change-user-css-theme

6. Removes CLI Wrapper

/usr/local/hestia/bin/hestia-theme

7. Log & Config Cleanup

Deletes /var/log/hestia/theme-changes.log

Removes logrotate config

Deletes sudoers file

8. Fully Removes Plugin Directory
/usr/local/hestia/plugins/theme-manager/

9. Force Mode
sudo bash uninstall.sh force


Skips:

Confirmations

Theme backup

Ideal for automation or resets.

📁 File Structure
Theme_Manager/
├── install.sh
├── uninstall.sh
├── wrapper.sh
├── hestia-theme
├── hestia_theme_manager.php
├── dashboard_index.php
├── theme_index.php
├── list_themes.php
├── patch_files/
│   ├── web_index.php
│   ├── list_index.php
│   ├── main.php
│   ├── login_index.php
│   ├── edit_server.php
│   └── panel.php
└── themes/
    └── example-theme/
        ├── theme.json
        ├── css/
        │   ├── dark.css
        │   └── light.css
        └── template files...

🛠 Installation

Run as root:

sudo bash install.sh


Installs:

Theme Manager plugin

Dashboard interface

Theme manager page

CLI wrapper

Backend scripts

CSS themes

Log rotation

Backups

🗑 Uninstallation
Interactive mode
sudo bash uninstall.sh

Force mode (no prompts)
sudo bash uninstall.sh force

Help
sudo bash uninstall.sh help

🌐 Web Interface URLs

Dashboard:
https://your-server/list/dashboard/

Theme Manager:
https://your-server/list/theme/

List Themes:
https://your-server/list/list_themes.php

🧰 CLI Usage
hestia-theme list
hestia-theme list-css
hestia-theme apply <template_theme> <css_theme>
hestia-theme css <css_theme>
hestia-theme current
hestia-theme status

Theme Installation
hestia-theme install-theme /path/to/theme.zip
hestia-theme install-theme /path/to/theme-dir

Uninstall a theme
hestia-theme uninstall-theme <theme-name>

Backups
hestia-theme backup-theme <theme-name>

📌 Requirements

HestiaCP installed

PHP 7.4+

Bash

Root privileges

🛡 Safety Features

✔ Full backups before modifying anything
✔ Required-file verification
✔ All system changes reversible
✔ Custom themes backed up during uninstall
✔ Permission and ownership enforced
✔ Log rotation automatically configured

📄 Logs
Type	Path
Theme changes	/var/log/hestia/theme-changes.log
Plugin logs	/usr/local/hestia/plugins/theme-manager/logs/
Original backups	/usr/local/hestia/plugins/theme-manager/backups/
Uninstall backups	/tmp/hestia-themes-backup-*/
❓ Support & Troubleshooting

Run:

hestia-theme status


Check logs:

/var/log/hestia/theme-changes.log

/usr/local/hestia/plugins/theme-manager/logs/

To restore Hestia completely:

sudo bash uninstall.sh
