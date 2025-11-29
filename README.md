# 📦 Hestia Theme Manager --- Installation & Uninstallation Guide (v2.0.6)

![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)\
![HestiaCP](https://img.shields.io/badge/HestiaCP-Compatible-blue?style=for-the-badge)\
![Version](https://img.shields.io/badge/Version-2.0.6-purple?style=for-the-badge)\
![Status](https://img.shields.io/badge/Status-Stable-success?style=for-the-badge)\
![Bash](https://img.shields.io/badge/Bash-Scripts-yellow?style=for-the-badge)\
![PHP](https://img.shields.io/badge/PHP-7.4+-blueviolet?style=for-the-badge)

A complete and safe **theme management system for Hestia Control
Panel**, enabling:

✔ Full dashboard theming\
✔ CSS color theme switching\
✔ Web-based theme UI\
✔ CLI theme operations\
✔ Safe backups + patch management\
✔ Custom theme installer

Version **2.0.6** introduces improved validation, safer installation,
enhanced CSS handling, and expanded CLI capabilities.

------------------------------------------------------------------------

# 📸 Screenshots

### Dashboard Overview

![Dashboard
<img width="1899" height="913" alt="image" src="https://github.com/user-attachments/assets/bec4b34e-2cbf-4ffa-8bea-20e0e46ce4dd" />
<img width="1906" height="921" alt="image" src="https://github.com/user-attachments/assets/2462b026-e9a5-430e-a336-166fd604c638" />
<img width="1884" height="916" alt="image" src="https://github.com/user-attachments/assets/17bd67fe-1f86-4ef5-ab89-462f564048fb" />

### Theme Manager List Page

![Theme List
<img width="1917" height="915" alt="image" src="https://github.com/user-attachments/assets/eeaa2980-6c90-4147-8120-e6304b1da675" />


### CSS Theme Selection UI

![CSS Theme
Screenshot](https://raw.githubusercontent.com/YourUser/YourRepo/main/screenshots/css-themes.png)

------------------------------------------------------------------------

# ✨ Features Overview

## ✔ Installation Script (install.sh) --- v2.0.6
``` bash
sudo bash install.sh
```

### 1. Patch File Handling

Backs up and replaces required Hestia system files: - web/index.php\
- web/list/index.php\
- web/inc/main.php\
- web/login/index.php\
- templates/pages/edit_server.php\
- templates/includes/panel.php

### 2. Dashboard Setup

Creates `/usr/local/hestia/web/list/dashboard/`\
Copies `dashboard_index.php → index.php`

### 3. Theme Interface Setup

Creates `/usr/local/hestia/web/list/theme/`\
Copies `theme_index.php → index.php`

### 4. Themes List Page

Adds `/usr/local/hestia/web/list/list_themes.php`

### 5. CSS Theme Installation (Improved)

Copies all CSS except: - style.css\
- color_theme.css

To:

    /usr/local/hestia/web/css/themes/custom/

### 6. Verification System

Prevent installs if patch files are missing.

### 7. Backup System

Backs up original files to:

    /usr/local/hestia/plugins/theme-manager/backups/original-files/

### 8. Backend Scripts

Creates: - v-change-user-theme\
- v-change-user-css-theme

### 9. CLI Wrapper

Installs:

    /usr/local/hestia/bin/hestia-theme

### 10. Theme Developer Guide

Creates `/usr/local/hestia/web/themes/README.md`

### 11. Logrotate

Adds `/etc/logrotate.d/hestia-theme-manager`

------------------------------------------------------------------------

# 🗑 Uninstallation --- v2.0.6

### Fully restores:

-   All original Hestia files\
-   Dashboard + theme pages\
-   Custom CSS themes\
-   CLI wrapper\
-   Backend scripts\
-   Logs + rotation configs\
-   Entire plugin directory

### Backs up themes to:

    /tmp/hestia-themes-backup-YYYYMMDD-HHMMSS/

------------------------------------------------------------------------

# 📁 File Structure

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

------------------------------------------------------------------------

# 🛠 Installation

``` bash
sudo bash install.sh
```

------------------------------------------------------------------------

# 🗑 Uninstallation

Interactive:

``` bash
sudo bash uninstall.sh
```

Force:

``` bash
sudo bash uninstall.sh force
```

------------------------------------------------------------------------

# 🌐 Web Interface URLs

  Page            URL
  --------------- -----------------------
  Dashboard       /list/dashboard/
  Theme Manager   /list/theme/
  Themes List     /list/list_themes.php

------------------------------------------------------------------------

# 🧰 CLI Usage

``` bash
hestia-theme list
hestia-theme list-css
hestia-theme apply <template> <css>
hestia-theme css <css-theme>
hestia-theme current
hestia-theme status
```

Theme Installation:

    hestia-theme install-theme /path/to/theme.zip
    hestia-theme install-theme /path/to/theme-dir

Theme Removal:

    hestia-theme uninstall-theme <name>

------------------------------------------------------------------------

# 📌 Requirements

-   HestiaCP\
-   PHP 7.4+\
-   Bash\
-   Root access

------------------------------------------------------------------------

# 🛡 Safety Features

✔ Full backups\
✔ Patch verification\
✔ Safe rollback\
✔ Theme backups\
✔ Permission enforcement\
✔ Log rotation

------------------------------------------------------------------------

# 📄 Logs

  Type               Location
  ------------------ --------------------------------------------------
  Theme changes      /var/log/hestia/theme-changes.log
  Plugin logs        /usr/local/hestia/plugins/theme-manager/logs/
  Original backups   /usr/local/hestia/plugins/theme-manager/backups/
  Theme backups      /tmp/hestia-themes-backup-\*

------------------------------------------------------------------------

# ❓ Support

Run:

    hestia-theme status

Check logs: - /var/log/hestia/theme-changes.log\
- /usr/local/hestia/plugins/theme-manager/logs/

------------------------------------------------------------------------


