# Screenshots

Drop one image per theme in this folder using the exact filenames below - the main `README.md` already links to them, so nothing else needs to change once they're here.

| Filename | Theme |
|---|---|
| `dark_glass_theme.png` | Dark Glass Theme |
| `glass_theme.png` | Glass Theme |
| `maxtheme.png` | Max Theme |
| `panel_theme.png` | Panel Theme |

## How to capture one

1. On the server: `sudo hestia-theme apply <theme-name>` (e.g. `sudo hestia-theme apply panel_theme`), or use the Dashboard Theme dropdown on Configure Server if Alt Dashboard is on.
2. Log into the panel in a browser and go to `/list/dashboard/` (with Alt Dashboard on) or any list page (e.g. `/list/web/`) - whichever best shows off the theme.
3. Take a full-page screenshot (browser dev tools → "Capture full size screenshot" gives a clean one without needing to scroll-stitch).
4. Save as PNG, ideally cropped to just the browser viewport (no OS chrome/taskbar), and keep it under ~1MB - resize to ~1600px wide if needed.
5. Save it into this folder using the exact filename from the table above, then commit and push.

## Adding a new theme later

Add a row to the two tables in the main `README.md`'s Screenshots section and drop a matching image in here.
