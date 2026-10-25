<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

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

// Resolve the application directly from its seller key, then check its owner.
$stmt = $conn->prepare("SELECT accounts.*, apps.secret AS seller_app_secret FROM apps JOIN accounts ON accounts.ownerid = apps.ownerid WHERE COALESCE(apps.seller_key, LEFT(SHA2(CONCAT('LicenseCore:seller:', apps.secret), 256), 32)) = ?");
$stmt->bind_param("s", $sellerkey);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid seller key']);
    exit;
}

$account = $result->fetch_assoc();
$ownerid = $account['ownerid'];
$app_secret = $account['seller_app_secret'];

// Check if account is expired
if ($account['expires'] > 0 && $account['expires'] < time()) {
    echo json_encode(['success' => false, 'message' => 'Seller account expired']);
    exit;
}

if ($type === 'add') {
    // Preserve literal mask characters and randomize only '*' placeholders.
    if ($mask !== '' && (strlen($mask) > 70 || strpos($mask, '*') === false)) {
        echo json_encode(['success' => false, 'message' => 'Mask must contain * and be at most 70 characters']);
        exit;
    }
    $key_format = $mask === '' ? 'uuid' : 'mask';
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    // Store duration; the countdown begins on first activation.
    $expiry_ts = max(0, $expiry) * 86400;
    $max_devices = max(1, min(1000, intval($_GET['max_devices'] ?? 1)));

    $generated_keys = [];
    for ($i = 0; $i < $amount; $i++) {
        if ($mask === '') {
            $license_key = generate_license_key('uuid');
        } else {
            $license_key = $mask;
            for ($j = 0; $j < strlen($license_key); $j++) {
                if ($license_key[$j] === '*') {
                    $license_key[$j] = $characters[random_int(0, strlen($characters) - 1)];
                }
            }
        }
        $status = 'active';

        $created_by = 'SellerAPI';
        $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, duration_seconds, status, format, created_by, max_devices) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssi", $app_secret, $license_key, $expiry_ts, $status, $key_format, $created_by, $max_devices);
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
