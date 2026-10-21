<?php
/**
 * QUICK START GUIDE - Run This First!
 * 
 * This file provides a visual setup wizard for LicenseAuth
 */

require_once __DIR__ . '/includes/config.php';

// Check PHP version
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4', '>=');

// Check for mysqli
$mysqli_ok = extension_loaded('mysqli');

// Check for OpenSSL
$openssl_ok = extension_loaded('openssl');

// Try to connect to MySQL
$db_ok = false;
$db_error = '';
try {
    $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($test_conn->connect_error) {
        $db_error = $test_conn->connect_error;
    } else {
        $db_ok = true;
        $test_conn->close();
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

$all_ok = $php_ok && $mysqli_ok && $openssl_ok && $db_ok;
?>
<!DOCTYPE html>
<html>
<head>
    <title>LicenseAuth - Setup Wizard</title>
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
        
        .wizard {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .wizard-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .wizard-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .wizard-content {
            padding: 40px 30px;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .check-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .check-icon.ok {
            background: #0c0;
        }
        
        .check-icon.error {
            background: #c00;
        }
        
        .check-info {
            flex: 1;
        }
        
        .check-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .check-detail {
            font-size: 13px;
            color: #666;
        }
        
        .check-error {
            color: #c00;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            flex: 1;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .status-message {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
        }
        
        .status-ok {
            background: #efe;
            color: #0c0;
            border: 1px solid #0c0;
        }
        
        .status-error {
            background: #fee;
            color: #c00;
            border: 1px solid #c00;
        }
        
        .setup-instructions {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .setup-instructions h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .setup-instructions ol {
            margin-left: 20px;
            color: #666;
            line-height: 1.8;
        }
        
        .setup-instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="wizard">
        <div class="wizard-header">
            <h1>🔐 LicenseAuth</h1>
            <p>License Management System - Setup Wizard</p>
        </div>
        
        <div class="wizard-content">
            <h2 style="margin-bottom: 20px;">System Requirements Check</h2>
            
            <!-- PHP Version Check -->
            <div class="check-item">
                <div class="check-icon <?= $php_ok ? 'ok' : 'error' ?>">
                    <?= $php_ok ? '✓' : '✕' ?>
                </div>
                <div class="check-info">
                    <div class="check-title">PHP Version</div>
                    <div class="check-detail">
                        <?= $php_ok ? "✓ $php_version (Required: 7.4+)" : "✕ $php_version (Required: 7.4+)" ?>
                    </div>
                </div>
            </div>
            
            <!-- MySQLi Extension Check -->
            <div class="check-item">
                <div class="check-icon <?= $mysqli_ok ? 'ok' : 'error' ?>">
                    <?= $mysqli_ok ? '✓' : '✕' ?>
                </div>
                <div class="check-info">
                    <div class="check-title">MySQLi Extension</div>
                    <div class="check-detail">
                        <?= $mysqli_ok ? '✓ Installed' : '✕ Not found. Please enable in php.ini' ?>
                    </div>
                </div>
            </div>
            
            <!-- OpenSSL Check -->
            <div class="check-item">
                <div class="check-icon <?= $openssl_ok ? 'ok' : 'error' ?>">
                    <?= $openssl_ok ? '✓' : '✕' ?>
                </div>
                <div class="check-info">
                    <div class="check-title">OpenSSL Extension</div>
                    <div class="check-detail">
                        <?= $openssl_ok ? '✓ Installed' : '✕ Not found (for security features)' ?>
                    </div>
                </div>
            </div>
            
            <!-- Database Connection Check -->
            <div class="check-item">
                <div class="check-icon <?= $db_ok ? 'ok' : 'error' ?>">
                    <?= $db_ok ? '✓' : '✕' ?>
                </div>
                <div class="check-info">
                    <div class="check-title">MySQL Connection</div>
                    <div class="check-detail <?= !$db_ok ? 'check-error' : '' ?>">
                        <?= $db_ok ? '✓ Connected to localhost' : "✕ Connection failed: $db_error" ?>
                    </div>
                </div>
            </div>
            
            <?php if ($all_ok): ?>
                <div class="status-message status-ok">
                    ✓ All requirements met! Ready to setup.
                </div>
                
                <div class="setup-instructions">
                    <h3>Next Steps:</h3>
                    <ol>
                        <li>Click "Setup Database" to create tables</li>
                        <li>Navigate to <strong>register.php</strong> to create your first account</li>
                        <li>Login and start managing licenses!</li>
                    </ol>
                </div>
                
                <div class="actions">
                    <a href="db_setup.php" class="btn btn-primary">Setup Database</a>
                    <a href="register.php" class="btn btn-secondary">Skip & Register</a>
                </div>
            <?php else: ?>
                <div class="status-message status-error">
                    ✕ Some requirements are missing. Please fix them before continuing.
                </div>
                
                <div class="setup-instructions">
                    <h3>How to Fix:</h3>
                    <ol>
                        <?php if (!$php_ok): ?>
                            <li><strong>PHP Version:</strong> Upgrade to PHP 7.4 or higher</li>
                        <?php endif; ?>
                        <?php if (!$mysqli_ok): ?>
                            <li><strong>MySQLi:</strong> Enable in php.ini: uncomment <code>extension=mysqli</code></li>
                        <?php endif; ?>
                        <?php if (!$openssl_ok): ?>
                            <li><strong>OpenSSL:</strong> Enable in php.ini: uncomment <code>extension=openssl</code></li>
                        <?php endif; ?>
                        <?php if (!$db_ok): ?>
                            <li><strong>MySQL:</strong> Start MySQL service and check connection settings</li>
                        <?php endif; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
