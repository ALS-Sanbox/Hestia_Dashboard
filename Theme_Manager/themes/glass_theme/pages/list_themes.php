<!-- Begin page title -->
<div class="page-title-container">
    <h1 class="page-title"><?= _("Themes") ?></h1>
    <div class="underline"></div>
</div>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/dashboard/">
				<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<button type="submit" class="button" form="main-form">
				<i class="fas fa-floppy-disk icon-purple"></i><?= _("Save") ?>
			</button>
		</div>
	</div>
</div>

<!-- Display Success/Error Messages -->
<?php if (!empty($_SESSION["error_msg"])) : ?>
	<div class="alert alert-danger">
		<i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION["error_msg"]) ?>
	</div>
	<?php $_SESSION["error_msg"] = ""; ?>
<?php endif; ?>

<?php if (!empty($_SESSION["ok_msg"])) : ?>
	<div class="alert alert-success">
		<i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION["ok_msg"]) ?>
	</div>
	<?php $_SESSION["ok_msg"] = ""; ?>
<?php endif; ?>

<!-- Main Theme Selection Form -->
<?php if ($_SESSION["POLICY_USER_CHANGE_THEME"] !== "no") : ?>
	<div class="theme-selection-container">
		
		<!-- Combined Theme Selection -->
		<div class="card u-mb20">
			<div class="card-header">
				<h2><i class="fas fa-palette"></i> <?= _("Complete Theme Selection") ?></h2>
				<p class="card-description"><?= _("Select both dashboard template and CSS theme together") ?></p>
			</div>
			<div class="card-body">
				<form id="main-form" method="post">
					<div class="form-row">
						<div class="form-group u-mb20">
							<label for="theme_name" class="form-label">
								<i class="fas fa-layer-group"></i> <?= _("Dashboard Template Theme") ?>
							</label>
							<select class="form-control" name="theme_name" id="theme_name" required>
								<?php foreach ($available_themes as $theme) : ?>
									<option value="<?= htmlspecialchars($theme) ?>" 
										<?= ($theme === $current_theme) ? 'selected' : '' ?>>
										<?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $theme))) ?>
									</option>
								<?php endforeach; ?>
							</select>
							<small class="form-text text-muted">
								<?= _("Current template theme:") ?> <strong><?= htmlspecialchars($current_theme) ?></strong>
							</small>
						</div>

						<div class="form-group u-mb20">
							<label for="css_theme_main" class="form-label">
								<i class="fas fa-paintbrush"></i> <?= _("CSS Theme") ?>
							</label>
							<select class="form-control" name="css_theme" id="css_theme_main" required>
								<?php foreach ($available_css_themes as $css_theme) : ?>
									<option value="<?= htmlspecialchars($css_theme) ?>" 
										<?= ($css_theme === $current_css_theme) ? 'selected' : '' ?>>
										<?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $css_theme))) ?>
									</option>
								<?php endforeach; ?>
							</select>
							<small class="form-text text-muted">
								<?= _("Current CSS theme:") ?> <strong><?= htmlspecialchars($current_css_theme) ?></strong>
							</small>
						</div>
					</div>

					<div class="form-actions">
						<button type="submit" name="apply_theme" class="button button-primary">
							<i class="fas fa-check"></i> <?= _("Apply Both Themes") ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Quick CSS Theme Selection -->
		<div class="card u-mb20">
			<div class="card-header">
				<h2><i class="fas fa-magic"></i> <?= _("Quick CSS Theme Change") ?></h2>
				<p class="card-description"><?= _("Change only the CSS theme without affecting your dashboard template") ?></p>
			</div>
			<div class="card-body">
				<form method="post" class="css-theme-form">
					<div class="form-inline-group">
						<label for="css_theme_quick" class="form-label"><?= _("CSS Theme") ?></label>
						<select class="form-control" name="css_theme" id="css_theme_quick">
							<?php foreach ($available_css_themes as $css_theme) : ?>
								<option value="<?= htmlspecialchars($css_theme) ?>" 
									<?= ($css_theme === $current_css_theme) ? 'selected' : '' ?>>
									<?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $css_theme))) ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" name="set_css_theme" class="button button-secondary">
							<i class="fas fa-bolt"></i> <?= _("Apply CSS Only") ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Theme Preview/Information -->
		<div class="card">
			<div class="card-header">
				<h2><i class="fas fa-info-circle"></i> <?= _("Theme Information") ?></h2>
			</div>
			<div class="card-body">
				<div class="theme-info-grid">
					<div class="info-item">
						<span class="info-label"><?= _("Available Template Themes:") ?></span>
						<span class="info-value"><?= count($available_themes) ?></span>
					</div>
					<div class="info-item">
						<span class="info-label"><?= _("Available CSS Themes:") ?></span>
						<span class="info-value"><?= count($available_css_themes) ?></span>
					</div>
					<div class="info-item">
						<span class="info-label"><?= _("Active Template:") ?></span>
						<span class="info-value"><code><?= htmlspecialchars($current_theme) ?></code></span>
					</div>
					<div class="info-item">
						<span class="info-label"><?= _("Active CSS Theme:") ?></span>
						<span class="info-value"><code><?= htmlspecialchars($current_css_theme) ?></code></span>
					</div>
				</div>
			</div>
		</div>

	</div>

<?php else : ?>
	<div class="alert alert-info">
		<i class="fas fa-lock"></i> <?= _("Theme changes are restricted by system policy.") ?>
	</div>
<?php endif; ?>
