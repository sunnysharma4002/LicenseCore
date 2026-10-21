<?php
include '../includes/config.php';
require_login();

$user = get_current_user_data();
$app_id = $_GET['id'] ?? 0;

// Get application details
$app = $conn->query("SELECT * FROM applications WHERE id = $app_id AND user_id = {$user['id']}")->fetch_assoc();

if (!$app) {
    header("Location: dashboard.php");
    exit();
}

// Handle license creation
$license_error = '';
$license_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_license') {
        $customer_name = $_POST['customer_name'] ?? '';
        $customer_email = $_POST['customer_email'] ?? '';
        $max_activations = $_POST['max_activations'] ?? 1;
        $expiry_days = $_POST['expiry_days'] ?? 0;
        
        if (empty($customer_name)) {
            $license_error = 'Customer name is required';
        } else {
            $license_key = generate_license_key();
            $expiry_date = $expiry_days > 0 ? date('Y-m-d H:i:s', strtotime("+$expiry_days days")) : null;
            
            $sql = "INSERT INTO licenses (app_id, license_key, customer_name, customer_email, max_activations, expiry_date) VALUES (
                $app_id,
                '$license_key',
                '".sanitize($customer_name)."',
                '".sanitize($customer_email)."',
                $max_activations,
                ".($expiry_date ? "'$expiry_date'" : "NULL")."
            )";
            
            if ($conn->query($sql) === TRUE) {
                $license_success = "License created! Key: <strong>$license_key</strong>";
            } else {
                $license_error = 'Error creating license: ' . $conn->error;
            }
        }
    }
}

// Get licenses for this app
$licenses_result = $conn->query("SELECT * FROM licenses WHERE app_id = $app_id ORDER BY created_at DESC");
$licenses = [];
while ($lic = $licenses_result->fetch_assoc()) {
    $licenses[] = $lic;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage App - LicenseAuth</title>
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            max-width: 1200px;
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
            font-size: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .app-info h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .app-info p {
            color: #999;
            font-size: 14px;
            font-family: monospace;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
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
            word-break: break-all;
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
            font-size: 14px;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #efe;
            color: #0c0;
        }
        
        .status-expired {
            background: #fee;
            color: #c00;
        }
        
        .action-btn {
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn:hover {
            background: #5568d3;
        }
        
        .action-btn.delete {
            background: #e74c3c;
        }
        
        .action-btn.delete:hover {
            background: #c0392b;
        }
        
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🔐 LicenseAuth</div>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <div class="app-header">
            <div class="app-info">
                <h1><?= sanitize($app['app_name']) ?></h1>
                <p>@<?= sanitize($app['app_slug']) ?></p>
            </div>
        </div>
        
        <!-- Create License Section -->
        <div class="section">
            <h2>Create New License</h2>
            
            <?php if ($license_error): ?>
                <div class="error">✗ <?= sanitize($license_error) ?></div>
            <?php endif; ?>
            
            <?php if ($license_success): ?>
                <div class="success">✓ <?= $license_success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_license">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer Name*</label>
                        <input type="text" name="customer_name" placeholder="John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Customer Email</label>
                        <input type="email" name="customer_email" placeholder="john@example.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Activations</label>
                        <input type="number" name="max_activations" value="1" min="1" max="100">
                    </div>
                    
                    <div class="form-group">
                        <label>Expiry Days (0 = no expiry)</label>
                        <input type="number" name="expiry_days" value="0" min="0">
                    </div>
                </div>
                
                <button type="submit" class="btn">Generate License</button>
            </form>
        </div>
        
        <!-- Licenses List -->
        <div class="section">
            <h2>Licenses (<?= count($licenses) ?>)</h2>
            
            <?php if (count($licenses) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Activations</th>
                            <th>Expires</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $license): ?>
                        <tr>
                            <td><code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px;"><?= substr($license['license_key'], 0, 16) ?>...</code></td>
                            <td><?= sanitize($license['customer_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $license['status'] ?>">
                                    <?= ucfirst($license['status']) ?>
                                </span>
                            </td>
                            <td><?= $license['current_activations'] ?>/<?= $license['max_activations'] ?></td>
                            <td><?= $license['expiry_date'] ? date('M d, Y', strtotime($license['expiry_date'])) : 'Never' ?></td>
                            <td><?= date('M d, Y', strtotime($license['created_at'])) ?></td>
                            <td>
                                <a href="license.php?id=<?= $license['id'] ?>" class="action-btn">View</a>
                                <a href="delete_license.php?id=<?= $license['id'] ?>" class="action-btn delete" onclick="return confirm('Delete this license?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    <p>No licenses created yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
