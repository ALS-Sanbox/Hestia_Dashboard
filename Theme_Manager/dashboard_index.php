<?php
use function Hestiacp\quoteshellarg\quoteshellarg;
$TAB = "DASHBOARD";

include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$user = $_SESSION['user'] ?? null;
if (!$user) {
    die("No user logged in.");
}

// Get system services status
exec(HESTIA_CMD . "v-list-sys-services json", $output, $return_var);
$allServices = json_decode(implode("", $output), true);
unset($output);

// Filter only the services you want
$wanted = [
    "apache2"      => "Apache",
    "php8.3-fpm"   => "PHP 8.3",
    "mariadb"      => "Database",
    "exim4"        => "Mail",
    "iptables"     => "Firewall"
];

$services = [];
foreach ($wanted as $key => $label) {
    if (isset($allServices[$key])) {
        $services[$label] = $allServices[$key]['STATE'];
    } else {
        $services[$label] = "unknown"; // fallback
    }
}

// Keep user stats if you still need them
exec(HESTIA_CMD . "v-list-user-stats $user json", $output, $return_var);
$panel[$user] = json_decode(implode("", $output), true);
unset($output);

//Get system quick stats
exec(HESTIA_CMD . "v-list-sys-info json", $output, $return_var);
$sysinfoRaw = json_decode(implode("", $output), true);
$sysinfo = $sysinfoRaw['sysinfo'] ?? [];
unset($output);

// Calculate uptime in days/hours/mins
$uptimeMinutes = intval($sysinfo['UPTIME'] ?? 0);
$days  = floor($uptimeMinutes / 1440);
$hours = floor(($uptimeMinutes % 1440) / 60);
$mins  = $uptimeMinutes % 60;
$uptimeFormatted = "{$days}d {$hours}h {$mins}m";

// Calculate CPU usage as % of cores (Option 3)
$cpuCores = (int) trim(shell_exec("nproc"));  // total cores
$loadRaw = $sysinfo['LOADAVERAGE'] ?? '';
$loadParts = preg_split('/\s*\/\s*/', $loadRaw);
$load1 = (float) ($loadParts[0] ?? 0);
$cpuUsage = $cpuCores > 0 ? round(($load1 / $cpuCores) * 100, 1) . "%" : "N/A";

// RAM usage
$totalRam = (int) ($sysinfo['MEMORY'] ?? 0);   // total MB
$usedRam  = (int) ($sysinfo['RAM'] ?? 0);      // used MB
$ramUsagePercent = $totalRam > 0 ? round(($usedRam / $totalRam) * 100, 1) : 0;
$ramUsageFormatted = "{$usedRam}MB / {$totalRam}MB ({$ramUsagePercent}%)";

// Server Time
$serverTime = trim(shell_exec("date '+%H:%M'"));

// Get latest 4 log entries
exec(HESTIA_CMD . "v-list-user-log $user json", $output, $return_var);
$logs = json_decode(implode("", $output), true);
unset($output);

$recentLogs = [];
if (is_array($logs)) {
    $logs = array_reverse($logs); // newest first
    $recentLogs = array_slice($logs, 0, 4); // just 4 entries
} else {
    $recentLogs[] = [
        "LEVEL" => "error",
        "DATE" => date("Y-m-d"),
        "TIME" => date("H:i:s"),
        "MESSAGE" => "Unable to load logs",
        "CATEGORY" => "system",
    ];
}

render_page($user, $template, "list_dashboard", compact('services', 'recentLogs', 'ramUsageFormatted'));
