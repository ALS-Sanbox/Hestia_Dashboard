<div id="token" token="<?= $_SESSION["token"] ?>"></div>

<header class="app-header-vertical">
    <div class="sidebar" id="sidebar">

        <!-- Logo -->
        <div class="sidebar-logo">
            <a href="/" title="<?= htmlentities($_SESSION["APP_NAME"]) ?>">
                <img src="/images/logo-header.svg" alt="<?= htmlentities($_SESSION["APP_NAME"]) ?>" class="sidebar-logo-img">
                <span class="sidebar-label"><?= htmlentities($_SESSION["APP_NAME"]) ?></span>
            </a>
        </div>

        <!-- Usage -->
        <div class="sidebar-usage">
            <?php if ($_SESSION["look"] !== "") {
                $user_icon = "fa-binoculars";
            } elseif ($_SESSION["userContext"] === "admin") {
                $user_icon = "fa-user-tie";
            } else {
                $user_icon = "fa-user";
            } ?>
            <div class="sidebar-usage-inner">
                <div class="sidebar-usage-item">
                    <i class="fas <?= $user_icon ?>"></i>
                    <span class="sidebar-label u-text-bold"><?= htmlspecialchars($user) ?></span>
                </div>
                <div class="sidebar-usage-item">
                    <i class="fas fa-hard-drive"></i>
                    <span class="sidebar-label"><?= humanize_usage_size($panel[$user]["U_DISK"]) ?> / <?= humanize_usage_size($panel[$user]["DISK_QUOTA"]) ?></span>
                </div>
                <div class="sidebar-usage-item">
                    <i class="fas fa-right-left"></i>
                    <span class="sidebar-label"><?= humanize_usage_size($panel[$user]["U_BANDWIDTH"]) ?> / <?= humanize_usage_size($panel[$user]["BANDWIDTH"]) ?></span>
                </div>
            </div>
        </div>
	
        <!-- Tabs -->
        <nav class="sidebar-tabs">
            <ul>
			<li><a href="/list/themes" class="<?php if (in_array($TAB, ["THEMES"])) echo "active"; ?>"><i class="fa-solid fa-paintbrush"></i> <span class="sidebar-label"><?= _("Themes")?></span></a></li>
			<li><a href="/list/dashboard/" class="<?php if (in_array($TAB, ["DASHBOARD"])) echo "active"; ?>"><i class="fas fa-gauge"></i> <span class="sidebar-label"><?= _("Dashboard") ?></span></a>
			</li>
<?php
// Only show Users menu if the logged-in user is truly admin and not impersonating
if (
    isset($_SESSION["user"]) && $_SESSION["user"] === "admin" &&
    isset($_SESSION["userContext"]) && strtolower($_SESSION["userContext"]) === "admin" &&
    empty($_SESSION["look"])
):
?>
<li>
    <a href="/list/user/"
       class="<?php if (in_array($TAB, ["USER", "LOG"])) echo "active"; ?>"
       data-toggle="submenu">
        <i class="fas fa-users"></i>
        <span class="sidebar-label"><?= _("Users") ?></span>
    </a>

    <!-- Submenu -->
    <ul class="sidebar-submenu">
        <li>
            <a href="/add/user/">
                <i class="fas fa-user-plus"></i>
                <span class="sidebar-label"><?= _("Add User") ?></span>
            </a>
        </li>
        <li>
            <a href="/list/package/">
                <i class="fas fa-box"></i>
                <span class="sidebar-label"><?= _("Add Package") ?></span>
            </a>
        </li>
    </ul>
</li>
<?php endif; ?>
		
                <li><a href="/list/web/" class="<?php if (in_array($TAB, ["WEB"])) echo "active"; ?>" data-toggle="submenu"><i class="fas fa-earth-americas"></i> <span class="sidebar-label"><?= _("Web") ?></span></a>
				<!-- Submenu -->
					<ul class="sidebar-submenu">
						<li>
							<a href="/add/web/"><i class="fas fa-user-plus"></i> <span class="sidebar-label"><?= _("Add Web") ?></span></a>
						</li>
					</ul></li>
                <li><a href="/list/dns/" class="<?php if (in_array($TAB, ["DNS"])) echo "active"; ?>"  data-toggle="submenu"><i class="fas fa-book-atlas"></i> <span class="sidebar-label"><?= _("DNS") ?></span></a>
				<!-- Submenu -->
					<ul class="sidebar-submenu">
						<li>
							<a href="/add/dns/"><i class="fas fa-user-plus"></i> <span class="sidebar-label"><?= _("Add DNS") ?></span></a>
						</li>
					</ul></li>
                <li><a href="/list/mail/" class="<?php if (in_array($TAB, ["MAIL"])) echo "active"; ?>"  data-toggle="submenu"><i class="fas fa-envelopes-bulk"></i> <span class="sidebar-label"><?= _("Mail") ?></span></a>
				<!-- Submenu -->
					<ul class="sidebar-submenu">
						<li>
							<a href="/add/mail/"><i class="fas fa-user-plus"></i> <span class="sidebar-label"><?= _("Add Mail") ?></span></a>
						</li>
					</ul></li>
                <li><a href="/list/db/" class="<?php if (in_array($TAB, ["DB"])) echo "active"; ?>"  data-toggle="submenu"><i class="fas fa-database"></i> <span class="sidebar-label"><?= _("Databases") ?></span></a>
				<!-- Submenu -->
					<ul class="sidebar-submenu">
						<li>
							<a href="/add/db/"><i class="fas fa-user-plus"></i> <span class="sidebar-label"><?= _("Add Database") ?></span></a>
						</li>
					</ul></li>
                <li><a href="/list/cron/" class="<?php if (in_array($TAB, ["CRON"])) echo "active"; ?>"  data-toggle="submenu"><i class="fas fa-clock"></i> <span class="sidebar-label"><?= _("Cron Jobs") ?></span></a>
				<!-- Submenu -->
					<ul class="sidebar-submenu">
						<li>
							<a href="/add/cron/"><i class="fas fa-user-plus"></i> <span class="sidebar-label"><?= _("Add Cron") ?></span></a>
						</li>
					</ul></li>
                <li><a href="/list/backup/" class="<?php if (in_array($TAB, ["BACKUP"])) echo "active"; ?>"  data-toggle="submenu"><i class="fas fa-file-zipper"></i> <span class="sidebar-label"><?= _("Backups") ?></span></a>
				<!-- Submenu -->
					<ul class="sidebar-submenu">
						<li>
							<a href="/add/cron/"><i class="fas fa-user-plus"></i> <span class="sidebar-label"><?= _("Add Cron") ?></span></a>
						</li>
					</ul></li></li>
            </ul>
        </nav>
        <!-- Menu -->
        <nav class="sidebar-menu">
            <ul>
<!-- Edit User -->
<?php if ($_SESSION["userContext"] === "admin" && ($_SESSION["look"] !== "" && $user == "admin")) { ?>
<!-- Hide 'edit user' entry point from other administrators for default 'admin' account-->
<li class="top-bar-menu-item">
<a title="<?= _("Logs") ?>"  class="<?php if (in_array($TAB, ["LOG"])) echo "active"; ?>"  href="/list/log/">
<i class="fas fa-clock-rotate-left"></i>
<span class="sidebar-label"><?= _("Logs") ?></span>
</a>
</li>
<?php } else { ?>
<?php if ($panel[$user]["SUSPENDED"] === "no") { ?>
<li class="top-bar-menu-item">
<a title="<?= htmlspecialchars($user) ?> (<?= htmlspecialchars($panel[$user]["NAME"]) ?>)" href="/edit/user/?user=<?= $user ?>&token=<?= $_SESSION["token"] ?>">
<i class="fas fa-circle-user"></i>
<span class="sidebar-label"><?= _("Edit User") ?> (<?= htmlspecialchars($panel[$user]["NAME"]) ?>)</span>
</a>
</li>
<?php } ?>
<?php } ?>			
			
<!-- File Manager -->
<?php if (isset($_SESSION["FILE_MANAGER"]) && !empty($_SESSION["FILE_MANAGER"]) && $_SESSION["FILE_MANAGER"] == "true") { ?>
<?php if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] === "admin" && $_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] == "yes") { ?>
<!-- Hide file manager when impersonating admin-->
<?php } else { ?>
<li><a title="<?= _("File manager") ?>" class=" <?php if ($TAB == "FM") {
echo "active";
} ?>" href="/fm/"><i class="fas fa-folder-open"></i> <span class="sidebar-label"><?= _("File Manager") ?></span></a></li>
<?php } ?>
<?php } ?>
				
				
<!-- Web Terminal -->
<?php if (isset($_SESSION["WEB_TERMINAL"]) && !empty($_SESSION["WEB_TERMINAL"]) && $_SESSION["WEB_TERMINAL"] == "true") { ?>
<?php if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] === "admin" && $_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] == "yes") { ?>
<!-- Hide web terminal when impersonating admin -->
<?php } elseif ($_SESSION["login_shell"] != "nologin") { ?>
<li><a title="<?= _("Web terminal") ?>" class="<?php if ($TAB == "TERMINAL") {
echo "active";
} ?>" href="/list/terminal/"><i class="fas fa-terminal"></i> <span class="sidebar-label"><?= _("Web Terminal") ?></span></a></li>
<?php } ?>
<?php } ?>
				
<!-- Server Settings -->
<?php if (($_SESSION["userContext"] === "admin" && $_SESSION["POLICY_SYSTEM_HIDE_SERVICES"] !== "yes") || $_SESSION["user"] === "admin") { ?>
<?php if ($_SESSION["userContext"] === "admin" && $_SESSION["look"] !== "") { ?>
<!-- Hide 'Server Settings' button when impersonating 'admin' or other users -->
<?php } else { ?>
<li class="top-bar-menu-item">
<a title="<?= _("Server settings") ?>" class="<?php if (in_array($TAB, ["SERVER", "IP", "RRD", "FIREWALL"])) { echo "active"; } ?>" href="/list/server/">
<i class="fas fa-gear"></i>
<span class="sidebar-label"><?= _("Server Settings") ?></span>
</a>
</li>
<?php } ?>
<?php } ?>
				
<!-- Statistics -->
<li class="top-bar-menu-item">
<a title="<?= _("Statistics") ?>" class="<?php if (in_array($TAB, ["STATS"])) echo "active"; ?>"  href="/list/stats/">
<i class="fas fa-chart-line"></i>
<span class="sidebar-label"><?= _("Statistics") ?></span>
</a>
</li>
<?php if ($_SESSION["HIDE_DOCS"] !== "yes") { ?>
<!-- Help / Documentation -->
<li class="top-bar-menu-item">
<a title="<?= _("Help") ?>" href="https://hestiacp.com/docs/" target="_blank" rel="noopener">
<i class="fas fa-circle-question"></i>
<span class="sidebar-label"><?= _("Help") ?></span>
</a>
</li>
<?php } ?>
				
<!-- Logout -->
<?php if (isset($_SESSION["look"]) && !empty($_SESSION["look"])) { ?>
<li class="top-bar-menu-item">
<a title="<?= _("Log out") ?> (<?= $user ?>)" href="/logout/?token=<?= $_SESSION["token"] ?>">
<i class="fas fa-circle-up"></i>
<span class="sidebar-label"><?= _("Log out") ?>(<?= $user ?>)</span>
</a>
</li>
<?php } else { ?>
<li class="top-bar-menu-item">
<a title="<?= _("Log out") ?>" href="/logout/?token=<?= $_SESSION["token"] ?>">
<i class="fas fa-right-from-bracket"></i>
<span class="sidebar-label"><?= _("Log out") ?></span>
</a>
</li>
<?php } ?>
            </ul>
        </nav>
    </div>
</header>
