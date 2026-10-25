<?php
/**
 * License Check Page - Allows users to verify their license status
 */
include 'includes/config.php';

$error = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $license_key = $_POST['license_key'] ?? '';
    $app_slug = $_POST['app_slug'] ?? '';
    
    if (empty($license_key) || empty($app_slug)) {
        $error = 'Please enter both license key and application name';
    } else {
        // Find app
        $app = $conn->query("SELECT id FROM applications WHERE app_slug = '".sanitize($app_slug)."'")->fetch_assoc();
        
        if (!$app) {
            $error = 'Application not found';
        } else {
            // Find license
            $license = $conn->query("SELECT * FROM licenses WHERE license_key = '$license_key' AND app_id = {$app['id']}")->fetch_assoc();
            
            if (!$license) {
                $error = 'License not found';
            } else {
                $result = [
                    'found' => true,
                    'customer' => $license['customer_name'],
                    'status' => $license['status'],
                    'expires' => $license['expiry_date'],
                    'activations' => $license['current_activations'] . '/' . $license['max_activations'],
                    'valid' => true
                ];
                
                // Check if valid
                if ($license['status'] !== 'active') {
                    $result['valid'] = false;
                    $result['error'] = 'License is ' . $license['status'];
                }
                
                if ($license['expiry_date'] && strtotime($license['expiry_date']) < time()) {
                    $result['valid'] = false;
                    $result['error'] = 'License has expired';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>License Verification - LicenseCore</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
        }
        
        .success {
            background: #efe;
            color: #0c0;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0c0;
        }
        
        .warning {
            background: #ffe;
            color: #880;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #880;
        }
        
        .button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .result {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-label {
            font-weight: 600;
            color: #666;
        }
        
        .result-value {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-valid {
            background: #efe;
            color: #0c0;
        }
        
        .status-invalid {
            background: #fee;
            color: #c00;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔐</div>
            <h1>License Verification</h1>
            <p class="subtitle">Check your license status</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">✗ <?= sanitize($error) ?></div>
        <?php endif; ?>
        
        <?php if ($result && !$result['valid']): ?>
            <div class="warning">⚠ <?= $result['error'] ?></div>
        <?php endif; ?>
        
        <?php if ($result && $result['valid']): ?>
            <div class="success">✓ License is valid!</div>
            <div class="result">
                <div class="result-item">
                    <span class="result-label">Customer:</span>
                    <span class="result-value"><?= sanitize($result['customer']) ?></span>
                </div>
                <div class="result-item">
                    <span class="result-label">Status:</span>
                    <span class="result-value">
                        <span class="status-badge status-valid"><?= ucfirst($result['status']) ?></span>
                    </span>
                </div>
                <div class="result-item">
                    <span class="result-label">Activations:</span>
                    <span class="result-value"><?= $result['activations'] ?></span>
                </div>
                <div class="result-item">
                    <span class="result-label">Expires:</span>
                    <span class="result-value"><?= $result['expires'] ? date('M d, Y', strtotime($result['expires'])) : 'Never' ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="license_key">License Key</label>
                <input 
                    type="text" 
                    id="license_key" 
                    name="license_key" 
                    placeholder="XXXX-XXXX-XXXX-XXXX"
                    value="<?= sanitize($_POST['license_key'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="app_slug">Application Name</label>
                <input 
                    type="text" 
                    id="app_slug" 
                    name="app_slug" 
                    placeholder="my-app"
                    value="<?= sanitize($_POST['app_slug'] ?? '') ?>"
                    required
                >
            </div>
            
            <button type="submit" class="button">Check License</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>