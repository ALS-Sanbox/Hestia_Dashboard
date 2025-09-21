<?php
session_start();
header("Location: /" . (isset($_SESSION["user"]) ? "list/dashboard" : "login") . "/");
