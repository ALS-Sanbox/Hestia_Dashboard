<link rel="alternate icon" href="/images/favicon.png" type="image/png">
<link rel="icon" href="/images/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/css/themes/default.min.css?<?= JS_LATEST_UPDATE ?>">
<?php
// Read the theme's own style.css directly off disk (through the templates
// symlink) and inline it, rather than linking to it as a URL. Hestia's
// panel nginx blanket-blocks everything under /templates/ to stop .php
// source files from being downloaded, which also 404s a <link href> here -
// a direct file read never touches nginx, same as how this include itself
// gets loaded by header.php.
// Relative url(../images/...) references inside style.css need rewriting:
// since the CSS is now inlined into the page rather than linked, the
// browser would otherwise resolve them against the page's own URL instead
// of the stylesheet's location - and /templates/images/ is blocked by
// nginx anyway, so images are deployed to the accessible /images/theme/
// path instead (see install.sh).
$style_css_path = $_SERVER["HESTIA"] . "/web/templates/css/style.css";
if (file_exists($style_css_path)) {
	$style_css = file_get_contents($style_css_path);
	$style_css = str_replace("../images/", "/images/theme/glass_theme/", $style_css);
	echo "<style>" . $style_css . "</style>";
}
?>

<?php
$selected_theme = !empty($_SESSION["userTheme"]) ? $_SESSION["userTheme"] : $_SESSION["THEME"];
// Load non-default theme
if ($selected_theme !== "default") {
	// Load HestiaCP-shipped themes (minified, updated/overwritten with updates) - ($HESTIA/web/css/themes/*.min.css)
	$non_default_theme_path = $_SERVER["HESTIA"] . "/web/css/themes/" . $selected_theme . ".min.css";
	if (file_exists($non_default_theme_path)) {
		echo '<link rel="stylesheet" href="/css/themes/' . $selected_theme . ".min.css?" . JS_LATEST_UPDATE . '">';
	}
	// Load custom theme files ($HESTIA/web/css/themes/custom/*.css)
	else {
		$custom_theme_path = $_SERVER["HESTIA"] . "/web/css/themes/custom/" . $selected_theme . ".min.css";
		if (file_exists($custom_theme_path)) {
			echo '<link rel="stylesheet" href="/css/themes/custom/' . $selected_theme . ".min.css?" . JS_LATEST_UPDATE . '">';
		} else {
			echo '<link rel="stylesheet" href="/css/themes/custom/' . $selected_theme . ".css?" . JS_LATEST_UPDATE . '">';
		}
	}
}

?>
