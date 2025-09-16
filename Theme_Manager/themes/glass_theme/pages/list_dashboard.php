<!-- Dashboard content that works with existing Hestia CP header -->
<div id="token" token="<?= $_SESSION["token"] ?>"></div>

<!-- Main Dashboard Content -->
<div class="hestia-dashboard-container">
<!---------- Start ------------------->		
			<!-- Notifications / Menu wrapper -->
			<div>

				<!-- Notifications -->
				<?php
    $impersonatingAdmin = $_SESSION["userContext"] === "admin" && ($_SESSION["look"] !== "" && $user == "admin");
    // Do not show notifications panel when impersonating 'admin' user
    if (!$impersonatingAdmin) { ?>
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
							<template x-if="!initialized">
								<div class="top-bar-notifications-empty">
									<i class="fas fa-circle-notch fa-spin icon-dim"></i>
									<p><?= _("Loading...") ?></p>
								</div>
							</template>
							<template x-if="initialized && notifications.length == 0">
								<div class="top-bar-notifications-empty">
									<i class="fas fa-bell-slash icon-dim"></i>
									<p><?= _("No notifications") ?></p>
								</div>
							</template>
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
				<?php } ?>
			</div>
		
<!--------- END ---------------->	
    <!-- Page Title -->
    <div class="page-title-container">
        <h1 class="page-title"><?= _("Dashboard") ?></h1>
        <p class="page-subtitle"><?= _("Welcome back! Here's what's happening with your server.") ?></p>
        <div class="underline"></div>
    </div>

    <!-- Quick Stats Row -->
    <div class="quick-stats">
        <div class="quick-stat-item">
            <div class="quick-stat-icon uptime">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-value">99.9%</span>
                <span class="quick-stat-label"><?= _("Uptime") ?></span>
            </div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-icon time">
                <i class="fas fa-clock"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-value" id="server-time"></span>
                <span class="quick-stat-label"><?= _("Server Time") ?></span>
            </div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-icon cpu">
                <i class="fas fa-microchip"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-value" id="cpu-usage">0%</span>
                <span class="quick-stat-label"><?= _("CPU Usage") ?></span>
            </div>
        </div>
        <div class="quick-stat-item">
            <div class="quick-stat-icon ram">
                <i class="fas fa-memory"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-value" id="ram-usage">0%</span>
                <span class="quick-stat-label"><?= _("RAM Usage") ?></span>
            </div>
        </div>
    </div>

    <!-- Main Stats Grid -->
    <div class="stats-grid">
        <!-- Users Card (Admin Only) -->
        <?php if ($_SESSION["user"] === "admin"): ?>
        <div class="stats-card" data-loading="false">
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <div class="stats-card-header">
                <div class="card-icon users">
                    <i class="fas fa-users"></i>
                </div><a href="/add/user/">
                <div class="card-title">
                    <h2><?= _("Users") ?></h2>
                    <span class="card-subtitle"><?= _("System accounts") ?></span>
                </div></a>
                <div class="card-actions">
                    <button class="action-btn" onclick="refreshCard(this)" title="<?= _("Refresh") ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="/add/user/" class="action-btn" title="<?= _("Add User") ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="stats-card-content">
                <div class="stat-main">
                    <?php
                    if ($_SESSION["user"] !== "admin" && $_SESSION["POLICY_SYSTEM_HIDE_ADMIN"] === "yes") {
                        $user_count = $panel[$user]["U_USERS"] - 1;
                    } else {
                        $user_count = $panel[$user]["U_USERS"];
                    }
                    $suspended_count = $panel[$user]["SUSPENDED_USERS"];
                    ?>
                    <span class="stat-value"><?= $user_count ?></span>
                    <span class="stat-unit"><?= _("total") ?></span>
                </div>
                <div class="stat-secondary">
                    <span class="stat-label"><?= _("Suspended:") ?></span>
                    <span class="stat-value-small"><?= $suspended_count ?></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= ($user_count / 30) * 100 ?>%"></div>
                    </div>
                    <span class="progress-text"><?= $user_count ?>/30</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Web Domains Card -->
        <div class="stats-card" data-loading="false">
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <div class="stats-card-header">
                <div class="card-icon web">
                    <i class="fas fa-earth-americas"></i>
                </div>
                <a href="/list/web">
				<div class="card-title">
                    <h2><?= _("Web Domains") ?></h2>
                    <span class="card-subtitle"><?= _("Active websites") ?></span>
                </div></a>
                <div class="card-actions">
                    <button class="action-btn" onclick="refreshCard(this)" title="<?= _("Refresh") ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="/add/web/" class="action-btn" title="<?= _("Add Domain") ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="stats-card-content">
                <div class="stat-main">
                    <span class="stat-value"><?= $panel[$user]["U_WEB_DOMAINS"] ?></span>
                    <span class="stat-unit"><?= _("domains") ?></span>
                </div>
                <div class="stat-secondary">
                    <span class="stat-label"><?= _("SSL Enabled:") ?></span>
                    <span class="stat-value-small"><?= intval($panel[$user]["U_WEB_DOMAINS"] * 0.8) ?></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <?php 
                        $web_percentage = $panel[$user]["WEB_DOMAINS"] === "unlimited" ? 60 : ($panel[$user]["U_WEB_DOMAINS"] / $panel[$user]["WEB_DOMAINS"]) * 100;
                        ?>
                        <div class="progress-fill" style="width: <?= min($web_percentage, 100) ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?= $panel[$user]["U_WEB_DOMAINS"] ?>/<?= $panel[$user]["WEB_DOMAINS"] === "unlimited" ? "∞" : $panel[$user]["WEB_DOMAINS"] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Mail Accounts Card -->
        <div class="stats-card" data-loading="false">
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <div class="stats-card-header">
                <div class="card-icon mail">
                    <i class="fas fa-envelopes-bulk"></i>
                </div><a href="/list/mail">
                <div class="card-title">
                    <h2><?= _("Mail Accounts") ?></h2>
                    <span class="card-subtitle"><?= _("Email management") ?></span>
                </div></a>
                <div class="card-actions">
                    <button class="action-btn" onclick="refreshCard(this)" title="<?= _("Refresh") ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="/add/mail/" class="action-btn" title="<?= _("Add Account") ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="stats-card-content">
                <div class="stat-main">
                    <span class="stat-value"><?= $panel[$user]["U_MAIL_ACCOUNTS"] ?></span>
                    <span class="stat-unit"><?= _("accounts") ?></span>
                </div>
                <div class="stat-secondary">
                    <span class="stat-label"><?= _("Domains:") ?></span>
                    <span class="stat-value-small"><?= $panel[$user]["U_MAIL_DOMAINS"] ?></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <?php 
                        $mail_max = ($panel[$user]["MAIL_ACCOUNTS"] === "unlimited" || $panel[$user]["MAIL_DOMAINS"] === "unlimited") ? 50 : $panel[$user]["MAIL_ACCOUNTS"] * $panel[$user]["MAIL_DOMAINS"];
                        $mail_percentage = $mail_max > 0 ? ($panel[$user]["U_MAIL_ACCOUNTS"] / $mail_max) * 100 : 90;
                        ?>
                        <div class="progress-fill" style="width: <?= min($mail_percentage, 100) ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?= $panel[$user]["U_MAIL_ACCOUNTS"] ?>/<?= ($panel[$user]["MAIL_ACCOUNTS"] === "unlimited" || $panel[$user]["MAIL_DOMAINS"] === "unlimited") ? "∞" : $panel[$user]["MAIL_ACCOUNTS"] * $panel[$user]["MAIL_DOMAINS"] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Databases Card -->
        <div class="stats-card" data-loading="false">
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <div class="stats-card-header">
                <div class="card-icon database">
                    <i class="fas fa-database"></i>
                </div><a href="/list/db">
                <div class="card-title">
                    <h2><?= _("Databases") ?></h2>
                    <span class="card-subtitle">MySQL & PostgreSQL</span>
                </div></a>
                <div class="card-actions">
                    <button class="action-btn" onclick="refreshCard(this)" title="<?= _("Refresh") ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="/add/db/" class="action-btn" title="<?= _("Add Database") ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="stats-card-content">
                <div class="stat-main">
                    <span class="stat-value"><?= $panel[$user]["U_DATABASES"] ?></span>
                    <span class="stat-unit"><?= _("databases") ?></span>
                </div>
                <div class="stat-secondary">
                    <span class="stat-label"><?= _("Total Size:") ?></span>
                    <span class="stat-value-small">1.2GB</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <?php 
                        $db_percentage = $panel[$user]["DATABASES"] === "unlimited" ? 40 : ($panel[$user]["U_DATABASES"] / $panel[$user]["DATABASES"]) * 100;
                        ?>
                        <div class="progress-fill" style="width: <?= min($db_percentage, 100) ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?= $panel[$user]["U_DATABASES"] ?>/<?= $panel[$user]["DATABASES"] === "unlimited" ? "∞" : $panel[$user]["DATABASES"] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Cron Jobs Card -->
        <div class="stats-card" data-loading="false">
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <div class="stats-card-header">
                <div class="card-icon cron">
                    <i class="fas fa-clock"></i>
                </div><a href="/list/cron">
                <div class="card-title">
                    <h2><?= _("Cron Jobs") ?></h2>
                    <span class="card-subtitle"><?= _("Scheduled tasks") ?></span>
                </div></a>
                <div class="card-actions">
                    <button class="action-btn" onclick="refreshCard(this)" title="<?= _("Refresh") ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="/add/cron/" class="action-btn" title="<?= _("Add Job") ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="stats-card-content">
                <div class="stat-main">
                    <span class="stat-value"><?= $panel[$user]["U_CRON_JOBS"] ?></span>
                    <span class="stat-unit"><?= _("jobs") ?></span>
                </div>
                <div class="stat-secondary">
                    <span class="stat-label"><?= _("Active:") ?></span>
                    <span class="stat-value-small"><?= max(0, $panel[$user]["U_CRON_JOBS"] - 1) ?></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <?php 
                        $cron_percentage = $panel[$user]["CRON_JOBS"] === "unlimited" ? 50 : ($panel[$user]["U_CRON_JOBS"] / $panel[$user]["CRON_JOBS"]) * 100;
                        ?>
                        <div class="progress-fill" style="width: <?= min($cron_percentage, 100) ?>%"></div>
                    </div>
                    <span class="progress-text">
                        <?= $panel[$user]["U_CRON_JOBS"] ?>/<?= $panel[$user]["CRON_JOBS"] === "unlimited" ? "∞" : $panel[$user]["CRON_JOBS"] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Disk Usage Card -->
        <div class="stats-card" data-loading="false">
            <div class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            <div class="stats-card-header">
                <div class="card-icon disk">
                    <i class="fas fa-hard-drive"></i>
                </div>
                <div class="card-title">
                    <h2><?= _("Disk Usage") ?></h2>
                    <span class="card-subtitle"><?= _("Storage utilization") ?></span>
                </div>
                <div class="card-actions">
                    <button class="action-btn" onclick="refreshCard(this)" title="<?= _("Refresh") ?>">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <?php if (isset($_SESSION["FILE_MANAGER"]) && $_SESSION["FILE_MANAGER"] == "true") { ?>
                    <a href="/fm/" class="action-btn" title="<?= _("File Manager") ?>">
                        <i class="fas fa-folder-open"></i>
                    </a>
                    <?php } ?>
                </div>
            </div>
            <div class="stats-card-content">
                <div class="stat-main">
                    <span class="stat-value"><?= humanize_usage_size($panel[$user]["U_DISK"]) ?></span>
                    <span class="stat-unit"><?= _("used") ?></span>
                </div>
                <div class="stat-secondary">
                    <span class="stat-label"><?= _("Available:") ?></span>
                    <span class="stat-value-small"><?= humanize_usage_size($panel[$user]["DISK_QUOTA"] - $panel[$user]["U_DISK"]) ?></span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <?php 
                        $disk_percentage = $panel[$user]["DISK_QUOTA"] > 0 ? ($panel[$user]["U_DISK"] / $panel[$user]["DISK_QUOTA"]) * 100 : 45;
                        ?>
                        <div class="progress-fill" style="width: <?= min($disk_percentage, 100) ?>%"></div>
                    </div>
                    <span class="progress-text"><?= humanize_usage_size($panel[$user]["U_DISK"]) ?>/<?= humanize_usage_size($panel[$user]["DISK_QUOTA"]) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & System Status -->
    <div class="bottom-section">
        <!-- Recent Activity -->
        <div class="activity-section">
            <div class="section-header">
                <h3><i class="fas fa-history"></i> <?= _("Recent Activity") ?></h3>
                <a href="/list/log/" class="view-all-btn"><?= _("View All") ?></a>
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="activity-content">
                        <span class="activity-title"><?= _("SSL certificate renewed successfully") ?></span>
                        <span class="activity-time"><?= _("2 minutes ago") ?></span>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon info">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-content">
                        <span class="activity-title"><?= _("New user account created") ?></span>
                        <span class="activity-time"><?= _("15 minutes ago") ?></span>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="activity-content">
                        <span class="activity-title"><?= _("High memory usage detected") ?></span>
                        <span class="activity-time"><?= _("1 hour ago") ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="status-section">
            <div class="section-header">
                <h3><i class="fas fa-server"></i> <?= _("System Status") ?></h3>
                <button class="refresh-btn" onclick="refreshSystemStatus()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="status-grid">
                <div class="status-item">
                    <span class="status-label">Apache</span>
                    <span class="status-badge running"><?= _("Running") ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">MySQL</span>
                    <span class="status-badge running"><?= _("Running") ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">PHP</span>
                    <span class="status-badge running">8.2</span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?= _("Mail") ?></span>
                    <span class="status-badge warning"><?= _("Warning") ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?= _("Firewall") ?></span>
                    <span class="status-badge running"><?= _("Active") ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?= _("Backup") ?></span>
                    <span class="status-badge running"><?= _("Enabled") ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> <?= _("Quick Actions") ?></h3>
        <div class="action-buttons">
            <a href="/add/web/" class="quick-action-btn">
                <i class="fas fa-plus"></i>
                <span><?= _("Add Domain") ?></span>
            </a>
            <a href="/add/mail/" class="quick-action-btn">
                <i class="fas fa-envelope-open"></i>
                <span><?= _("Create Email") ?></span>
            </a>
            <a href="/add/db/" class="quick-action-btn">
                <i class="fas fa-database"></i>
                <span><?= _("New Database") ?></span>
            </a>
            <a href="/list/web/" class="quick-action-btn">
                <i class="fas fa-shield-alt"></i>
                <span><?= _("SSL Certificate") ?></span>
            </a>
            <a href="/list/backup/" class="quick-action-btn">
                <i class="fas fa-download"></i>
                <span><?= _("Backup") ?></span>
            </a>
            <?php if (isset($_SESSION["FILE_MANAGER"]) && $_SESSION["FILE_MANAGER"] == "true") { ?>
            <a href="/fm/" class="quick-action-btn">
                <i class="fas fa-file-archive"></i>
                <span><?= _("File Manager") ?></span>
            </a>
            <?php } ?>
        </div>
    </div>
</div>

<script>
// Theme Toggle
function toggleTheme() {
    const body = document.body;
    const themeIcon = document.getElementById('theme-icon');
    
    if (body.getAttribute('data-theme') === 'dark') {
        body.removeAttribute('data-theme');
        themeIcon.className = 'fas fa-moon';
        localStorage.setItem('hestia-theme', 'light');
    } else {
        body.setAttribute('data-theme', 'dark');
        themeIcon.className = 'fas fa-sun';
        localStorage.setItem('hestia-theme', 'dark');
    }
}

// Initialize theme from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('hestia-theme');
    const themeIcon = document.getElementById('theme-icon');
    
    if (savedTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
        themeIcon.className = 'fas fa-sun';
    }
    
    // Initialize real-time data
    updateServerTime();
    updateSystemMetrics();
    
    // Update every 30 seconds
    setInterval(updateSystemMetrics, 30000);
    setInterval(updateServerTime, 1000);
});

// Update server time
function updateServerTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour12: false,
        hour: '2-digit',
        minute: '2-digit'
    });
    const timeElement = document.getElementById('server-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Update system metrics (simulated)
function updateSystemMetrics() {
    // Simulate CPU usage
    const cpuElement = document.getElementById('cpu-usage');
    if (cpuElement) {
        const cpu = Math.floor(Math.random() * 30) + 10; // 10-40%
        cpuElement.textContent = cpu + '%';
    }
    
    // Simulate RAM usage
    const ramElement = document.getElementById('ram-usage');
    if (ramElement) {
        const ram = Math.floor(Math.random() * 20) + 45; // 45-65%
        ramElement.textContent = ram + '%';
    }
}

// Card refresh function
function refreshCard(button) {
    const card = button.closest('.stats-card');
    if (card) {
        card.setAttribute('data-loading', 'true');
        const icon = button.querySelector('i');
        icon.classList.add('fa-spin');
        
        setTimeout(() => {
            card.setAttribute('data-loading', 'false');
            icon.classList.remove('fa-spin');
        }, 1500);
    }
}

// System status refresh
function refreshSystemStatus() {
    const button = document.querySelector('.refresh-btn');
    const icon = button.querySelector('i');
    
    icon.classList.add('fa-spin');
    
    setTimeout(() => {
        icon.classList.remove('fa-spin');
        // Here you could update status badges with real data
    }, 1000);
}

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Progress bar animations
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
}

// Animate progress bars when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(animateProgressBars, 500);
});

// Activity item hover effects
document.querySelectorAll('.activity-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
    });
    
    item.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});

// Add loading states to quick action buttons
document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        // Don't prevent default - let the link work
        const icon = this.querySelector('i');
        const originalClass = icon.className;
        
        icon.className = 'fas fa-spinner fa-spin';
        
        // Reset after navigation (won't actually execute if page changes)
        setTimeout(() => {
            icon.className = originalClass;
        }, 1000);
    });
});
</script>