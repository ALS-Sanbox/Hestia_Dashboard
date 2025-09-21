## **UPDATE 9-15-2025**
This new update changes the installiation precedures to be more automated than before. The only thing that is currenlty still manual is the css file that handles colors.
When you create a theme you still at the moment have to create css themes for the different colors you want supported under the normal css/themes/custom folder and then in the theme.json file use the same name to set the default css theme you want used.

# **Installation Instructions:**
1. Download and extract the Theme_Manager folder
2. Run
   ```bash
   bash install.sh
   ```

# To uninstall just run 
```bash 
bash uninstall.sh
```

## **Theme Creation:**
1. Make a copy of the glass theme
2. Make changes as desired
3. *Important* I seperated out the css into two files the one in the theme folder controls everything but the color settings. I create a seperate css file and place it in the ```/usr/local/hestia/css/themes/custom``` folder and use that file name in the theme.json file to load the color settings. THis is done to allow for different color themes.
   
## **Installation Script Features:**

1. Patch File Handling
Creates backups of original Hestia files before overwriting them
Apply Patches:
patch_files/list_index.php → ```/usr/local/hestia/web/list/index.php```
patch_files/main.php → ```/usr/local/hestia/web/inc/main.php```
patch_files/login_index.php → ```/usr/local/hestia/web/login/index.php```

2. Dashboard Setup
Creates ```/usr/local/hestia/web/list/dashboard/```directory
Copies dashboard_index.php to ```/usr/local/hestia/web/list/dashboard/index.php```
Copies glass_color_theme.css to ```/usr/local/hestia/web/css/themes/custom/```

3. File Verification
Added verify_patch_files() function to ensure all required files exist before installation
Checks for the required directory structure

4. Enhanced Backup System
Creates $PLUGIN_DIR/backups/original-files/ for storing original patched files
Maintains separate backups for theme files and patched system files

## **Uninstallation Script Features:**

1. Original File Restoration
restore_original_patched_files() function restores the backed-up original files
Properly restores file permissions and ownership

2. Dashboard Cleanup
Removes ```/usr/local/hestia/web/list/dashboard/``` directory
Removes custom CSS theme files
Cleans up empty directories

3. Comprehensive Restoration
Restores both theme files and patched system files
Maintains the existing theme restoration functionality

File Structure:
```
Theme_Manager/
├── install.sh (modified)
├── uninstall.sh (modified)
├── patch_files/
│   ├── list_index.php
│   ├── main.php
│   └── login_index.php
├── dashboard_index.php
├── glass_color_theme.css
├── hestia_theme_manager.php
└── themes/ (optional)
```

## **WIP**
1. Add in the ability to change the dashboard(skin) via GUI
2. Add in the ability to add themes through GUI
