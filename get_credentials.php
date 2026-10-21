<?php
/**
 * Get Your App Credentials
 * This script retrieves your actual app credentials from the database
 */

require_once 'includes/enhanced.php';

echo "=== Your Application Credentials ===\n\n";

// Get all apps
$stmt = $conn->prepare("SELECT a.id, a.ownerid, a.name, a.secret, a.version, acc.username as owner 
                        FROM apps a 
                        LEFT JOIN accounts acc ON a.ownerid = acc.ownerid 
                        LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No applications found in database!\n";
    echo "\nYou need to create an application first.\n";
    echo "Go to: /licenceauth/app/ and create an app\n\n";
    exit;
}

echo "Found " . $result->num_rows . " application(s):\n";
echo str_repeat('=', 80) . "\n";

while ($app = $result->fetch_assoc()) {
    echo "\nApp ID: {$app['id']}\n";
    echo "Owner: {$app['owner']} (ownerid: {$app['ownerid']})\n";
    echo "App Name: {$app['name']}\n";
    echo "Secret: {$app['secret']}\n";
    echo "Version: {$app['version']}\n";
    echo str_repeat('-', 80) . "\n";
    
    // Save to test file
    $test_config = "
// Copy these values to debug_api.php:
\$ownerid = '{$app['ownerid']}';
\$app_name = '{$app['name']}';
\$app_secret = '{$app['secret']}';
";
    
    echo "\nCopy and paste this into debug_api.php:\n";
    echo $test_config;
}

echo "\n\nAfter updating debug_api.php with real credentials, run it again:\n";
echo "php debug_api.php\n";
