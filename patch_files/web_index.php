<?php
session_start();
header("Location: /" . (isset($_SESSION["user"]) ? "list/" . ($_SESSION["ALT_DASHBOARD"] === "true" ? "dashboard" : "user") : "login") . "/");
