# Hestia Theme Manager

A plugin for [HestiaCP](https://hestiacp.com/) that adds a custom dashboard and full theme-switching support: swap the entire panel's look (a **Dashboard Theme**) and its accent palette (a **Color Theme**) independently, per-server or per-user, from the web UI or the CLI.

Current version: **2.1.1**

---

## What it does

- **Alt Dashboard** - an optional custom landing page at `/list/dashboard/`, toggled on from Configure Server. When off, the panel behaves exactly like stock Hestia.
- **Dashboard Theme** - swaps the *entire* template set (every page, not just the dashboard) by re-pointing Hestia's `web/templates` symlink. Selectable from Configure Server (admin, server-wide default) when Alt Dashboard is on.
- **Color Theme** - swaps just the accent CSS on top of whichever Dashboard Theme is active. Selectable from both Configure Server (server-wide default) and Edit User (per-user override) - the dropdown is filtered to only show colors that actually belong to the currently active Dashboard Theme.
- **CLI** - the `hestia-theme` command covers everything the web UI does, plus installing/removing/backing up custom themes.

Bundled themes:

| Theme | Color Theme options |
|---|---|
| `dark_glass_theme` | dark_glass_theme_color (default), orange, emerald, crimson, ocean, amethyst, sunset |
| `glass_theme` | glass_theme_color (default), glass_darkmode_theme, sunrise, forest, berry, slate, violet |
| `maxtheme` | 28 variants (14 dark + 14 light) - blue, indigo, red, teal, cyan, orange, rose, zinc, gray, pink, sky, green, purple, stone |
| `panel_theme` | classic_blue (default), jupiter_teal, slate_gray, forest_green, graphite |

---

## Installation

```bash
sudo bash install.sh
```

Must be run as root on a server with HestiaCP already installed. What it does, in order:

1. Verifies required files are present and Hestia is installed.
2. Backs up any pre-existing `theme-manager` plugin install to `/tmp/` (mode 700).
3. Copies the plugin's own files into `/usr/local/hestia/plugins/theme-manager/` and runs its PHP-side install (this snapshots `web/templates/` as the plugin's own "pristine original" backup, used by `hestia-theme uninstall` later) - **before** touching any core Hestia files, so that snapshot is genuinely untouched.
4. Backs up the original copies of the core Hestia files it's about to patch (skipped if a backup already exists from a previous install, so re-running never clobbers the true originals with an already-patched copy):
   - `web/index.php`, `web/list/index.php`, `web/inc/main.php`, `web/login/index.php`
   - `web/templates/pages/edit_server.php`, `web/templates/includes/panel.php`
5. Applies the patched versions of those same 6 files from `patch_files/`.
6. Deploys `dashboard_index.php` and `dashboard_toggle.php` to `/list/dashboard/`, and the dashboard page template into the active template set.
7. Sets `ALT_DASHBOARD=false` by default (existing installs keep landing on the stock Users list until an admin opts in), and patches Hestia's `v-list-sys-config` in-place so the `ALT_DASHBOARD` setting is actually exposed to `$_SESSION` (idempotent - only patches if not already present).
8. Deploys every theme under `themes/` to both the "active" themes directory (`web/themes/`, what `web/templates` symlinks into) and the theme gallery (`web/list/theme/`, which also holds each theme's own `edit_server.php`/`edit_user.php`/`panel.php` so switching Dashboard Theme never strands anyone without those controls). Any theme's own `images/` folder gets deployed to `/images/theme/<name>/`.
9. Installs each theme's Color Theme CSS files into `/usr/local/hestia/web/css/themes/custom/` (skips any file literally named `style.css` or `color_theme.css` - those are structural/template files, not deployable color variants).
10. Patches a real PHP 8.3 crash in Hestia's own `edit/server/index.php` and `edit/user/index.php` controllers (both check `$_POST["v_theme"]`/`v_user_theme` unconditionally on every request, which throws a fatal `TypeError` on PHP 8.3 once a theme value is set in the session - patched in-place with a minimal `sed`, not a full file copy, since these are large core files that change often between Hestia releases).
11. Creates the backend scripts (`v-change-user-theme`, `v-change-user-css-theme`), configures `sudo` so the `hestiaweb` user can run them, sets up the theme-change log and its logrotate config, installs the `hestia-theme` CLI wrapper, and drops a theme-development guide into the themes directory.

No Dashboard Theme is force-applied on install - the panel keeps behaving exactly as it did before until an admin turns Alt Dashboard on and picks one.

---

## Uninstallation

```bash
sudo bash uninstall.sh          # interactive, asks for confirmation
sudo bash uninstall.sh force    # non-interactive, skips confirmation
sudo bash uninstall.sh help     # show usage
```

What it does:

1. Backs up the custom `themes/` directory to `/tmp/hestia-themes-backup-<timestamp>/` (mode 700) so themes can be restored after a reinstall.
2. Restores the original copies of every file `install.sh` patched: `web/index.php`, `web/list/index.php`, `web/inc/main.php`, `web/login/index.php`, `web/templates/pages/edit_server.php`, `web/templates/includes/panel.php` - plus, if they were touched, the in-place patches to `v-list-sys-config` and the `edit/server`/`edit/user` controllers.
3. Removes the dashboard folder (`/list/dashboard/`) and the theme gallery folder (`/list/theme/`), along with any legacy `/list/themes/` (plural) directory left over from older versions.
4. Removes every custom Color Theme CSS file from `web/css/themes/custom/`.
5. Removes the active themes directory (`web/themes/`) and any per-theme images deployed to `web/images/theme/`.
6. Runs the plugin's own PHP-side uninstall (restores from its "pristine original" snapshot), falling back to a manual restore from `$PLUGIN_DIR/backups/original/` if that fails.
7. Removes the backend scripts, the sudoers file, the theme-change log, the `hestia-theme` CLI command, the logrotate config, and finally the plugin directory itself.

After uninstalling, the panel is back to stock HestiaCP behavior - nothing patched, no custom theme active.

---

## Web UI

With Alt Dashboard on:

- **Configure Server** (`/edit/server/`) - Alt Dashboard toggle, Dashboard Theme dropdown, Color Theme dropdown (server-wide default, admin only).
- **Edit User** (`/edit/user/`) - Color Theme dropdown, scoped to that one user's personal preference. A user's own choice always takes priority over the server default, but never affects anyone else.
- **Dashboard** (`/list/dashboard/`) - the custom landing page, once Alt Dashboard is enabled.

All three controls save instantly via AJAX (`/list/dashboard/toggle.php`) - no full-page form submit, no CSRF-page detour.

---

## CLI

```
hestia-theme [command] [arguments]

Core Commands:
  install                            Install the theme manager plugin
  uninstall                          Uninstall plugin and restore original
  apply <theme> [css]                Apply a Dashboard Theme, optionally with a Color Theme
  css <theme>                        Apply only a Color Theme
  list                                List available Dashboard Themes
  list-css                            List available Color Themes
  current                             Show current active themes
  status                              Show detailed system status
  debug                               Show debug information

Theme Management:
  install-theme <source> [name] [-f]  Install a theme from a ZIP or directory
  uninstall-theme <name> [-f] [-b]    Uninstall a theme
  list-installed                      List installed themes with metadata
  theme-info <name>                   Show detailed theme information
  validate-theme <name>               Validate a theme's structure

Backup Management:
  backup-theme <name> [backup_name]   Create a theme backup
  list-backups                        List available backups
  restore-backup <name>               Restore a theme from backup

Options:
  -f, --force      Force operation (overwrite/uninstall active theme)
  -b, --backup     Create a backup before uninstall
  -h, --help       Show detailed help
```

Examples:

```bash
hestia-theme list
hestia-theme apply panel_theme jupiter_teal_color_theme
hestia-theme css sunset_color_theme
hestia-theme current
hestia-theme install-theme /path/to/theme.zip my-theme
hestia-theme uninstall-theme old-theme --backup
```

---

## Building a custom theme

```
themes/my-theme/
├── theme.json              # name, description, css_theme (default), themes[] (color whitelist)
├── header.php
├── footer.php
├── includes/
│   ├── css.php              # inlines style.css + links the active Color Theme CSS
│   ├── js.php
│   └── panel.php             # sidebar / navigation
├── pages/
│   ├── list_dashboard.php   # only needed if you want a custom dashboard body
│   ├── list_user.php
│   └── ...                  # one file per Hestia page
├── pages/login/
│   └── ...
├── css/
│   ├── style.css             # structural CSS, inlined (not linked - see below)
│   ├── color_theme.css       # template used to derive Color Theme variants (never deployed directly)
│   └── my_variant_color.css # an actual selectable Color Theme
└── images/                   # optional; deployed to /images/theme/my-theme/
```

`theme.json`'s `themes` array is a whitelist - only CSS files listed there (and actually present in the deployed `css/` directory) show up in the Color Theme dropdown for this Dashboard Theme.

**Important:** never link `style.css`/theme JS as a URL under `/templates/` - Hestia's panel nginx config blocks everything under that path to stop `.php` source files from being downloaded, which silently 404s CSS/JS too. Read the file with `file_get_contents()` and inline it in a `<style>`/`<script>` block instead (see any bundled theme's `includes/css.php` for the pattern).

---

## Requirements

- HestiaCP installed
- PHP 7.4+ (PHP 8.3 is explicitly supported - see the null-guard patch above)
- Root access, Bash

---

## Logs & backups

| What | Where |
|---|---|
| Plugin logs | `/usr/local/hestia/plugins/theme-manager/logs/` |
| Theme change log | `/var/log/hestia/theme-changes.log` |
| Install-time backups (original core files, pristine template snapshot) | `/usr/local/hestia/plugins/theme-manager/backups/` |
| Uninstall-time theme backup | `/tmp/hestia-themes-backup-<timestamp>/` (mode 700) |

Run `hestia-theme status` for a live system check, or `hestia-theme debug` for detailed diagnostics.

See `change_log.txt` for the full version history.
