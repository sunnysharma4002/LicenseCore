<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Calcutta');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/enhanced.php';

$sellerkey = sanitize($_GET['sellerkey'] ?? '');
$type = sanitize($_GET['type'] ?? '');
$expiry = intval($_GET['expiry'] ?? 1);
$level = intval($_GET['level'] ?? 1);
$amount = min(max(intval($_GET['amount'] ?? 1), 1), 100);
$mask = sanitize($_GET['mask'] ?? '');
$character = sanitize($_GET['character'] ?? '');
$note = sanitize($_GET['note'] ?? '');
$format = sanitize($_GET['format'] ?? 'json');

if (empty($sellerkey)) {
    echo json_encode(['success' => false, 'message' => 'Missing seller key']);
    exit;
}

// Look up account by seller key (md5 of ownerid)
$stmt = $conn->prepare("SELECT * FROM accounts WHERE MD5(ownerid) = ?");
$stmt->bind_param("s", $sellerkey);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid seller key']);
    exit;
}

$account = $result->fetch_assoc();
$ownerid = $account['ownerid'];

// Check if account is expired
if ($account['expires'] > 0 && $account['expires'] < time()) {
    echo json_encode(['success' => false, 'message' => 'Seller account expired']);
    exit;
}

if ($type === 'add') {
    // Determine format from mask
    $key_format = 'uuid';
    if (strpos($mask, '******-******-******-******-******-******') !== false) {
        $key_format = 'uuid';
    } elseif (strpos($mask, 'XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX') !== false) {
        $key_format = 'alphanum';
    }

    // Calculate expiry timestamp
    $expiry_ts = time() + ($expiry * 86400);

    $generated_keys = [];
    for ($i = 0; $i < $amount; $i++) {
        $license_key = generate_license_key($key_format);
        $status = 'active';

        // For seller, we need an app - use the first app
        $stmt_app = $conn->prepare("SELECT secret FROM apps WHERE ownerid = ? LIMIT 1");
        $stmt_app->bind_param("s", $ownerid);
        $stmt_app->execute();
        $app_result = $stmt_app->get_result();
        if ($app_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'No applications found']);
            exit;
        }
        $app_row = $app_result->fetch_assoc();
        $app_secret = $app_row['secret'];

        $created_by = 'SellerAPI';
        $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, expiry, status, format, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $app_secret, $license_key, $expiry_ts, $status, $key_format, $created_by);
        if ($stmt->execute()) {
            $generated_keys[] = $license_key;
        }
    }

    $response = [
        'success' => true,
        'message' => 'Keys generated successfully'
    ];
    if ($format === 'json') {
        $response['keys'] = $generated_keys;
    } else {
        $response['key'] = implode("\n", $generated_keys);
    }
    echo json_encode($response);
    log_action($app_secret ?? '', 'seller_keys_generated', $account['username'], "Seller generated $amount keys via API");
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
}