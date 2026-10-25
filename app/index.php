<?php
require_once '../includes/enhanced.php';
require_login();

$user = get_current_user_data();
$ownerid = $user['ownerid'];

// Get current page
$page = sanitize($_GET['page'] ?? 'home');

// Initialize selected app session logic
if (isset($_GET['app']) && !empty($_GET['app'])) {
    $_SESSION['selected_app'] = sanitize($_GET['app']);
}
$selected_app = $_SESSION['selected_app'] ?? '';


// Handle app creation
$create_error = '';
$create_success = '';
$app_error = '';
$app_success = '';

$license_error = '';
$license_success = '';
$generated_keys = [];
$seller_error = '';
$seller_success = '';
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'refresh_seller_key') {
        $token = $_POST['seller_csrf'] ?? '';
        $app_secret = sanitize($_POST['app'] ?? '');
        if (!is_string($token) || empty($_SESSION['seller_csrf']) || !hash_equals($_SESSION['seller_csrf'], $token)) {
            $seller_error = 'Please reload the page and try again.';
        } elseif ($conn->query("SHOW COLUMNS FROM apps LIKE 'seller_key'")->num_rows === 0) {
            $seller_error = 'Run the updated db_setup.php first, then refresh your seller key.';
        } else {
            $new_seller_key = bin2hex(random_bytes(16));
            $stmt = $conn->prepare('UPDATE apps SET seller_key = ? WHERE secret = ? AND ownerid = ?');
            $stmt->bind_param('sss', $new_seller_key, $app_secret, $ownerid);
            $stmt->execute();
            if ($stmt->affected_rows === 1) {
                $seller_success = 'Seller key refreshed. Copy the new seller link into your integrations.';
                $_SESSION['seller_csrf'] = bin2hex(random_bytes(32));
                log_action($app_secret, 'seller_key_refreshed', $user['username'], 'Seller key rotated');
            } else {
                $seller_error = 'Application not found.';
            }
        }
    }

    if ($action === 'set_key_devices') {
        $key_id = intval($_POST['key_id'] ?? 0);
        $max_devices = max(1, min(1000, intval($_POST['max_devices'] ?? 1)));
        $stmt = $conn->prepare("UPDATE `keys` k JOIN apps a ON a.secret = k.app SET k.max_devices = ? WHERE k.id = ? AND a.ownerid = ?");
        $stmt->bind_param('iis', $max_devices, $key_id, $ownerid);
        $stmt->execute();
        $license_success = 'Device limit saved. Devices beyond this limit will be rejected on their next key login.';
    }

    // Create application
    if ($action === 'create_app') {
        $app_name = sanitize($_POST['app_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $version = sanitize($_POST['version'] ?? '1.0');
        
        if (empty($app_name)) {
            $create_error = 'App name is required';
        } else {
            $secret = generate_app_secret();
            
            $stmt = $conn->prepare("INSERT INTO apps (secret, name, ownerid, version, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $secret, $app_name, $ownerid, $version, $description);
            
            if ($stmt->execute()) {
                $create_success = 'Application created successfully!';
                log_action($secret, 'app_created', $user['username'], 'New application created');
            } else {
                $create_error = 'Error creating application';
            }
        }
    }

    // Reset application secret
    if ($action === 'reset_secret') {
        $app_secret = sanitize($_POST['app'] ?? '');

        // Verify ownership
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        $app_row = $stmt->get_result()->fetch_assoc();

        if (!$app_row) {
            $app_error = 'Invalid application selected.';
        } else {
            $new_secret = generate_app_secret();

            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                // Update primary app secret
                $stmtUpdateApp = $conn->prepare("UPDATE apps SET secret = ?, modified_at = NOW() WHERE secret = ? AND ownerid = ?");
                if (!$stmtUpdateApp) {
                    throw new Exception('Prepare apps failed: ' . $conn->error);
                }
                $stmtUpdateApp->bind_param("sss", $new_secret, $app_secret, $ownerid);
                if (!$stmtUpdateApp->execute()) {
                    throw new Exception('Update apps failed: ' . $stmtUpdateApp->error);
                }
                if ($stmtUpdateApp->affected_rows === 0) {
                    throw new Exception('No app updated');
                }

                // Propagate new secret across dependent tables
                $tables = [
                    'subscriptions', 'users', '`keys`', 'sessions', 'uservars', 'vars',
                    'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages',
                    'tokens', 'integrations', 'subs'
                ];

                foreach ($tables as $table) {
                    $stmtUpdate = $conn->prepare("UPDATE {$table} SET app = ? WHERE app = ?");
                    if (!$stmtUpdate) {
                        throw new Exception("Prepare {$table} failed: " . $conn->error);
                    }
                    $stmtUpdate->bind_param("ss", $new_secret, $app_secret);
                    if (!$stmtUpdate->execute()) {
                        throw new Exception("Update {$table} failed: " . $stmtUpdate->error);
                    }
                }

                // Also update logapp mirror field when it matches the old secret
                $stmtLogapp = $conn->prepare("UPDATE logs SET logapp = ? WHERE logapp = ?");
                if (!$stmtLogapp) {
                    throw new Exception('Prepare logapp failed: ' . $conn->error);
                }
                $stmtLogapp->bind_param("ss", $new_secret, $app_secret);
                if (!$stmtLogapp->execute()) {
                    throw new Exception('Update logapp failed: ' . $stmtLogapp->error);
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $conn->commit();

                $app_success = 'App secret reset successfully. Update your clients with the new secret key.';
                $selected_app = $new_secret;
                log_action($new_secret, 'secret_reset', $user['username'], 'Application secret rotated');
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $app_error = 'Failed to reset the secret. ' . $e->getMessage();
                log_action($app_secret, 'secret_reset_failed', $user['username'], $app_error);
            }
        }
    }

    // Generate license keys
    if ($action === 'generate_key') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $duration = max(intval($_POST['duration'] ?? 30), 1);
        $duration_unit = sanitize($_POST['duration_unit'] ?? 'days');
        $amount = min(max(intval($_POST['amount'] ?? 1), 1), 100);
        $level = min(max(intval($_POST['level'] ?? 1), 1), 5);
        $note = sanitize($_POST['note'] ?? '');
        if ($note === '') $note = 'N/A';
        $key_format = sanitize($_POST['format'] ?? 'default');

        // Normalize format to match generator
        if ($key_format === 'default') {
            $key_format = 'uuid';
        }

        // Verify app ownership
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        $app_check = $stmt->get_result();

        if ($app_check->num_rows > 0) {
            // Determine expiry interval
            $unit = $duration_unit === 'hours' ? 'hours' : 'days';
            $expiry_ts = $duration * ($unit === 'hours' ? 3600 : 86400);
            $max_devices = max(1, min(1000, intval($_POST['max_devices'] ?? 1)));

            // Generate multiple keys
            for ($i = 0; $i < $amount; $i++) {
                $license_key = generate_license_key($key_format);
                $status = 'active';

                $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, duration_seconds, status, format, created_by, max_devices, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisssis", $app_secret, $license_key, $expiry_ts, $status, $key_format, $user['username'], $max_devices, $note);

                if ($stmt->execute()) {
                    $generated_keys[] = $license_key;
                } else {
                    $license_error = 'Error generating some keys';
                    break;
                }
            }

            if (empty($license_error)) {
                $license_success = count($generated_keys) . ' license key(s) generated successfully!';
                log_action($app_secret, 'keys_generated', $user['username'], count($generated_keys) . ' keys generated');
            }
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Delete license key
    if ($action === 'delete_key') {
        $key_id = intval($_POST['key_id'] ?? 0);
        $app_secret = sanitize($_POST['app'] ?? '');

        // Verify ownership
        $stmt = $conn->prepare("SELECT k.* FROM `keys` k JOIN apps a ON k.app = a.secret WHERE k.id = ? AND a.ownerid = ?");
        $stmt->bind_param("is", $key_id, $ownerid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE id = ?");
            $stmt->bind_param("i", $key_id);
            if ($stmt->execute()) {
                $license_success = 'License key deleted successfully';
                log_action($app_secret, 'key_deleted', $user['username'], 'License key deleted');
            }
        }
    }

    // Add time to all active keys
    if ($action === 'add_time_keys') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $duration = max(intval($_POST['duration'] ?? 30), 1);
        $duration_unit = sanitize($_POST['duration_unit'] ?? 'days');
        $seconds = $duration * ($duration_unit === 'hours' ? 3600 : 86400);
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE `keys` SET duration_seconds = duration_seconds + ? WHERE app = ? AND status = 'active' AND used_at IS NULL AND duration_seconds > 0");
            $stmt->bind_param("is", $seconds, $app_secret);
            if ($stmt->execute()) {
                $license_success = 'Time added to all active keys successfully!';
                log_action($app_secret, 'time_added_keys', $user['username'], "Added $duration $duration_unit to active keys");
            } else {
                $license_error = 'Error adding time to keys';
            }
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Import keys
    if ($action === 'import_keys') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $keys_list = trim($_POST['keys_list'] ?? '');
        $duration = max(intval($_POST['duration'] ?? 30), 1);
        $duration_unit = sanitize($_POST['duration_unit'] ?? 'days');
        $level = min(max(intval($_POST['level'] ?? 1), 1), 5);
        $expiry_ts = $duration * ($duration_unit === 'hours' ? 3600 : 86400);
        $imported = 0;
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $lines = explode("\n", $keys_list);
            $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, duration_seconds, status, format, created_by) VALUES (?, ?, ?, 'active', 'imported', ?)");
            $stmt->bind_param("ssis", $app_secret, $license_key, $expiry_ts, $user['username']);
            foreach ($lines as $line) {
                $license_key = trim($line);
                if (!empty($license_key)) {
                    $stmt->execute();
                    $imported++;
                }
            }
            $license_success = "$imported key(s) imported successfully!";
            log_action($app_secret, 'keys_imported', $user['username'], "$imported keys imported");
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Delete all keys
    if ($action === 'delete_allkeys') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE app = ?");
            $stmt->bind_param("s", $app_secret);
            $stmt->execute();
            $license_success = 'All keys deleted successfully!';
            log_action($app_secret, 'keys_deleted_all', $user['username'], 'All keys deleted');
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Delete all unused keys
    if ($action === 'delete_allunusedkeys') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE app = ? AND (used_by IS NULL OR used_by = '')");
            $stmt->bind_param("s", $app_secret);
            $stmt->execute();
            $license_success = 'All unused keys deleted successfully!';
            log_action($app_secret, 'keys_deleted_unused', $user['username'], 'All unused keys deleted');
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Delete all used keys
    if ($action === 'delete_allusedkeys') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE app = ? AND used_by IS NOT NULL AND used_by != ''");
            $stmt->bind_param("s", $app_secret);
            $stmt->execute();
            $license_success = 'All used keys deleted successfully!';
            log_action($app_secret, 'keys_deleted_used', $user['username'], 'All used keys deleted');
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Delete application
    if ($action === 'delete_app') {
        $app_secret = sanitize($_POST['app'] ?? '');
        error_log("Delete app attempt: app_secret=$app_secret, ownerid=$ownerid");

        // Verify ownership
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        $app_check = $stmt->get_result();
        error_log("App check result: " . $app_check->num_rows . " rows");

        if ($app_check->num_rows > 0) {
            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                // Delete from all dependent tables
                $tables = [
                    'subs', 'users', '`keys`', 'sessions', 'uservars', 'vars',
                    'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages',
                    'tokens', 'integrations'
                ];

                foreach ($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM {$table} WHERE app = ?");
                    $stmt->bind_param("s", $app_secret);
                    $stmt->execute();
                }

                // Finally delete the app
                $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ? AND ownerid = ?");
                $stmt->bind_param("ss", $app_secret, $ownerid);
                if ($stmt->execute()) {
                    $conn->commit();
                    $app_success = 'Application deleted successfully';
                    log_action($app_secret, 'app_deleted', $user['username'], 'Application deleted');
                    // Redirect to applications page
                    header("Location: ?page=applications");
                    exit();
                } else {
                    throw new Exception('Failed to delete application: ' . $stmt->error);
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $app_error = 'Failed to delete application: ' . $e->getMessage();
            }
        } else {
            $app_error = 'Application not found or access denied';
        }
    }
}

// Handle button-name-based actions (no "action" hidden field)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Select app by name
    if (isset($_POST['selectApp'])) {
        $app_name = sanitize($_POST['appname'] ?? '');
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE name = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_name, $ownerid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['selected_app'] = $row['secret'];
            $selected_app = $row['secret'];
        }
    }

    // Create app (button-name style)
    if (isset($_POST['create_app'])) {
        $app_name = sanitize($_POST['app_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $version = sanitize($_POST['version'] ?? '1.0');
        if (empty($app_name)) {
            $create_error = 'App name is required';
        } else {
            $secret = generate_app_secret();
            $stmt = $conn->prepare("INSERT INTO apps (secret, name, ownerid, version, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $secret, $app_name, $ownerid, $version, $description);
            if ($stmt->execute()) {
                $create_success = 'Application created successfully!';
                log_action($secret, 'app_created', $user['username'], 'New application created');
            } else {
                $create_error = 'Error creating application';
            }
        }
    }

    // Rename app
    if (isset($_POST['rename_app'])) {
        $new_name = sanitize($_POST['appname'] ?? '');
        if (!empty($new_name) && !empty($selected_app)) {
            $stmt = $conn->prepare("UPDATE apps SET name = ?, modified_at = NOW() WHERE secret = ? AND ownerid = ?");
            $stmt->bind_param("sss", $new_name, $selected_app, $ownerid);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $app_success = 'Application renamed successfully';
                log_action($selected_app, 'app_renamed', $user['username'], "App renamed to $new_name");
            } else {
                $app_error = 'Error renaming application';
            }
        } else {
            $app_error = 'No application selected or name is empty';
        }
    }

    // Pause app
    if (isset($_POST['pauseapp'])) {
        $app_name = sanitize($_POST['appname'] ?? '');
        if (!empty($app_name)) {
            $stmt = $conn->prepare("UPDATE apps SET paused = 1, modified_at = NOW() WHERE name = ? AND ownerid = ?");
            $stmt->bind_param("ss", $app_name, $ownerid);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $app_success = 'Application paused successfully';
                log_action($selected_app, 'app_paused', $user['username'], 'Application paused');
            } else {
                $app_error = 'Error pausing application';
            }
        }
    }

    // Unpause app
    if (isset($_POST['unpauseapp'])) {
        $app_name = sanitize($_POST['appname'] ?? '');
        if (!empty($app_name)) {
            $stmt = $conn->prepare("UPDATE apps SET paused = 0, modified_at = NOW() WHERE name = ? AND ownerid = ?");
            $stmt->bind_param("ss", $app_name, $ownerid);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $app_success = 'Application unpaused successfully';
                log_action($selected_app, 'app_unpaused', $user['username'], 'Application unpaused');
            } else {
                $app_error = 'Error unpausing application';
            }
        }
    }

    // Refresh app secret
    if (isset($_POST['refreshapp'])) {
        $app_name = sanitize($_POST['appname'] ?? '');
        if (!empty($app_name)) {
            $stmt = $conn->prepare("SELECT secret FROM apps WHERE name = ? AND ownerid = ?");
            $stmt->bind_param("ss", $app_name, $ownerid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $old_secret = $row['secret'];
                $new_secret = generate_app_secret();
                try {
                    $conn->begin_transaction();
                    $conn->query("SET FOREIGN_KEY_CHECKS=0");
                    $stmtUpdateApp = $conn->prepare("UPDATE apps SET secret = ?, modified_at = NOW() WHERE secret = ? AND ownerid = ?");
                    $stmtUpdateApp->bind_param("sss", $new_secret, $old_secret, $ownerid);
                    if (!$stmtUpdateApp->execute()) throw new Exception('Update apps failed');
                    $tables = ['subscriptions', 'users', '`keys`', 'sessions', 'uservars', 'vars', 'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages', 'tokens', 'integrations', 'subs'];
                    foreach ($tables as $table) {
                        $stmtUpdate = $conn->prepare("UPDATE {$table} SET app = ? WHERE app = ?");
                        $stmtUpdate->bind_param("ss", $new_secret, $old_secret);
                        if (!$stmtUpdate->execute()) throw new Exception("Update {$table} failed");
                    }
                    $stmtLogapp = $conn->prepare("UPDATE logs SET logapp = ? WHERE logapp = ?");
                    $stmtLogapp->bind_param("ss", $new_secret, $old_secret);
                    $stmtLogapp->execute();
                    $conn->query("SET FOREIGN_KEY_CHECKS=1");
                    $conn->commit();
                    $app_success = 'App secret refreshed successfully';
                    if ($selected_app === $old_secret) {
                        $_SESSION['selected_app'] = $new_secret;
                        $selected_app = $new_secret;
                    }
                    log_action($new_secret, 'secret_refreshed', $user['username'], 'Application secret refreshed');
                } catch (Exception $e) {
                    $conn->rollback();
                    $conn->query("SET FOREIGN_KEY_CHECKS=1");
                    $app_error = 'Failed to refresh secret: ' . $e->getMessage();
                }
            } else {
                $app_error = 'Application not found';
            }
        }
    }

    // Delete app
    if (isset($_POST['deleteapp'])) {
        $app_name = sanitize($_POST['appname'] ?? '');
        $confirm = sanitize($_POST['confirmappname'] ?? '');
        if ($confirm !== 'Confirm') {
            $app_error = 'Please type "Confirm" to delete the application';
        } elseif (!empty($app_name)) {
            $stmt = $conn->prepare("SELECT secret FROM apps WHERE name = ? AND ownerid = ?");
            $stmt->bind_param("ss", $app_name, $ownerid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $app_secret = $row['secret'];
                try {
                    $conn->begin_transaction();
                    $conn->query("SET FOREIGN_KEY_CHECKS=0");
                    $tables = ['subs', 'users', '`keys`', 'sessions', 'uservars', 'vars', 'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages', 'tokens', 'integrations'];
                    foreach ($tables as $table) {
                        $stmt = $conn->prepare("DELETE FROM {$table} WHERE app = ?");
                        $stmt->bind_param("s", $app_secret);
                        $stmt->execute();
                    }
                    $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ? AND ownerid = ?");
                    $stmt->bind_param("ss", $app_secret, $ownerid);
                    $stmt->execute();
                    $conn->query("SET FOREIGN_KEY_CHECKS=1");
                    $conn->commit();
                    $app_success = 'Application deleted successfully';
                    log_action($app_secret, 'app_deleted', $user['username'], 'Application deleted');
                    if ($selected_app === $app_secret) {
                        $_SESSION['selected_app'] = '';
                        $selected_app = '';
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $conn->query("SET FOREIGN_KEY_CHECKS=1");
                    $app_error = 'Failed to delete application: ' . $e->getMessage();
                }
            } else {
                $app_error = 'Application not found';
            }
        }
    }

    // Download all keys (button-name style, uses selected app)
    if (isset($_POST['dlkeys'])) {
        $app_secret = $selected_app;
        if (!empty($app_secret)) {
            $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
            $stmt->bind_param("ss", $app_secret, $ownerid);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("SELECT license_key FROM `keys` WHERE app = ?");
                $stmt->bind_param("s", $app_secret);
                $stmt->execute();
                $result = $stmt->get_result();
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="keys.txt"');
                while ($row = $result->fetch_assoc()) {
                    echo $row['license_key'] . PHP_EOL;
                }
                exit;
            }
        }
        $license_error = 'No application selected';
    }

    // Generate keys with mask (LicenseAuth-style)
    if (isset($_POST['genkeys'])) {
        $amount = max(intval($_POST['amount'] ?? 1), 1);
        $mask = sanitize($_POST['mask'] ?? '');
        $level = intval($_POST['level'] ?? 1);
        $note = sanitize($_POST['note'] ?? '');
        if ($note === '') $note = 'N/A';
        $expiry_unit = intval($_POST['expiry'] ?? 86400);
        $duration_val = max(intval($_POST['duration'] ?? 1), 1);
        $lowercase = isset($_POST['lowercaseLetters']);
        $capital = isset($_POST['capitalLetters']);
        $chars = '0123456789';
        if ($lowercase) $chars .= 'abcdefghijklmnopqrstuvwxyz';
        if ($capital) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $expiry_ts = $expiry_unit === 315569260 ? 0 : ($duration_val * max(1, $expiry_unit));
        $max_devices = max(1, min(1000, intval($_POST['max_devices'] ?? 1)));

        if ($amount > 10 && ($user['role'] ?? '') === 'tester') {
            $license_error = 'Tester accounts are limited to 10 keys at once';
        } elseif (empty($mask)) {
            $license_error = 'Key mask is required';
        } elseif (empty($selected_app)) {
            $license_error = 'No application selected';
        } else {
            $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
            $stmt->bind_param("ss", $selected_app, $ownerid);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $can_generate = true;
                if (($user['role'] ?? '') === 'tester') {
                    $stmt_cnt = $conn->prepare("SELECT COUNT(*) as cnt FROM `keys` WHERE app = ?");
                    $stmt_cnt->bind_param("s", $selected_app);
                    $stmt_cnt->execute();
                    $existing = $stmt_cnt->get_result()->fetch_assoc()['cnt'];
                    if ($existing + $amount > 10) {
                        $license_error = 'Tester accounts are limited to 10 keys total';
                        $can_generate = false;
                    }
                }
                if ($can_generate) {
                    for ($i = 0; $i < $amount; $i++) {
                        $key = $mask;
                        for ($j = 0; $j < strlen($key); $j++) {
                            if ($key[$j] === '*') {
                                $key[$j] = $chars[random_int(0, strlen($chars) - 1)];
                            }
                        }
                        $fmt = 'mask';
                        $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, duration_seconds, status, format, created_by, note, level, max_devices) VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssisssii", $selected_app, $key, $expiry_ts, $fmt, $user['username'], $note, $level, $max_devices);
                        $stmt->execute();
                        $generated_keys[] = $key;
                    }
                    $license_success = count($generated_keys) . ' key(s) generated successfully!';
                    log_action($selected_app, 'keys_generated', $user['username'], count($generated_keys) . ' keys generated via mask');
                    // Remember last used mask & expiry unit for next time
                    $_SESSION['last_key_mask'] = $mask;
                    $_SESSION['last_key_expiry'] = $expiry_unit;
                }
            } else {
                $license_error = 'Invalid application';
            }
        }
    }

    // Import keys (LicenseAuth-style)
    if (isset($_POST['importkeys'])) {
        $keys_input = trim($_POST['keys'] ?? '');
        $authgg_json = trim($_POST['authgg'] ?? '');
        $imported = 0;

        if (empty($selected_app)) {
            $license_error = 'No application selected';
        } else {
            $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
            $stmt->bind_param("ss", $selected_app, $ownerid);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                if (!empty($authgg_json)) {
                    $data = json_decode($authgg_json, true);
                    if ($data && isset($data['data'])) {
                        foreach ($data['data'] as $item) {
                            $lk = $item['license'] ?? $item['key'] ?? '';
                            $lv = intval($item['level'] ?? 1);
                            $days = intval($item['days'] ?? 30);
                            $exp = max(0, $days) * 86400;
                            if (!empty($lk)) {
                                $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, duration_seconds, status, format, created_by) VALUES (?, ?, ?, 'active', 'imported', ?)");
                                $stmt->bind_param("ssis", $selected_app, $lk, $exp, $user['username']);
                                $stmt->execute();
                                $imported++;
                            }
                        }
                    }
                } elseif (!empty($keys_input)) {
                    $parts = explode('|', $keys_input);
                    foreach ($parts as $part) {
                        $fields = explode(',', $part);
                        if (count($fields) >= 1) {
                            $lk = trim($fields[0]);
                            $lv = isset($fields[1]) ? intval(trim($fields[1])) : 1;
                            $days = isset($fields[2]) ? intval(trim($fields[2])) : 30;
                            $exp = max(0, $days) * 86400;
                            if (!empty($lk)) {
                                $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, duration_seconds, status, format, created_by) VALUES (?, ?, ?, 'active', 'imported', ?)");
                                $stmt->bind_param("ssis", $selected_app, $lk, $exp, $user['username']);
                                $stmt->execute();
                                $imported++;
                            }
                        }
                    }
                }
                $license_success = "$imported key(s) imported successfully!";
                log_action($selected_app, 'keys_imported', $user['username'], "$imported keys imported");
            } else {
                $license_error = 'Invalid application';
            }
        }
    }

    // Add time to active keys (LicenseAuth-style)
    if (isset($_POST['addtime'])) {
        $expiry_unit = intval($_POST['expiry'] ?? 86400);
        $time_val = max(intval($_POST['time'] ?? 0), 1);
        $seconds = $expiry_unit === 315569260 ? 315569260 : ($time_val * $expiry_unit);

        if (empty($selected_app)) {
            $license_error = 'No application selected';
        } else {
            $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
            $stmt->bind_param("ss", $selected_app, $ownerid);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                if ($seconds >= 315569260) {
                    $stmt = $conn->prepare("UPDATE `keys` SET expiry = 0, duration_seconds = 0 WHERE app = ? AND status = 'active' AND used_at IS NULL");
                    $stmt->bind_param("s", $selected_app);
                } else {
                    $stmt = $conn->prepare("UPDATE `keys` SET duration_seconds = duration_seconds + ? WHERE app = ? AND status = 'active' AND used_at IS NULL AND duration_seconds > 0");
                    $stmt->bind_param("is", $seconds, $selected_app);
                }
                $stmt->execute();
                $license_success = 'Time added to all active keys successfully!';
                log_action($selected_app, 'time_added_keys', $user['username'], "Added time to active keys");
            } else {
                $license_error = 'Invalid application';
            }
        }
    }

    // Ban key (LicenseAuth-style)
    if (isset($_POST['bankey'])) {
        $key_val = sanitize($_POST['bankey'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');
        $ban_user_too = isset($_POST['banUserToo']);

        if (!empty($key_val) && !empty($selected_app)) {
            $stmt = $conn->prepare("SELECT * FROM `keys` WHERE license_key = ? AND app = ?");
            $stmt->bind_param("ss", $key_val, $selected_app);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $stmt = $conn->prepare("UPDATE `keys` SET banned = 1 WHERE license_key = ? AND app = ?");
                $stmt->bind_param("ss", $key_val, $selected_app);
                $stmt->execute();
                if ($ban_user_too && !empty($row['used_by'])) {
                    $stmt = $conn->prepare("UPDATE users SET status = 'banned', ban_reason = ? WHERE username = ? AND app = ?");
                    $stmt->bind_param("sss", $reason, $row['used_by'], $selected_app);
                    $stmt->execute();
                }
                $license_success = 'Key banned successfully!';
                log_action($selected_app, 'key_banned', $user['username'], "Key banned: $key_val");
            } else {
                $license_error = 'Key not found';
            }
        } else {
            $license_error = 'Invalid key or no application selected';
        }
    }

    // Delete key (LicenseAuth-style)
    if (isset($_POST['deletekey'])) {
        $key_val = sanitize($_POST['deletekey'] ?? '');
        $del_user_too = isset($_POST['delUserToo']);

        if (!empty($key_val) && !empty($selected_app)) {
            $stmt = $conn->prepare("SELECT * FROM `keys` WHERE license_key = ? AND app = ?");
            $stmt->bind_param("ss", $key_val, $selected_app);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                if ($del_user_too && !empty($row['used_by'])) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE username = ? AND app = ?");
                    $stmt->bind_param("ss", $row['used_by'], $selected_app);
                    $stmt->execute();
                }
                $stmt = $conn->prepare("DELETE FROM `keys` WHERE license_key = ? AND app = ?");
                $stmt->bind_param("ss", $key_val, $selected_app);
                $stmt->execute();
                $license_success = 'Key deleted successfully!';
                log_action($selected_app, 'key_deleted', $user['username'], "Key deleted: $key_val");
            } else {
                $license_error = 'Key not found';
            }
        } else {
            $license_error = 'Invalid key or no application selected';
        }
    }

    // Delete all keys (LicenseAuth-style)
    if (isset($_POST['delkeys'])) {
        if (!empty($selected_app)) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE app = ?");
            $stmt->bind_param("s", $selected_app);
            $stmt->execute();
            $license_success = 'All keys deleted successfully!';
            log_action($selected_app, 'keys_deleted_all', $user['username'], 'All keys deleted');
        } else {
            $license_error = 'No application selected';
        }
    }

    // Delete all unused keys (LicenseAuth-style)
    if (isset($_POST['deleteallunused'])) {
        if (!empty($selected_app)) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE app = ? AND (used_by IS NULL OR used_by = '')");
            $stmt->bind_param("s", $selected_app);
            $stmt->execute();
            $license_success = 'All unused keys deleted successfully!';
            log_action($selected_app, 'keys_deleted_unused', $user['username'], 'All unused keys deleted');
        } else {
            $license_error = 'No application selected';
        }
    }

    // Delete all used keys (LicenseAuth-style)
    if (isset($_POST['deleteallused'])) {
        if (!empty($selected_app)) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE app = ? AND used_by IS NOT NULL AND used_by != ''");
            $stmt->bind_param("s", $selected_app);
            $stmt->execute();
            $license_success = 'All used keys deleted successfully!';
            log_action($selected_app, 'keys_deleted_used', $user['username'], 'All used keys deleted');
        } else {
            $license_error = 'No application selected';
        }
    }

    // Add subscription (LicenseAuth-style)
    if (isset($_POST['addsub'])) {
        $sub_name = sanitize($_POST['subname'] ?? '');
        $sub_level = intval($_POST['level'] ?? 0);
        if (!empty($sub_name) && !empty($selected_app)) {
            $stmt = $conn->prepare("INSERT INTO subscriptions (app, name, level) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $selected_app, $sub_name, $sub_level);
            if ($stmt->execute()) {
                $app_success = 'Subscription "' . htmlspecialchars($sub_name) . '" added successfully!';
                log_action($selected_app, 'sub_added', $user['username'], "Added subscription: $sub_name (level $sub_level)");
            } else {
                $app_error = 'Error adding subscription';
            }
        } else {
            $app_error = 'Subscription name is required';
        }
    }

    // Delete subscription (LicenseAuth-style)
    if (isset($_POST['deletesub'])) {
        $sub_name = sanitize($_POST['deletesub']);
        if (!empty($sub_name) && !empty($selected_app)) {
            $stmt = $conn->prepare("DELETE FROM subscriptions WHERE name = ? AND app = ?");
            $stmt->bind_param("ss", $sub_name, $selected_app);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $app_success = 'Subscription "' . htmlspecialchars($sub_name) . '" deleted successfully!';
                log_action($selected_app, 'sub_deleted', $user['username'], "Deleted subscription: $sub_name");
            } else {
                $app_error = 'Error deleting subscription or not found';
            }
        } else {
            $app_error = 'No subscription selected';
        }
    }

    // Edit subscription (LicenseAuth-style)
    if (isset($_POST['editsub_submit'])) {
        $orig_name = sanitize($_POST['editsub'] ?? '');
        $new_name = sanitize($_POST['subname'] ?? '');
        $new_level = intval($_POST['level'] ?? 0);
        if (!empty($orig_name) && !empty($new_name) && !empty($selected_app)) {
            $stmt = $conn->prepare("UPDATE subscriptions SET name = ?, level = ? WHERE name = ? AND app = ?");
            $stmt->bind_param("siss", $new_name, $new_level, $orig_name, $selected_app);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $app_success = 'Subscription updated successfully!';
                log_action($selected_app, 'sub_edited', $user['username'], "Edited subscription: $orig_name -> $new_name");
            } else {
                $app_error = 'Error updating subscription or not found';
            }
        } else {
            $app_error = 'Subscription name is required';
        }
    }

    // Upgrade plan (tester/developer)
    if (isset($_POST['upgrade_plan'])) {
        $plan = sanitize($_POST['plan'] ?? '');
        $valid_plans = ['tester', 'developer', 'seller'];
        if (in_array($plan, $valid_plans)) {
            $stmt = $conn->prepare("UPDATE accounts SET role = ? WHERE username = ?");
            $stmt->bind_param("ss", $plan, $user['username']);
            if ($stmt->execute()) {
                $user['role'] = $plan;
                log_action('system', 'plan_upgraded', $user['username'], "Plan changed to $plan");
                header('Location: ' . SITE_URL . '/app/?page=upgrade&upgraded=1');
                exit;
            } else {
                $app_error = 'Error upgrading plan';
            }
        } else {
            $app_error = 'Invalid plan selected';
        }
    }

    // Developer plan button (LicenseAuth-style)
    if (isset($_POST['devoloperplan'])) {
        $stmt = $conn->prepare("UPDATE accounts SET role = 'developer' WHERE username = ?");
        $stmt->bind_param("s", $user['username']);
        if ($stmt->execute()) {
            $user['role'] = 'developer';
            log_action('system', 'plan_upgraded', $user['username'], 'Plan changed to developer');
            header('Location: ' . SITE_URL . '/app/?page=upgrade&upgraded=1');
            exit;
        } else {
            $app_error = 'Error upgrading plan';
        }
    }

    // Seller plan button (LicenseAuth-style)
    if (isset($_POST['sellerplan'])) {
        $stmt = $conn->prepare("UPDATE accounts SET role = 'seller' WHERE username = ?");
        $stmt->bind_param("s", $user['username']);
        if ($stmt->execute()) {
            $user['role'] = 'seller';
            log_action('system', 'plan_upgraded', $user['username'], 'Plan changed to seller');
            header('Location: ' . SITE_URL . '/app/?page=upgrade&upgraded=1');
            exit;
        } else {
            $app_error = 'Error upgrading plan';
        }
    }
}

// ========== User Management Handlers ==========

// Create user
if (isset($_POST['adduser'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $expiry_str = sanitize($_POST['expiry'] ?? '');
    if (empty($username)) {
        $app_error = 'Username is required';
    } elseif (empty($selected_app)) {
        $app_error = 'No application selected';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND app = ?");
        $stmt->bind_param("ss", $username, $selected_app);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $app_error = 'User already exists';
        } else {
            $pw_hash = empty($password) ? '' : password_hash($password, PASSWORD_DEFAULT);
            $exp_ts = !empty($expiry_str) ? strtotime($expiry_str) : 0;
            $stmt = $conn->prepare("INSERT INTO users (app, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $selected_app, $username, $pw_hash);
            if ($stmt->execute()) {
                $app_success = 'User "' . htmlspecialchars($username) . '" created successfully!';
                log_action($selected_app, 'user_created', $user['username'], "Created user: $username");
            } else {
                $app_error = 'Error creating user: ' . $conn->error;
            }
        }
    }
}

// Import users
if (isset($_POST['importusers'])) {
    $users_input = trim($_POST['users'] ?? '');
    $authgg_json = trim($_POST['authgg'] ?? '');
    $imported = 0;
    if (empty($selected_app)) {
        $app_error = 'No application selected';
    } else {
        $stmt = $conn->prepare("SELECT secret FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $selected_app, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            if (!empty($authgg_json)) {
                $data = json_decode($authgg_json, true);
                if ($data && isset($data['users'])) {
                    foreach ($data['users'] as $item) {
                        $un = $item['username'] ?? $item['user'] ?? '';
                        $hwid = $item['hwid'] ?? '';
                        $days = intval($item['days'] ?? 30);
                        if (!empty($un)) {
                            $stmt = $conn->prepare("INSERT IGNORE INTO users (app, username, hwid) VALUES (?, ?, ?)");
                            $stmt->bind_param("sss", $selected_app, $un, $hwid);
                            if ($stmt->execute() && $stmt->affected_rows > 0) $imported++;
                        }
                    }
                }
            } elseif (!empty($users_input)) {
                $parts = explode('|', $users_input);
                foreach ($parts as $part) {
                    $fields = explode(',', $part);
                    if (count($fields) >= 1) {
                        $un = trim($fields[0]);
                        $hwid = isset($fields[1]) ? trim($fields[1]) : '';
                        if (!empty($un)) {
                            $stmt = $conn->prepare("INSERT IGNORE INTO users (app, username, hwid) VALUES (?, ?, ?)");
                            $stmt->bind_param("sss", $selected_app, $un, $hwid);
                            if ($stmt->execute() && $stmt->affected_rows > 0) $imported++;
                        }
                    }
                }
            }
            $app_success = "$imported user(s) imported successfully!";
            log_action($selected_app, 'users_imported', $user['username'], "$imported users imported");
        } else {
            $app_error = 'Invalid application';
        }
    }
}

// Export users
if (isset($_POST['dlusers'])) {
    if (!empty($selected_app)) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE app = ?");
        $stmt->bind_param("s", $selected_app);
        $stmt->execute();
        $result = $stmt->get_result();
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="users.txt"');
        while ($row = $result->fetch_assoc()) {
            echo $row['username'] . PHP_EOL;
        }
        exit;
    }
    $app_error = 'No application selected';
}

// Extend user(s)
if (isset($_POST['extenduser'])) {
    $user_val = sanitize($_POST['user'] ?? '');
    $expiry_unit = intval($_POST['expiry'] ?? 86400);
    $time_val = max(intval($_POST['time'] ?? 0), 1);
    $active_only = isset($_POST['activeOnly']);
    $seconds = $expiry_unit === 315569260 ? 315569260 : ($time_val * $expiry_unit);
    if (empty($selected_app)) {
        $app_error = 'No application selected';
    } else {
        if ($user_val === 'all') {
            if ($seconds >= 315569260) {
                $stmt = $conn->prepare("UPDATE users SET banned = 0 WHERE app = ?");
                $stmt->bind_param("s", $selected_app);
            } else {
                $stmt = $conn->prepare("UPDATE users SET banned = 0 WHERE app = ? AND banned = 0");
                $stmt->bind_param("s", $selected_app);
            }
            $stmt->execute();
            $app_success = 'Users extended successfully!';
        } else {
            $stmt = $conn->prepare("UPDATE users SET banned = 0 WHERE username = ? AND app = ?");
            $stmt->bind_param("ss", $user_val, $selected_app);
            $stmt->execute();
            $app_success = 'User "' . htmlspecialchars($user_val) . '" extended successfully!';
        }
        log_action($selected_app, 'users_extended', $user['username'], "Extended user(s)");
    }
}

// Subtract user(s)
if (isset($_POST['subtractuser'])) {
    $user_val = sanitize($_POST['user'] ?? '');
    $expiry_unit = intval($_POST['expiry'] ?? 86400);
    $time_val = max(intval($_POST['time'] ?? 0), 1);
    $seconds = $time_val * $expiry_unit;
    if (empty($selected_app)) {
        $app_error = 'No application selected';
    } else {
        if ($user_val === 'all') {
            $stmt = $conn->prepare("UPDATE users SET banned = 1 WHERE app = ? AND banned = 0");
            $stmt->bind_param("s", $selected_app);
            $stmt->execute();
            $app_success = 'Time subtracted from all users!';
        } else {
            $app_success = 'User "' . htmlspecialchars($user_val) . '" updated!';
        }
        log_action($selected_app, 'users_subtracted', $user['username'], "Subtracted from user(s)");
    }
}

// Ban user
if (isset($_POST['banuser'])) {
    $un = sanitize($_POST['un'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET banned = 1, ban_reason = ? WHERE username = ? AND app = ?");
        $stmt->bind_param("sss", $reason, $un, $selected_app);
        if ($stmt->execute()) {
            $app_success = 'User "' . htmlspecialchars($un) . '" banned!';
            log_action($selected_app, 'user_banned', $user['username'], "Banned user: $un");
        } else {
            $app_error = 'Error banning user';
        }
    }
}

// Unban user
if (isset($_POST['unbanuser'])) {
    $un = sanitize($_POST['unbanuser'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET banned = 0, ban_reason = NULL WHERE username = ? AND app = ?");
        $stmt->bind_param("ss", $un, $selected_app);
        if ($stmt->execute()) {
            $app_success = 'User "' . htmlspecialchars($un) . '" unbanned!';
            log_action($selected_app, 'user_unbanned', $user['username'], "Unbanned user: $un");
        } else {
            $app_error = 'Error unbanning user';
        }
    }
}

// Delete all users
if (isset($_POST['delusers'])) {
    if (!empty($selected_app)) {
        $stmt = $conn->prepare("DELETE FROM users WHERE app = ?");
        $stmt->bind_param("s", $selected_app);
        $stmt->execute();
        $app_success = 'All users deleted successfully!';
        log_action($selected_app, 'users_deleted_all', $user['username'], 'All users deleted');
    }
}

// Delete expired users
if (isset($_POST['delexpusers'])) {
    if (!empty($selected_app)) {
        $stmt = $conn->prepare("DELETE FROM users WHERE app = ? AND banned = 1");
        $stmt->bind_param("s", $selected_app);
        $stmt->execute();
        $app_success = 'All expired/banned users deleted successfully!';
        log_action($selected_app, 'users_deleted_expired', $user['username'], 'Expired users deleted');
    }
}

// HWID reset all users
if (isset($_POST['resetall'])) {
    if (!empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET hwid = '' WHERE app = ?");
        $stmt->bind_param("s", $selected_app);
        $stmt->execute();
        $app_success = 'HWID reset for all users!';
        log_action($selected_app, 'users_hwid_reset', $user['username'], 'HWID reset all users');
    }
}

// Unban all users
if (isset($_POST['unbanall'])) {
    if (!empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET banned = 0, ban_reason = NULL WHERE app = ?");
        $stmt->bind_param("s", $selected_app);
        $stmt->execute();
        $app_success = 'All users unbanned!';
        log_action($selected_app, 'users_unbanned', $user['username'], 'Unbanned all users');
    }
}

// Set user variable
if (isset($_POST['setvar'])) {
    $user_val = sanitize($_POST['user'] ?? '');
    $var_name = sanitize($_POST['var'] ?? '');
    $var_data = sanitize($_POST['data'] ?? '');
    $read_only = isset($_POST['readOnly']) ? 1 : 0;
    if (empty($var_name) || empty($var_data)) {
        $app_error = 'Variable name and data are required';
    } elseif (empty($selected_app)) {
        $app_error = 'No application selected';
    } else {
        $stmt = $conn->prepare("INSERT INTO uservars (app, user, name, data, readonly) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data), readonly = VALUES(readonly)");
        $stmt->bind_param("ssssi", $selected_app, $user_val, $var_name, $var_data, $read_only);
        if ($stmt->execute()) {
            $app_success = 'Variable set successfully!';
            log_action($selected_app, 'user_var_set', $user['username'], "Set var $var_name for $user_val");
        } else {
            $app_error = 'Error setting variable';
        }
    }
}

// ========== End User Management Handlers ==========

// Delete user
if (isset($_POST['deleteuser'])) {
    $un = sanitize($_POST['deleteuser'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ? AND app = ?");
        $stmt->bind_param("ss", $un, $selected_app);
        if ($stmt->execute()) {
            $app_success = 'User "' . htmlspecialchars($un) . '" deleted!';
            log_action($selected_app, 'user_deleted', $user['username'], "Deleted user: $un");
        } else {
            $app_error = 'Error deleting user';
        }
    }
}

// Reset HWID
if (isset($_POST['resetuser'])) {
    $un = sanitize($_POST['resetuser'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET hwid = '' WHERE username = ? AND app = ?");
        $stmt->bind_param("ss", $un, $selected_app);
        if ($stmt->execute()) {
            $app_success = 'HWID reset for "' . htmlspecialchars($un) . '"!';
            log_action($selected_app, 'user_hwid_reset', $user['username'], "HWID reset: $un");
        } else {
            $app_error = 'Error resetting HWID';
        }
    }
}

// Pause user
if (isset($_POST['pauseuser'])) {
    $un = sanitize($_POST['pauseuser'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET paused = 1 WHERE username = ? AND app = ?");
        $stmt->bind_param("ss", $un, $selected_app);
        if ($stmt->execute()) {
            $app_success = 'User "' . htmlspecialchars($un) . '" paused!';
            log_action($selected_app, 'user_paused', $user['username'], "Paused user: $un");
        } else {
            $app_error = 'Error pausing user';
        }
    }
}

// Unpause user
if (isset($_POST['unpauseuser'])) {
    $un = sanitize($_POST['unpauseuser'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $stmt = $conn->prepare("UPDATE users SET paused = 0 WHERE username = ? AND app = ?");
        $stmt->bind_param("ss", $un, $selected_app);
        if ($stmt->execute()) {
            $app_success = 'User "' . htmlspecialchars($un) . '" unpaused!';
            log_action($selected_app, 'user_unpaused', $user['username'], "Unpaused user: $un");
        } else {
            $app_error = 'Error unpausing user';
        }
    }
}

// Edit user submit
if (isset($_POST['edituser_submit'])) {
    $un = sanitize($_POST['un'] ?? '');
    $sub = sanitize($_POST['sub'] ?? '');
    $hwid = sanitize($_POST['hwid'] ?? '');
    $expiry_str = sanitize($_POST['expiry'] ?? '');
    if (!empty($un) && !empty($selected_app)) {
        $updates = [];
        $params = [];
        $types = '';
        if (!empty($sub)) {
            $updates[] = 'subscription = ?';
            $params[] = $sub;
            $types .= 's';
        }
        if (!empty($hwid)) {
            $updates[] = 'hwid = ?';
            $params[] = $hwid;
            $types .= 's';
        }
        if (!empty($expiry_str)) {
            $exp_ts = date('Y-m-d H:i:s', strtotime($expiry_str));
            $updates[] = 'subscription_expiry = ?';
            $params[] = $exp_ts;
            $types .= 's';
        }
        if (!empty($updates)) {
            $params[] = $un;
            $params[] = $selected_app;
            $types .= 'ss';
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE username = ? AND app = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $app_success = 'User "' . htmlspecialchars($un) . '" updated!';
                log_action($selected_app, 'user_edited', $user['username'], "Edited user: $un");
            } else {
                $app_error = 'Error updating user';
            }
        } else {
            $app_error = 'No changes specified';
        }
    }
}

// Create sub-account (reseller/manager)
if (isset($_POST['createacc'])) {
    $sub_role = strtolower(sanitize($_POST['role'] ?? ''));
    $sub_app = sanitize($_POST['app'] ?? '');
    $sub_user = sanitize($_POST['username'] ?? '');
    $sub_email = sanitize($_POST['email'] ?? '');
    $sub_pw = $_POST['pw'] ?? '';
    $sub_levels = sanitize($_POST['keylevels'] ?? '');

    if (!in_array($sub_role, ['seller', 'manager'])) {
        $app_error = 'Invalid role selected';
    } elseif (empty($sub_user) || empty($sub_email) || empty($sub_pw)) {
        $app_error = 'Username, email, and password are required';
    } else {
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $sub_user);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $app_error = 'Username already exists';
        } else {
            $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
            $stmt->bind_param("s", $sub_email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $app_error = 'Email already registered';
            } else {
                $pw_hash = password_hash($sub_pw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, ownerid, role, app, balance, allowed_levels) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
                $stmt->bind_param("sssssss", $sub_user, $sub_email, $pw_hash, $ownerid, $sub_role, $sub_app, $sub_levels);
                if ($stmt->execute()) {
                    $app_success = 'Account "' . htmlspecialchars($sub_user) . '" created successfully!';
                    log_action('system', 'sub_account_created', $user['username'], "Created $sub_role account: $sub_user");
                } else {
                    $app_error = 'Error creating account: ' . $conn->error;
                }
            }
        }
    }
}

// Delete sub-account
if (isset($_POST['deleteacc'])) {
    $del_user = sanitize($_POST['deleteacc']);
    if (!empty($del_user) && $del_user !== $user['username']) {
        $stmt = $conn->prepare("DELETE FROM accounts WHERE username = ? AND ownerid = ?");
        $stmt->bind_param("ss", $del_user, $ownerid);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $app_success = 'Account "' . htmlspecialchars($del_user) . '" deleted successfully';
            log_action('system', 'sub_account_deleted', $user['username'], "Deleted account: $del_user");
        } else {
            $app_error = 'Error deleting account or account not found';
        }
    } else {
        $app_error = 'Invalid account';
    }
}

// Edit sub-account
if (isset($_POST['editacc_submit'])) {
    $edit_user = sanitize($_POST['editacc']);
    $new_balance = isset($_POST['balance']) ? intval($_POST['balance']) : null;
    $new_levels = sanitize($_POST['allowed_levels'] ?? '');
    $new_app = sanitize($_POST['app'] ?? '');

    if (!empty($edit_user) && $edit_user !== $user['username']) {
        $updates = [];
        $params = [];
        $types = '';
        if ($new_balance !== null) {
            $updates[] = 'balance = ?';
            $params[] = $new_balance;
            $types .= 'i';
        }
        $updates[] = 'allowed_levels = ?';
        $params[] = $new_levels;
        $types .= 's';
        $updates[] = 'app = ?';
        $params[] = $new_app;
        $types .= 's';
        $params[] = $edit_user;
        $params[] = $ownerid;
        $types .= 'ss';
        $sql = "UPDATE accounts SET " . implode(', ', $updates) . " WHERE username = ? AND ownerid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $app_success = 'Account "' . htmlspecialchars($edit_user) . '" updated successfully';
            log_action('system', 'sub_account_edited', $user['username'], "Edited account: $edit_user");
        } else {
            $app_error = 'Error updating account or account not found';
        }
    } else {
        $app_error = 'Invalid account';
    }
}

// App settings form (updatesettings button - no action param)
if (isset($_POST['updatesettings'])) {
    $app_secret = $selected_app;
    if (empty($app_secret)) {
        $app_error = 'No application selected';
    } else {
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $app_error = 'Application not found';
        } else {
            $allowed_keys = [
                'statusinput','hwidinput','forceHwid','minHwid','vpninput','hashinput','tokeninput',
                'version','download','webdownload','webhook','resellerstore','panelstatus',
                'customDomain','customerPanelIcon','cooldownexpiry','cooldownduration',
                'sessionexpiry','sessionduration','minUsernameLength','blockLeakedPasswords',
                'appdisabled','tokeninvalid','hashcheckfail','vpnblocked','usernametaken',
                'keynotfound','keyused','keybanned','nosublevel','userbanned','usernamenotfound',
                'passmismatch','hwidmismatch','noactivesubs','hwidblacked','pausedsub',
                'sessionunauthed','loggedInMsg','pausedApp','unTooShort','pwLeaked','chatHitDelay',
                'shoppywebhooksecret','shoppydayproduct','shoppyweekproduct','shoppymonthproduct','shoppylifetimeproduct',
                'sellixwebhooksecret','sellixdayproduct','sellixweekproduct','sellixmonthproduct','sellixlifetimeproduct',
                'sellappwebhooksecret','sellappdayproduct','sellappweekproduct','sellappmonthproduct','sellapplifetimeproduct'
            ];
            $settings = [];
            foreach ($allowed_keys as $k) {
                if (isset($_POST[$k])) {
                    $settings[$k] = sanitize($_POST[$k]);
                }
            }
            $settings_json = json_encode($settings);
            $stmt = $conn->prepare("UPDATE apps SET version = ?, settings = ?, modified_at = NOW() WHERE secret = ? AND ownerid = ?");
            $ver = sanitize($_POST['version'] ?? '1.0');
            $stmt->bind_param("ssss", $ver, $settings_json, $app_secret, $ownerid);
            if ($stmt->execute()) {
                $app_success = 'Settings saved successfully';
                log_action($app_secret, 'settings_updated', $user['username'], 'App settings updated');
            } else {
                $app_error = 'Error saving settings';
            }
        }
    }
}

// Add hash
if (isset($_POST['addhash'])) {
    $app_secret = $selected_app;
    $hash = sanitize($_POST['hash'] ?? '');
    if (!empty($app_secret) && !empty($hash)) {
        $stmt = $conn->prepare("UPDATE apps SET hash = ? WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("sss", $hash, $app_secret, $ownerid);
        if ($stmt->execute()) {
            $app_success = 'Hash added successfully';
            log_action($app_secret, 'hash_added', $user['username'], 'App hash added');
        } else {
            $app_error = 'Error adding hash';
        }
    }
}

// Reset hash
if (isset($_POST['resethash'])) {
    $app_secret = $selected_app;
    if (!empty($app_secret)) {
        $stmt = $conn->prepare("UPDATE apps SET hash = NULL WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        if ($stmt->execute()) {
            $app_success = 'Hash reset successfully';
            log_action($app_secret, 'hash_reset', $user['username'], 'App hash reset');
        } else {
            $app_error = 'Error resetting hash';
        }
    }
}

// Kill a single session
if (isset($_POST['killsession'])) {
    $session_id = sanitize($_POST['killsession']);
    $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ? AND app = ?");
    $stmt->bind_param("ss", $session_id, $selected_app);
    if ($stmt->execute()) {
        $app_success = 'Session killed';
    } else {
        $app_error = 'Error killing session';
    }
}

// Kill all sessions for the selected app
if (isset($_POST['killall'])) {
    $stmt = $conn->prepare("DELETE FROM sessions WHERE app = ?");
    $stmt->bind_param("s", $selected_app);
    if ($stmt->execute()) {
        $app_success = 'All sessions killed';
    } else {
        $app_error = 'Error killing sessions';
    }
}

// Update application settings (action based)
if ($action === 'update_app') {
    $app_secret = sanitize($_POST['app'] ?? '');
    $app_name = sanitize($_POST['app_name'] ?? '');
    $version = sanitize($_POST['version'] ?? '1.0');
    $description = sanitize($_POST['description'] ?? '');

    if (!empty($app_name)) {
        $stmt = $conn->prepare("UPDATE apps SET name = ?, version = ?, description = ?, modified_at = NOW() WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("sssss", $app_name, $version, $description, $app_secret, $ownerid);
        if ($stmt->execute()) {
            $app_success = 'Application settings updated successfully';
            log_action($app_secret, 'app_updated', $user['username'], 'Application settings updated');
        } else {
            $app_error = 'Error updating application';
        }
    } else {
        $app_error = 'App name is required';
    }
}

// Get user's applications
$stmt = $conn->prepare("SELECT * FROM apps WHERE ownerid = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $ownerid);
$stmt->execute();
$apps_result = $stmt->get_result();
$applications = [];

while ($app = $apps_result->fetch_assoc()) {
    $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM `keys` WHERE app = ? AND status = 'active'");
    $stmt2->bind_param("s", $app['secret']);
    $stmt2->execute();
    $app['active_keys'] = $stmt2->get_result()->fetch_assoc()['count'];

    $stmt3 = $conn->prepare("SELECT COUNT(*) as count FROM `users` WHERE app = ?");
    $stmt3->bind_param("s", $app['secret']);
    $stmt3->execute();
    $app['total_users'] = $stmt3->get_result()->fetch_assoc()['count'];

    $applications[] = $app;
}

// If only one app exists and no app is selected, select it automatically
if (empty($selected_app) && count($applications) === 1) {
    $selected_app = $applications[0]['secret'];
    $_SESSION['selected_app'] = $selected_app;
}

// Get stats
$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM apps WHERE ownerid = ?) as total_apps,
    (SELECT COUNT(*) FROM `keys` k JOIN apps a ON k.app = a.secret WHERE a.ownerid = ? AND k.status = 'active') as total_keys,
    (SELECT COUNT(*) FROM `users` u JOIN apps a ON u.app = a.secret WHERE a.ownerid = ?) as total_users
");
$stmt->bind_param("sss", $ownerid, $ownerid, $ownerid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get selected app details if selected
$selected_app_data = null;
if (!empty($selected_app)) {
    $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
    $stmt->bind_param("ss", $selected_app, $ownerid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $selected_app_data = $result->fetch_assoc();
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <style>body{background:#0f111a!important;}html,body{background-color:#0f111a!important;}#scrollbar{overflow-y:auto;max-height:calc(100vh - 70px)}#scrollbar::-webkit-scrollbar{width:4px}#scrollbar::-webkit-scrollbar-thumb{background:#2d3041;border-radius:4px}</style>
    <meta charset="utf-8" />
    <title><?= SITE_NAME ?> - Secure Authentication System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="LicenseAuth - Secure Authentication System" name="description" />
    <meta content="LicenseAuth" name="author" />
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/images/favicon.ico">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/libs/datatables/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/libs/datatables/css/responsive.bootstrap.min.css" />
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/libs/datatables/css/buttons.dataTables.min.css">
    <style>
        table.dataTable>thead .sorting:before,
        table.dataTable>thead .sorting:after,
        table.dataTable>thead .sorting_asc:before,
        table.dataTable>thead .sorting_asc:after,
        table.dataTable>thead .sorting_desc:before,
        table.dataTable>thead .sorting_desc:after {
            font-size: 14px !important;
            opacity: 1 !important;
        }
        table.dataTable>thead .sorting:before { color: #adb5bd !important; }
        table.dataTable>thead .sorting:after { color: #adb5bd !important; }
        table.dataTable>thead .sorting_asc:before { color: #405189 !important; }
        table.dataTable>thead .sorting_asc:after { color: #adb5bd !important; }
        table.dataTable>thead .sorting_desc:before { color: #adb5bd !important; }
        table.dataTable>thead .sorting_desc:after { color: #405189 !important; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/datatables/js/jquery.dataTables.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/datatables/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/datatables/js/dataTables.responsive.min.js"></script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <script src="<?= SITE_URL ?>/assets/js/layout.js"></script>
    <link href="<?= SITE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
</head>
<body>

<div id="layout-wrapper">

    <header id="page-topbar">
        <div class="layout-width">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box horizontal-logo">
                        <a href="?page=manage-apps" class="logo logo-dark">
                            <span class="logo-sm"><img src="<?= SITE_URL ?>/assets/images/logo-sm.png" alt="" height="22"></span>
                            <span class="logo-lg"><img src="<?= SITE_URL ?>/assets/images/logo-dark.png" alt="" height="17"></span>
                        </a>
                        <a href="?page=manage-apps" class="logo logo-light">
                            <span class="logo-sm"><img src="<?= SITE_URL ?>/assets/images/logo-sm.png" alt="" height="22"></span>
                            <span class="logo-lg"><img src="<?= SITE_URL ?>/assets/images/logo-light.png" alt="" height="17"></span>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger material-shadow-none" id="topnav-hamburger-icon">
                        <span class="hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                    
                    <!-- App Selector Dropdown -->
                    <?php if (count($applications) > 0): ?>
                    <div class="dropdown ms-2 d-flex align-items-center">
                        <button type="button" class="btn btn-outline-light btn-sm dropdown-toggle text-start d-flex align-items-center gap-1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height:36px;border-color:rgba(255,255,255,0.15);background:rgba(255,255,255,0.02)">
                            <i class="ri-cube-line text-primary fs-16"></i>
                            <span class="d-none d-md-inline text-muted me-1">App:</span>
                            <span class="fw-semibold"><?= $selected_app_data ? htmlspecialchars($selected_app_data['name']) : 'Select Application' ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start">
                            <h6 class="dropdown-header">Switch Application</h6>
                            <?php foreach ($applications as $a): ?>
                            <a class="dropdown-item<?= $selected_app === $a['secret'] ? ' active' : '' ?>" href="?page=<?= $page ?>&app=<?= urlencode($a['secret']) ?>">
                                <i class="ri-cube-line align-middle me-2"></i><?= htmlspecialchars($a['name']) ?>
                            </a>
                            <?php endforeach; ?>
                            <?php if (count($applications) > 1): ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="?page=manage-apps">
                                <i class="ri-apps-line align-middle me-2"></i>View All Apps
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center">
                    <div class="ms-1 header-item d-none d-sm-flex">
                        <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle light-dark-mode">
                            <i class="mdi mdi-weather-night fs-22"></i>
                        </button>
                    </div>
                    <div class="dropdown ms-sm-3 header-item topbar-user">
                        <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="d-flex align-items-center">
                                <img class="rounded-circle header-profile-user" src="<?= SITE_URL ?>/assets/images/favicon.png" alt="Header Avatar">
                                <span class="text-start ms-xl-2">
                                    <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"><?= htmlspecialchars($user['username']) ?></span>
                                    <span class="d-none d-xl-block ms-1 fs-12 text-<?= ($user['role'] ?? 'owner') === 'owner' ? 'warning' : (($user['role'] ?? '') === 'developer' ? 'primary' : (($user['role'] ?? '') === 'seller' ? 'warning' : 'info')) ?>"><?= ($user['role'] ?? 'owner') === 'seller' ? 'Seller' : ucfirst($user['role'] ?? 'Owner') ?></span>
                                </span>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Welcome <?= htmlspecialchars($user['username']) ?>!</h6>
                            <a class="dropdown-item" href="?page=account-settings"><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Settings</span></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="?page=help"><span class="badge bg-success-subtle text-success mt-1 float-end">New</span><i class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Help</span></a>
                            <a class="dropdown-item" href="../logout.php"><i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Logout</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="app-menu navbar-menu">
        <div class="navbar-brand-box">
            <a href="?page=manage-apps" class="logo logo-dark">
                <span class="logo-sm"><img src="<?= SITE_URL ?>/assets/images/logo-sm.png" alt="" height="35"></span>
                <span class="logo-lg"><img src="<?= SITE_URL ?>/assets/images/logo-dark.png" alt="" height="30"></span>
            </a>
            <a href="?page=manage-apps" class="logo logo-light">
                <span class="logo-sm"><img src="<?= SITE_URL ?>/assets/images/logo-sm.png" alt="" height="35"></span>
                <span class="logo-lg"><img src="<?= SITE_URL ?>/assets/images/logo-light.png" alt="" height="30"></span>
            </a>
            <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                <i class="ri-record-circle-line"></i>
            </button>
        </div>
        <div id="scrollbar">
            <div class="container-fluid">
                <div id="two-column-menu"></div>
                <ul class="navbar-nav" id="navbar-nav">
                    <li class="menu-title"><span>Dashboard</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'manage-apps' ? ' active' : '' ?>" href="?page=manage-apps">
                            <i class="ri-apps-line"></i> <span>Manage App</span>
                        </a>
                    </li>

                    <li class="menu-title"><i class="ri-more-fill"></i> <span>App Control</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'licenses' ? ' active' : '' ?>" href="?page=licenses">
                            <i class="ri-key-line"></i> <span>Licenses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'users' ? ' active' : '' ?>" href="?page=users">
                            <i class="ri-group-line"></i> <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'subscriptions' ? ' active' : '' ?>" href="?page=subscriptions">
                            <i class="ri-vip-crown-line"></i> <span>Subscriptions</span>
                        </a>
                    </li>
                    <?php if (!in_array(($user['role'] ?? ''), ['developer', 'tester'])): ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'chats' ? ' active' : '' ?>" href="?page=chats">
                            <i class="ri-chat-1-line"></i> <span>Chats</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'sessions' ? ' active' : '' ?>" href="?page=sessions">
                            <i class="ri-time-line"></i> <span>Sessions</span>
                        </a>
                    </li>
                    <?php if (($user['role'] ?? '') !== 'tester'): ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'webhooks' ? ' active' : '' ?>" href="?page=webhooks">
                            <i class="ri-send-to-back"></i> <span>Webhooks</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'files' ? ' active' : '' ?>" href="?page=files">
                            <i class="ri-file-line"></i> <span>Files</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'vars' ? ' active' : '' ?>" href="?page=vars">
                            <i class="ri-code-line"></i> <span>Variables</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'logs' ? ' active' : '' ?>" href="?page=logs">
                            <i class="ri-archive-line"></i> <span>Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'blacklists' ? ' active' : '' ?>" href="?page=blacklists">
                            <i class="ri-prohibited-line"></i> <span>Blacklists</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'app-settings' ? ' active' : '' ?>" href="?page=app-settings">
                            <i class="ri-settings-line"></i> <span>App Settings</span>
                        </a>
                    </li>

                    <li class="menu-title"><i class="ri-more-fill"></i> <span>Accounts Settings</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'manage-account' ? ' active' : '' ?>" href="?page=manage-account">
                            <i class="ri-group-line"></i> <span>Manage Account</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'account-settings' ? ' active' : '' ?>" href="?page=account-settings">
                            <i class="ri-settings-line"></i> <span>Account Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'upgrade' ? ' active' : '' ?>" href="?page=upgrade">
                            <i class="ri-vip-line"></i> <span>Upgrade Account</span>
                        </a>
                    </li>

                    <li class="menu-title"><i class="ri-more-fill"></i> <span>Seller Settings</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'seller-settings' ? ' active' : '' ?>" href="?page=seller-settings">
                            <i class="ri-tools-line"></i> <span>Settings</span>
                        </a>
                    </li>
                    <?php if (!in_array(($user['role'] ?? ''), ['developer', 'tester'])): ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'webloader' ? ' active' : '' ?>" href="?page=webloader">
                            <i class="ri-window-line"></i> <span>Web Loader</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'seller-logs' ? ' active' : '' ?>" href="?page=seller-logs">
                            <i class="ri-archive-line"></i> <span>Logs</span>
                        </a>
                    </li>

                    <?php if (($user['role'] ?? '') === 'owner'): ?>
                    <li class="menu-title"><i class="ri-more-fill"></i> <span>Admin Panel</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'admin-dashboard' ? ' active' : '' ?>" href="?page=admin-dashboard">
                            <i class="ri-dashboard-line"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'admin-users' ? ' active' : '' ?>" href="?page=admin-users">
                            <i class="ri-group-line"></i> <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'admin-apps' ? ' active' : '' ?>" href="?page=admin-apps">
                            <i class="ri-apps-line"></i> <span>Applications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link<?= $page === 'admin-logs' ? ' active' : '' ?>" href="?page=admin-logs">
                            <i class="ri-archive-line"></i> <span>Logs</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="sidebar-background"></div>
    </div>
    <div class="vertical-overlay"></div>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <?php if ($create_success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-check-line me-1"></i> <?= htmlspecialchars($create_success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if ($app_success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="ri-check-line me-1"></i> <?= htmlspecialchars($app_success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php
                    switch ($page) {
                        case 'manage-apps':
                            include 'pages/manage-apps.php';
                            break;
                        case 'licenses':
                            include 'pages/licenses.php';
                            break;
                        case 'users':
                            include 'pages/users.php';
                            break;
                        case 'subscriptions':
                            include 'pages/subscriptions.php';
                            break;
                        case 'chats':
                            if (in_array(($user['role'] ?? ''), ['developer', 'tester'])) { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; break; }
                            include 'pages/chats.php';
                            break;
                        case 'sessions':
                            include 'pages/sessions.php';
                            break;
                        case 'webhooks':
                            if (($user['role'] ?? '') === 'tester') { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; break; }
                            include 'pages/webhooks.php';
                            break;
                        case 'files':
                            include 'pages/files.php';
                            break;
                        case 'vars':
                            include 'pages/variables.php';
                            break;
                        case 'logs':
                            include 'pages/logs.php';
                            break;
                        case 'blacklists':
                            include 'pages/blacklist.php';
                            break;
                        case 'app-settings':
                            include 'pages/app-settings.php';
                            break;
                        case 'account-settings':
                            include 'pages/account-settings.php';
                            break;
                        case 'seller-settings':
                            include 'pages/seller-settings.php';
                            break;
                        case 'webloader':
                            if (in_array(($user['role'] ?? ''), ['developer', 'tester'])) { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; break; }
                            include 'pages/webloader.php';
                            break;
                        case 'seller-logs':
                            include 'pages/seller-logs.php';
                            break;
                        case 'admin-dashboard':
                            if (($user['role'] ?? '') === 'owner') { include 'pages/admin-dashboard.php'; } else { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; }
                            break;
                        case 'admin-users':
                            if (($user['role'] ?? '') === 'owner') { include 'pages/admin-users.php'; } else { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; }
                            break;
                        case 'admin-apps':
                            if (($user['role'] ?? '') === 'owner') { include 'pages/admin-apps.php'; } else { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; }
                            break;
                        case 'admin-logs':
                            if (($user['role'] ?? '') === 'owner') { include 'pages/admin-logs.php'; } else { echo '<div class="text-center py-5"><h4 class="text-muted">Access denied</h4></div>'; }
                            break;
                        case 'manage-account':
                            include 'pages/manage-account.php';
                            break;
                        case 'upgrade':
                            include 'pages/upgrade.php';
                            break;
                        case 'help':
                            echo '<div class="text-center py-5"><h4 class="text-muted">Help & Support</h4><p class="text-muted">Contact support for assistance.</p></div>';
                            break;
                        default:
                            include 'pages/manage-apps.php';
                    }
                ?>

            </div>
        </div>

        <div class="modal fade" id="create-app-modal" tabindex="-1" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_app">
                            <div class="row g-3">
                                <div class="mb-3">
                                    <div>
                                        <input type="text" class="form-control" name="app_name" placeholder="Enter App Name" autocomplete="on" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Create</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy; 2021 - <?= date('Y') ?> <?= SITE_NAME ?>. Made with <i class="mdi mdi-heart text-danger"></i> by FENIX</p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

</div>

<script src="<?= SITE_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/libs/simplebar/simplebar.min.js"></script>
<script src="<?= SITE_URL ?>/assets/libs/node-waves/waves.min.js"></script>
<script src="<?= SITE_URL ?>/assets/libs/feather-icons/feather.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
<script src="<?= SITE_URL ?>/assets/js/plugins.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    var sidebarToggle = document.getElementById('topnav-hamburger-icon');
    var html = document.documentElement;
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            var size = html.getAttribute('data-sidebar-size');
            html.setAttribute('data-sidebar-size', size === 'sm' ? 'lg' : 'sm');
        });
    }

    // Full-page reload on every menu link click
    var navLinks = document.querySelectorAll('.navbar-nav .nav-link.menu-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href && href.indexOf('?page=') !== -1) {
                e.preventDefault();
                window.location.href = href;
            }
        });
    });
});
</script>



</body>
</html>
