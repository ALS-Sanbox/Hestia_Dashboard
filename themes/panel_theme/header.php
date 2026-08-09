<!doctype html>
<html class="no-js" lang="<?= $_SESSION["LANGUAGE"] ?>">

<head>
<?php
require $_SERVER["HESTIA"] . "/web/templates/includes/title.php";
require $_SERVER["HESTIA"] . "/web/templates/includes/css.php";
require $_SERVER["HESTIA"] . "/web/templates/includes/js.php";
?>
</head>

<body class="page-<?= strtolower($TAB) ?> lang-<?= $_SESSION["language"] ?>">
	<div class="app">
		<div class="cp-topbar">
			<div class="cp-topbar-brand">
				<img src="/images/logo-header.svg" alt="<?= htmlentities($_SESSION["APP_NAME"]) ?>" class="cp-topbar-logo">
				<span class="cp-topbar-appname"><?= htmlentities($_SESSION["APP_NAME"]) ?></span>
			</div>
			<?php if (!empty($_SESSION["user"])): ?>
			<div class="cp-topbar-user">
				<i class="fas fa-circle-user"></i>
				<span class="cp-topbar-username"><?= htmlspecialchars($_SESSION["user"]) ?></span>
			</div>
			<?php endif; ?>
		</div>