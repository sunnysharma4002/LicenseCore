<?php
/**
 * Comprehensive API Debug Test
 * Helps diagnose connection and signature issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration - UPDATE THESE VALUES
$api_url = '<?= SITE_URL ?>/api/1.2/';
$ownerid = 'J33AZRRD5M';  // From your database
$app_name = 'DemoApp';  // From your database
$app_secret = 'af40cef071c0030880bbac99969cfa5b80b93d6d0b7230a3df4456ef678a00b5';  // From your database

echo "=== KeyAuth API Connectivity Test ===\n\n";

// Test 1: Basic connectivity
echo "Test 1: Checking API endpoint accessibility...\n";
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ CURL Error: $curl_error\n";
    echo "Please check:\n";
    echo "  1. XAMPP Apache is running\n";
    echo "  2. URL is correct: $api_url\n";
    echo "  3. File exists at c:/xampp/htdocs/licenceauth/api/1.2/index.php\n\n";
    exit(1);
}

echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“ API endpoint accessible (HTTP $http_code)\n\n";

// Test 2: Init request with full debugging
echo "Test 2: Testing INIT endpoint...\n";
$init_data = [
    'type' => 'init',
    'ver' => '1.0',
    'name' => $app_name,
    'ownerid' => $ownerid,
    'secret' => $app_secret,
    'enckey' => 'testkey123'
];

echo "Request data:\n";
print_r($init_data);
echo "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($init_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

if ($curl_errno) {
    echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ CURL Error ($curl_errno): $curl_error\n";
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    echo "Verbose output:\n$verbose_log\n";
    exit(1);
}

$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);

curl_close($ch);
fclose($verbose);

echo "HTTP Status: $http_code\n";
echo "\nResponse Headers:\n";
echo str_repeat('-', 50) . "\n";
echo $header;
echo str_repeat('-', 50) . "\n";

echo "\nResponse Body:\n";
echo str_repeat('-', 50) . "\n";
echo $body;
echo str_repeat('-', 50) . "\n\n";

// Parse response
$json = json_decode($body, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ Invalid JSON response: " . json_last_error_msg() . "\n";
    echo "Raw body: $body\n";
    exit(1);
}

// Extract signature
$signature = null;
if (preg_match('/signature:\s*([a-f0-9]+)/i', $header, $matches)) {
    $signature = $matches[1];
    echo "Signature received: $signature\n\n";
}

if (!isset($json['success'])) {
    echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ Response missing 'success' field\n";
    print_r($json);
    exit(1);
}

if (!$json['success']) {
    echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ API returned error: " . ($json['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“ INIT successful!\n";
echo "Session ID: " . ($json['data']['sessionid'] ?? 'N/A') . "\n";
echo "Nonce: " . ($json['data']['nonce'] ?? 'N/A') . "\n";

// Test 3: Verify signature
if ($signature) {
    echo "\nTest 3: Verifying signature...\n";
    $expected_sig = hash_hmac('sha256', $body, $app_secret);
    
    if ($signature === $expected_sig) {
        echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“ Signature valid!\n";
    } else {
        echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ Signature mismatch!\n";
        echo "Received:  $signature\n";
        echo "Expected:  $expected_sig\n";
        echo "Note: This is expected - init signs with app_secret, subsequent requests use combined enckey\n";
    }
} else {
    echo "ÃƒÂ¢Ã…Â¡Ã‚Â  No signature in response headers\n";
}

// Test 4: Check session
if (isset($json['data']['sessionid'])) {
    echo "\nTest 4: Testing CHECK endpoint...\n";
    $sessionid = $json['data']['sessionid'];
    
    $check_data = [
        'type' => 'check',
        'sessionid' => $sessionid,
        'name' => $app_name,
        'ownerid' => $ownerid,
        'secret' => $app_secret
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($check_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $header_size);
    curl_close($ch);
    
    $check_json = json_decode($body, true);
    
    if ($check_json && $check_json['success']) {
        echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“ Session check successful!\n";
    } else {
        echo "ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ Session check failed: " . ($check_json['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n=== All Tests Complete ===\n";
echo "\nIf you're seeing 'Connection failure' in your C# client:\n";
echo "1. Check the C# client is pointing to: $api_url\n";
echo "2. Verify ownerid, name, and secret match your database\n";
echo "3. Check XAMPP Apache error logs\n";
echo "4. Check API error log: c:/xampp/htdocs/licenceauth/logs/api_errors.log\n";
