<?php
session_start();
if (isset($_SESSION["user"])) {
	header("Location: /list/dashboard/");
} else {
	header("Location: /login/");
}
?>
