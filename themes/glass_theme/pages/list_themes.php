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
<?php
// Only show menu if the logged-in user is truly admin and not impersonating
if (
    isset($_SESSION["user"]) && $_SESSION["user"] === "admin" &&
    isset($_SESSION["userContext"]) && strtolower($_SESSION["userContext"]) === "admin" &&
    empty($_SESSION["look"])
):
?>
		<!-- Quick Dashboard Theme Selection -->
			<div class="card u-mb20">
				<div class="card-header">
					<h2><i class="fas fa-magic"></i> <?= _("Dashboard Theme Change") ?></h2>
					<p class="card-description"><?= _("Change only the dashboard theme without affecting your css template") ?></p>
				</div>
				<div class="card-body">
					<form method="post" class="css-theme-form">
						<div class="form-inline-group">
							<label for="dashboard_theme_quick" class="form-label"><?= _("Dashboard Theme") ?></label>
							<select class="form-control" name="dashboard_theme" id="dashboard_theme_quick">
								<?php foreach ($available_themes as $theme) : ?>
									<option value="<?= htmlspecialchars($theme) ?>" 
										<?= ($theme === $current_theme) ? 'selected' : '' ?>>
										<?= htmlspecialchars(ucwords(str_replace(["-", "_"], " ", $theme))) ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button type="submit" name="set_dashboard_theme" class="button button-secondary">
								<i class="fas fa-bolt"></i> <?= _("Apply Dashboard Only") ?>
							</button>
						</div>
					</form>
				</div>
			</div>		
<?php endif; ?>
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

	</div>
<?php else : ?>
	<div class="alert alert-info">
		<i class="fas fa-lock"></i> <?= _("Theme changes are restricted by system policy.") ?>
	</div>
<?php endif; ?>