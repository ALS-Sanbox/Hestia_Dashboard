<?php
session_start();
if (isset($_SESSION["user"])) {
	header("Location: /list/" . ($_SESSION["ALT_DASHBOARD"] === "true" ? "dashboard" : "user") . "/");
} else {
	header("Location: /login/");
}
?>
