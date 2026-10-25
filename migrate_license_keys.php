<?php
// Run once via CLI or with the upload-specific browser setup code.
if (PHP_SAPI !== 'cli') {
    header('Cache-Control: no-store');
    header('X-Frame-Options: DENY');
    header('Content-Type: text/html; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo '<!doctype html><html><head><title>License migration</title></head><body>';
        echo '<h1>Update license activation and devices</h1>';
        echo '<form method="post"><label>Setup code <input type="password" name="setup_code" required autocomplete="off"></label> ';
        echo '<button type="submit">Run migration</button></form></body></html>';
        exit;
    }
    $code = $_POST['setup_code'] ?? '';
    if (!is_string($code) || !hash_equals('9d09d3900a863c2f1dbe0cf096415f34', $code)) {
        http_response_code(403);
        exit('Invalid setup code.');
    }
}
require __DIR__ . '/includes/config.php';
$columns = array_column($conn->query('SHOW COLUMNS FROM `keys`')->fetch_all(MYSQLI_ASSOC), 'Field');
if (!in_array('duration_seconds', $columns, true)) {
    $conn->query('ALTER TABLE `keys` ADD duration_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0');
    // Preserve the purchased duration for unused legacy keys, even if their old deadline passed.
    $conn->query('UPDATE `keys` SET duration_seconds = GREATEST(1, expiry - UNIX_TIMESTAMP(created_at)) WHERE expiry > 0');
    $conn->query('UPDATE `keys` SET expiry = 0 WHERE used_at IS NULL');
}
if (!in_array('max_devices', $columns, true)) {
    $conn->query('ALTER TABLE `keys` ADD max_devices INT UNSIGNED NOT NULL DEFAULT 1');
}
$conn->query('CREATE TABLE IF NOT EXISTS key_devices (
    key_id INT NOT NULL,
    hwid_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (key_id, hwid_hash),
    FOREIGN KEY (key_id) REFERENCES `keys`(id) ON DELETE CASCADE
) ENGINE=InnoDB');
echo "License duration and device migration complete.\n";
if (PHP_SAPI !== 'cli') {
    echo '<p>You can now delete migrate_license_keys.php from your server using File Manager.</p>';
}
