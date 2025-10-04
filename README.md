# **Installation Instructions**

1. Download and extract the Theme_Manager folder

   **ZIP version:**
   ```bash
   wget https://github.com/ALS-Sanbox/Hestia_Dashboard/releases/download/v2.0.0/Theme_Manager.zip
   unzip Theme_Manager.zip
   ```
   
   **TAR.GZ version:**
   ```bash
   wget https://github.com/ALS-Sanbox/Hestia_Dashboard/releases/download/v2.0.0/Theme_Manager.tar.gz
   tar -xzf Theme_Manager.tar.gz
   ```

2. Enter the extracted folder:
   ```bash
   cd Theme_Manager
   ```

3. Run the installation script:
   ```bash
   bash install.sh
   ```

4. Set Glass Theme as active:
   ```bash
   hestia-theme apply glass_theme
   ```

## **What's Next?**

After installation, you can run `hestia-theme status` to see the current theme status and `hestia-theme list` to view currently available themes.

### Usage
```bash
php hestia_theme_manager.php [install|uninstall|apply|css|list|list-css|current|status]
```

### Commands:
- `install` - Install the theme manager
- `uninstall` - Uninstall and restore original
- `apply <theme> [css]` - Apply template theme with optional CSS theme
- `css <theme>` - Apply only CSS theme
- `list` - List available template themes
- `list-css` - List available CSS themes
- `current` - Show current active themes
- `status` - Show detailed system status

### To Uninstall
```bash 
bash uninstall.sh
```
## **Themes Included**
Dark Glass Theme 
<img width="1900" height="916" alt="darkglass_theme" src="https://github.com/user-attachments/assets/e3f427c9-21b0-4bf0-80ca-cd702e36ad01" />

Glass Theme
<img width="1900" height="916" alt="glass_theme" src="https://github.com/user-attachments/assets/0788cdfd-7410-41eb-be50-12c7574c8c4e" />

## **Theme Creation**
1. Make a copy of the glass theme
2. Make changes as desired
3. **Important:** I separated the CSS into two files. The one called theme.php is the settings and the one color_theme is all the color settings this allows for other color variants to be created.

## **Work In Progress (WIP)**
1. When on the user dashboard need to make it where the boxes reflect the users data and their options
2. Add the ability to add themes through GUI -- only working with cli currently
<img width="1843" height="674" alt="Capture" src="https://github.com/user-attachments/assets/262d6439-cfca-422e-964b-98aa6172c9c5" />

## **BUGS**
1. The Hestia way of switching css themes broke and needs to be fixed I think its due to a wrapper script???
2. If you choose the original theme you have to go back through the command line to change the dashboard again - original files need the menu link for themes added
