<?php
include '../includes/config.php';
require_login();

$user = get_current_user_data();
$license_id = $_GET['id'] ?? 0;

// Get license details
$license = $conn->query("SELECT l.*, a.app_name, a.app_slug FROM licenses l
    JOIN applications a ON l.app_id = a.id
    WHERE l.id = $license_id AND a.user_id = {$user['id']}")->fetch_assoc();

if (!$license) {
    header("Location: dashboard.php");
    exit();
}

// Get activations
$activations = [];
$result = $conn->query("SELECT * FROM activations WHERE license_id = $license_id ORDER BY activated_at DESC");
while ($act = $result->fetch_assoc()) {
    $activations[] = $act;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>License Details - LicenseAuth</title>
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
            background: #f5f5f5;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .back-btn {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .section h2 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .license-key-box {
            background: #f9f9f9;
            border: 2px dashed #667eea;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            font-family: monospace;
            text-align: center;
            font-size: 16px;
            word-break: break-all;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #eee;
            font-weight: 600;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #efe;
            color: #0c0;
        }
        
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🔐 LicenseAuth</div>
            <a href="app.php?id=<?= $license['app_id'] ?>" class="back-btn">← Back</a>
        </div>
    </div>
    
    <div class="container">
        <div class="section">
            <h2>License Details</h2>
            
            <div class="license-key-box">
                <?= $license['license_key'] ?>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value"><?= sanitize($license['customer_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= $license['customer_email'] ? sanitize($license['customer_email']) : 'N/A' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge"><?= ucfirst($license['status']) ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Application</div>
                    <div class="info-value"><?= sanitize($license['app_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Activations</div>
                    <div class="info-value"><?= $license['current_activations'] ?>/<?= $license['max_activations'] ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Expires</div>
                    <div class="info-value"><?= $license['expiry_date'] ? date('M d, Y', strtotime($license['expiry_date'])) : 'Never' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Created</div>
                    <div class="info-value"><?= date('M d, Y', strtotime($license['created_at'])) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Activations -->
        <div class="section">
            <h2>Device Activations (<?= count($activations) ?>)</h2>
            
            <?php if (count($activations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Device Name</th>
                            <th>HWID</th>
                            <th>IP Address</th>
                            <th>Activated</th>
                            <th>Last Verified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activations as $act): ?>
                        <tr>
                            <td><?= sanitize($act['device_name']) ?: 'Unknown' ?></td>
                            <td><code><?= substr($act['hwid'], 0, 16) ?>...</code></td>
                            <td><?= $act['ip_address'] ?></td>
                            <td><?= date('M d, Y H:i', strtotime($act['activated_at'])) ?></td>
                            <td><?= $act['last_verified'] ? date('M d, Y H:i', strtotime($act['last_verified'])) : 'Never' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    <p>No device activations yet</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="delete_license.php?id=<?= $license['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this license?')">Delete License</a>
        </div>
    </div>
</body>
</html>
