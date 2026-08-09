<div id="token" token="<?= $_SESSION["token"] ?>"></div>

<!---------- Start ------------------->		
<!-- Notifications / Menu wrapper -->
<div>
    <!-- Notifications -->
    <?php
    $impersonatingAdmin = $_SESSION["userContext"] === "admin" 
        && ($_SESSION["look"] !== "" && $user == "admin");
    // Do not show notifications panel when impersonating 'admin' user
    if (!$impersonatingAdmin): ?>
        <div x-data="notifications" class="top-bar-notifications">
            <button
                x-on:click="toggle()"
                x-bind:class="open && 'active'"
                class="top-bar-menu-link"
                type="button"
                title="<?= _("Notifications") ?>"
            >
                <i
                    x-bind:class="{
                        'animate__animated animate__swing icon-orange': (!initialized && <?= $panel[$user]["NOTIFICATIONS"] == "yes" ? "true" : "false" ?>) || notifications.length != 0,
                        'fas fa-bell': true
                    }"
                ></i>
                <span class="u-hidden"><?= _("Notifications") ?></span>
            </button>

            <div
                x-cloak
                x-show="open"
                x-on:click.outside="open = false"
                class="top-bar-notifications-panel"
            >
                <!-- Loading -->
                <template x-if="!initialized">
                    <div class="top-bar-notifications-empty">
                        <i class="fas fa-circle-notch fa-spin icon-dim"></i>
                        <p><?= _("Loading...") ?></p>
                    </div>
                </template>

                <!-- No notifications -->
                <template x-if="initialized && notifications.length == 0">
                    <div class="top-bar-notifications-empty">
                        <i class="fas fa-bell-slash icon-dim"></i>
                        <p><?= _("No notifications") ?></p>
                    </div>
                </template>

                <!-- Notifications list -->
                <template x-if="initialized && notifications.length > 0">
                    <ul>
                        <template x-for="notification in notifications" :key="notification.ID">
                            <li
                                x-bind:id="`notification-${notification.ID}`"
                                x-bind:class="notification.ACK && 'unseen'"
                                class="top-bar-notification-item"
                                x-data="{ open: true }"
                                x-show="open"
                                x-collapse
                            >
                                <div class="top-bar-notification-inner">
                                    <div class="top-bar-notification-header">
                                        <p x-text="notification.TOPIC" class="top-bar-notification-title"></p>
                                        <button
                                            x-on:click="open = false; setTimeout(() => remove(notification.ID), 300);"
                                            type="button"
                                            class="top-bar-notification-delete"
                                            title="<?= _("Delete notification") ?>"
                                        >
                                            <i class="fas fa-xmark"></i>
                                            <span class="u-hidden-visually"><?= _("Delete notification") ?></span>
                                        </button>
                                    </div>
                                    <div class="top-bar-notification-content" x-html="notification.NOTICE"></div>
                                    <p class="top-bar-notification-timestamp">
                                        <time
                                            :datetime="`${notification.TIMESTAMP_ISO}`"
                                            x-bind:title="`${notification.TIMESTAMP_TITLE}`"
                                            x-text="`${notification.TIMESTAMP_TEXT}`"
                                        ></time>
                                    </p>
                                </div>
                            </li>
                        </template>
                    </ul>
                </template>

                <!-- Delete all -->
                <template x-if="initialized && notifications.length > 2">
                    <button
                        x-on:click="removeAll()"
                        type="button"
                        class="top-bar-notifications-delete-all"
                    >
                        <i class="fas fa-check"></i>
                        <?= _("Delete all notifications") ?>
                    </button>
                </template>
            </div>
        </div>
    <?php endif; ?>
</div>
<!--------- END ---------------->		
<div class="hestia-dashboard-container">
	<!-- Page Title -->
	<div class="page-title-container" style="align-items: flex-start; text-align: left; margin-bottom: 20px;">
		<h1 class="page-title"><?= _("Dashboard") ?></h1>
	</div>

	<div class="cp-dashboard-columns">

		<!-- Column 1: Tools -->
		<div class="cp-tools-column">
			<div class="page-title-container" style="align-items: flex-start; text-align: left; margin-bottom: 15px;">
				<h2 class="cp-panel-heading-title" style="font-size: 1.2em;"><?= _("Tools") ?></h2>
			</div>
			<div class="cp-tools-grid">

				<!-- Files -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-blue);"><i class="fas fa-folder-open"></i></div>
						<div class="cp-panel-heading-title"><?= _("Files") ?></div>
					</div>
					<div class="cp-panel-body">
						<?php if (isset($_SESSION["FILE_MANAGER"]) && !empty($_SESSION["FILE_MANAGER"]) && $_SESSION["FILE_MANAGER"] == "true"): ?>
						<a href="/fm/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-blue);"><i class="fas fa-folder-open"></i></div>
							<span class="cp-item-text"><?= _("File Manager") ?></span>
						</a>
						<?php endif; ?>
						<a href="/list/backup/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-blue);"><i class="fas fa-download"></i></div>
							<span class="cp-item-text"><?= _("Backups") ?></span>
						</a>
					</div>
				</div>

				<!-- Databases -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-orange);"><i class="fas fa-database"></i></div>
						<div class="cp-panel-heading-title"><?= _("Databases") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/list/db/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-orange);"><i class="fas fa-database"></i></div>
							<span class="cp-item-text"><?= _("Databases") ?></span>
						</a>
						<a href="/add/db/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-orange);"><i class="fas fa-plus"></i></div>
							<span class="cp-item-text"><?= _("New Database") ?></span>
						</a>
					</div>
				</div>

				<!-- Domains -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-purple);"><i class="fas fa-earth-americas"></i></div>
						<div class="cp-panel-heading-title"><?= _("Domains") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/list/web/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-purple);"><i class="fas fa-earth-americas"></i></div>
							<span class="cp-item-text"><?= _("Web Domains") ?></span>
						</a>
						<a href="/add/web/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-purple);"><i class="fas fa-plus"></i></div>
							<span class="cp-item-text"><?= _("Add Domain") ?></span>
						</a>
						<a href="/list/dns/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-purple);"><i class="fas fa-book-atlas"></i></div>
							<span class="cp-item-text"><?= _("DNS Zones") ?></span>
						</a>
						<a href="/generate/ssl/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-purple);"><i class="fas fa-shield-alt"></i></div>
							<span class="cp-item-text"><?= _("SSL Certificate") ?></span>
						</a>
					</div>
				</div>

				<!-- Email -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-teal);"><i class="fas fa-envelopes-bulk"></i></div>
						<div class="cp-panel-heading-title"><?= _("Email") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/list/mail/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-teal);"><i class="fas fa-envelopes-bulk"></i></div>
							<span class="cp-item-text"><?= _("Mail Accounts") ?></span>
						</a>
						<a href="/add/mail/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-teal);"><i class="fas fa-envelope-open"></i></div>
							<span class="cp-item-text"><?= _("Create Email") ?></span>
						</a>
					</div>
				</div>

				<!-- Metrics -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-green);"><i class="fas fa-chart-line"></i></div>
						<div class="cp-panel-heading-title"><?= _("Metrics") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/list/stats/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-green);"><i class="fas fa-chart-line"></i></div>
							<span class="cp-item-text"><?= _("Statistics") ?></span>
						</a>
						<a href="/list/log/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-green);"><i class="fas fa-history"></i></div>
							<span class="cp-item-text"><?= _("Logs") ?></span>
						</a>
					</div>
				</div>

				<!-- Security -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-red);"><i class="fas fa-shield-halved"></i></div>
						<div class="cp-panel-heading-title"><?= _("Security") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/list/firewall/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-red);"><i class="fas fa-shield-halved"></i></div>
							<span class="cp-item-text"><?= _("Firewall") ?></span>
						</a>
						<a href="/list/ip/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-red);"><i class="fas fa-network-wired"></i></div>
							<span class="cp-item-text"><?= _("IP Management") ?></span>
						</a>
					</div>
				</div>

				<!-- Advanced -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: var(--icon-color-maroon);"><i class="fas fa-code"></i></div>
						<div class="cp-panel-heading-title"><?= _("Advanced") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/list/cron/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-maroon);"><i class="fas fa-clock"></i></div>
							<span class="cp-item-text"><?= _("Cron Jobs") ?></span>
						</a>
						<?php if (isset($_SESSION["WEB_TERMINAL"]) && !empty($_SESSION["WEB_TERMINAL"]) && $_SESSION["WEB_TERMINAL"] == "true" && $_SESSION["login_shell"] != "nologin"): ?>
						<a href="/list/terminal/" class="cp-item">
							<div class="cp-item-icon" style="background: var(--icon-color-maroon);"><i class="fas fa-terminal"></i></div>
							<span class="cp-item-text"><?= _("Web Terminal") ?></span>
						</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Preferences -->
				<div class="cp-panel">
					<div class="cp-panel-heading">
						<div class="cp-panel-heading-icon" style="background: #64748b;"><i class="fas fa-sliders"></i></div>
						<div class="cp-panel-heading-title"><?= _("Preferences") ?></div>
					</div>
					<div class="cp-panel-body">
						<a href="/edit/user/?user=<?= $user ?>&token=<?= $_SESSION["token"] ?>" class="cp-item">
							<div class="cp-item-icon" style="background: #64748b;"><i class="fas fa-circle-user"></i></div>
							<span class="cp-item-text"><?= _("Edit Profile") ?></span>
						</a>
						<?php if ($_SESSION["userContext"] === "admin" && empty($_SESSION["look"])): ?>
						<a href="/list/server/" class="cp-item">
							<div class="cp-item-icon" style="background: #64748b;"><i class="fas fa-gear"></i></div>
							<span class="cp-item-text"><?= _("Server Settings") ?></span>
						</a>
						<?php endif; ?>
					</div>
				</div>

			</div>
		</div>

		<!-- Column 2: General Information -->
		<div class="cp-info-column">

			<div class="cp-panel">
				<div class="cp-panel-heading">
					<div class="cp-panel-heading-title"><?= _("General Information") ?></div>
				</div>
				<div class="cp-info-list">
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Current User") ?></span>
						<span class="cp-info-value"><?= htmlspecialchars($user) ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Primary Domain") ?></span>
						<span class="cp-info-value"><?= htmlspecialchars($primaryDomain) ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("SSL Certificate") ?></span>
						<span class="cp-info-value <?= $sslStatus === "Active" ? "cp-ssl-active" : "cp-ssl-inactive" ?>"><?= _($sslStatus) ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("IP Address") ?></span>
						<span class="cp-info-value"><?= htmlspecialchars($primaryDomainIp) ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Last Login IP") ?></span>
						<span class="cp-info-value"><?= htmlspecialchars($lastLoginIp) ?></span>
					</div>
				</div>
			</div>

			<div class="cp-panel">
				<div class="cp-panel-heading">
					<div class="cp-panel-heading-title"><?= _("Statistics") ?></div>
				</div>
				<div class="cp-info-list">
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Disk Usage") ?></span>
						<span class="cp-info-value"><?= humanize_usage_size($panel[$user]["U_DISK"]) ?> / <?= $panel[$user]["DISK_QUOTA"] === "unlimited" ? "&#8734;" : humanize_usage_size($panel[$user]["DISK_QUOTA"]) ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Bandwidth") ?></span>
						<span class="cp-info-value"><?= humanize_usage_size($panel[$user]["U_BANDWIDTH"]) ?> / <?= $panel[$user]["BANDWIDTH"] === "unlimited" ? "&#8734;" : humanize_usage_size($panel[$user]["BANDWIDTH"]) ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Web Domains") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_WEB_DOMAINS"] ?> / <?= $panel[$user]["WEB_DOMAINS"] === "unlimited" ? "&#8734;" : $panel[$user]["WEB_DOMAINS"] ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Domain Aliases") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_WEB_ALIASES"] ?? 0 ?> / <?= ($panel[$user]["WEB_ALIASES"] ?? "unlimited") === "unlimited" ? "&#8734;" : $panel[$user]["WEB_ALIASES"] ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Email Accounts") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_MAIL_ACCOUNTS"] ?> / <?= $panel[$user]["MAIL_ACCOUNTS"] === "unlimited" ? "&#8734;" : $panel[$user]["MAIL_ACCOUNTS"] ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Mail Domains") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_MAIL_DOMAINS"] ?> / <?= $panel[$user]["MAIL_DOMAINS"] === "unlimited" ? "&#8734;" : $panel[$user]["MAIL_DOMAINS"] ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Databases") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_DATABASES"] ?> / <?= $panel[$user]["DATABASES"] === "unlimited" ? "&#8734;" : $panel[$user]["DATABASES"] ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("DNS Zones") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_DNS_DOMAINS"] ?> / <?= $panel[$user]["DNS_DOMAINS"] === "unlimited" ? "&#8734;" : $panel[$user]["DNS_DOMAINS"] ?></span>
					</div>
					<div class="cp-info-row">
						<span class="cp-info-label"><?= _("Cron Jobs") ?></span>
						<span class="cp-info-value"><?= $panel[$user]["U_CRON_JOBS"] ?> / <?= $panel[$user]["CRON_JOBS"] === "unlimited" ? "&#8734;" : $panel[$user]["CRON_JOBS"] ?></span>
					</div>
				</div>
			</div>

		</div>

	</div>
</div>
