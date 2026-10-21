<?php
// Display last 100 lines of error log
$logfile = __DIR__ . '/logs/api_errors.log';

if (!file_exists($logfile)) {
    die("Log file not found at: $logfile");
}

$lines = file($logfile);
$last_lines = array_slice($lines, -100);

header('Content-Type: text/plain');
echo "=== LAST 100 LOG ENTRIES ===\n\n";
echo implode('', $last_lines);
?>
