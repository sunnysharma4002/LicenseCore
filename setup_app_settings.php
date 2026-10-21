<?php
require_once __DIR__ . '/includes/config.php';

$added = 0;
$checks = [
    'settings' => "SHOW COLUMNS FROM apps LIKE 'settings'",
    'hash'     => "SHOW COLUMNS FROM apps LIKE 'hash'"
];
foreach ($checks as $col => $sql) {
    $r = $conn->query($sql);
    if ($r && $r->num_rows === 0) {
        if ($col === 'settings') {
            $conn->query("ALTER TABLE apps ADD `settings` TEXT DEFAULT NULL AFTER `description`");
        } else {
            $conn->query("ALTER TABLE apps ADD `hash` VARCHAR(255) DEFAULT NULL AFTER `settings`");
        }
        echo "✅ Added column: $col<br>";
        $added++;
    }
}
if ($added === 0) echo "✅ Columns already exist<br>";
echo "<a href='app/?page=app-settings'>Go to App Settings</a>";
$conn->close();
