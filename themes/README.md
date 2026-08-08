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
