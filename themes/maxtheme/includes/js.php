<script defer src="/js/dist/main.min.js?<?= JS_LATEST_UPDATE ?>"></script>
<script defer src="/js/dist/alpinejs-collapse.min.js?<?= JS_LATEST_UPDATE ?>"></script>
<script defer src="/js/dist/alpinejs.min.js?<?= JS_LATEST_UPDATE ?>"></script>
<?php
// Same reasoning as includes/css.php: a <script src> pointing under
// /templates/ gets 404'd by the panel's nginx config, so read the
// theme's own JS directly off disk and inline it instead.
$theme_js_path = $_SERVER["HESTIA"] . "/web/templates/js/maxtheme.min.js";
if (file_exists($theme_js_path)) {
	echo "<script>" . file_get_contents($theme_js_path) . "</script>";
}
?>

<script>
	document.documentElement.classList.replace('no-js', 'js');
	document.addEventListener('alpine:init', () => {
		Alpine.store('globals', {
			USER_PREFIX: '<?= $user_plain ?>_',
			UNLIMITED: '<?= _("Unlimited") ?>',
			NOTIFICATIONS_EMPTY: '<?= _("No notifications") ?>',
			NOTIFICATIONS_DELETE_ALL: '<?= _("Delete all notifications") ?>',
			CONFIRM_LEAVE_PAGE: '<?= _("Are you sure you want to leave the page?") ?>',
			ERROR_MESSAGE: '<?= !empty($_SESSION["error_msg"]) ? htmlentities($_SESSION["error_msg"],ENT_QUOTES) : "" ?>',
			BLACKLIST: '<?= _("BLACKLIST") ?>',
			IPVERSE: '<?= _("IPVERSE") ?>'
		});
	})
</script>
<?php $_SESSION["unset_alerts"] = true; ?>

<?php
$customScriptDirectory = new DirectoryIterator($_SERVER["HESTIA"] . "/web/js/custom_scripts");
foreach ($customScriptDirectory as $customScript) {
	$extension = $customScript->getExtension();
	if ($extension === "js") {
		$customScriptPath = "/js/custom_scripts/" . rawurlencode($customScript->getBasename());
		echo '<script defer src="' . $customScriptPath . '"></script>';
	} elseif ($extension === "php") {
		require_once $customScript->getPathname();
	}
} ?>
