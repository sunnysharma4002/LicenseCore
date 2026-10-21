<?php
/**
 * LicenseAuth API - License Validation & Activation
 * Public API endpoint for license verification
 */

header('Content-Type: application/json');

include 'includes/config.php';

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

switch($action) {
    case 'validate':
        // Validate license key
        $license_key = $_GET['key'] ?? '';
        $app_slug = $_GET['app'] ?? '';
        
        if (empty($license_key) || empty($app_slug)) {
            $response['message'] = 'License key and app slug required';
            break;
        }
        
        // Find license
        $app = $conn->query("SELECT id FROM applications WHERE app_slug = '".sanitize($app_slug)."'")->fetch_assoc();
        
        if (!$app) {
            $response['message'] = 'Application not found';
            break;
        }
        
        $license = $conn->query("SELECT * FROM licenses WHERE license_key = '$license_key' AND app_id = {$app['id']}")->fetch_assoc();
        
        if (!$license) {
            $response['message'] = 'License not found';
            break;
        }
        
        // Check if license is valid
        if ($license['status'] !== 'active') {
            $response['message'] = 'License is ' . $license['status'];
            break;
        }
        
        // Check expiry
        if ($license['expiry_date'] && strtotime($license['expiry_date']) < time()) {
            $response['message'] = 'License has expired';
            $response['expired'] = true;
            break;
        }
        
        // Check activation limit
        if ($license['current_activations'] >= $license['max_activations']) {
            $response['message'] = 'Maximum activations reached';
            break;
        }
        
        $response['success'] = true;
        $response['message'] = 'License is valid';
        $response['license_id'] = $license['id'];
        $response['customer'] = $license['customer_name'];
        $response['activations_remaining'] = $license['max_activations'] - $license['current_activations'];
        
        break;
        
    case 'activate':
        // Activate license on a device
        $license_id = $_POST['license_id'] ?? 0;
        $hwid = $_POST['hwid'] ?? '';
        $device_name = $_POST['device_name'] ?? '';
        
        if ($license_id <= 0 || empty($hwid)) {
            $response['message'] = 'License ID and HWID required';
            break;
        }
        
        $license = $conn->query("SELECT * FROM licenses WHERE id = $license_id")->fetch_assoc();
        
        if (!$license) {
            $response['message'] = 'License not found';
            break;
        }
        
        // Check if already activated with this HWID
        $exists = $conn->query("SELECT id FROM activations WHERE license_id = $license_id AND hwid = '".sanitize($hwid)."'")->fetch_assoc();
        
        if ($exists) {
            $response['success'] = true;
            $response['message'] = 'Already activated on this device';
            break;
        }
        
        // Check activation limit
        if ($license['current_activations'] >= $license['max_activations']) {
            $response['message'] = 'Maximum activations reached';
            break;
        }
        
        // Create activation
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO activations (license_id, hwid, ip_address, device_name) VALUES (
            $license_id,
            '".sanitize($hwid)."',
            '$ip',
            '".sanitize($device_name)."'
        )";
        
        if ($conn->query($sql) === TRUE) {
            $conn->query("UPDATE licenses SET current_activations = current_activations + 1 WHERE id = $license_id");
            $response['success'] = true;
            $response['message'] = 'License activated successfully';
        } else {
            $response['message'] = 'Error activating license';
        }
        
        break;
        
    case 'verify':
        // Verify activation
        $license_id = $_GET['license_id'] ?? 0;
        $hwid = $_GET['hwid'] ?? '';
        
        if ($license_id <= 0 || empty($hwid)) {
            $response['message'] = 'License ID and HWID required';
            break;
        }
        
        $activation = $conn->query("SELECT * FROM activations WHERE license_id = $license_id AND hwid = '".sanitize($hwid)."' AND status = 'active'")->fetch_assoc();
        
        if (!$activation) {
            $response['message'] = 'Activation not found';
            break;
        }
        
        $license = $conn->query("SELECT * FROM licenses WHERE id = $license_id")->fetch_assoc();
        
        // Check if license expired
        if ($license['expiry_date'] && strtotime($license['expiry_date']) < time()) {
            $response['message'] = 'License has expired';
            $response['expired'] = true;
            break;
        }
        
        // Update last verified
        $conn->query("UPDATE activations SET last_verified = NOW() WHERE id = {$activation['id']}");
        
        $response['success'] = true;
        $response['message'] = 'Activation is valid';
        $response['customer'] = $license['customer_name'];
        $response['expires'] = $license['expiry_date'];
        
        break;
        
    default:
        $response['message'] = 'Invalid action';
}

echo json_encode($response);
$conn->close();
?>
