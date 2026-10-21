<?php
/**
 * Enhanced License Authentication API v1.2
 * KeyAuth Compatible - Full Integration
 * Supports: Init, Register, Login, License, Check, Variables, Webhooks, Chat, Download
 */

// Set error handler before anything else
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../logs/api_errors.log');

// Set timezone to IST
date_default_timezone_set('Asia/Calcutta');

// Global exception handler
set_exception_handler(function($exception) {
    http_response_code(200); // Keep 200 for API compatibility
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $exception->getMessage()
    ]);
    error_log('API Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    exit;
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, signature');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/enhanced.php';
    
// Global response function with signature support
// When $flat is true, $data fields are merged at top-level (KeyAuth expected shape)
// $data_key controls the property name when not flat (default 'data', but can be 'info')
function send_response($success, $message, $data = null, $enckey = null, $flat = false, $data_key = 'data') {
    try {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            if ($flat && is_array($data)) {
                // Merge data fields into top-level
                foreach ($data as $k => $v) {
                    $response[$k] = $v;
                }
            } else {
                $response[$data_key] = $data;
            }
        }
        
        // Generate signature if enckey provided
        if ($enckey) {
            $response_json = json_encode($response);
            $signature = hash_hmac('sha256', $response_json, $enckey);
            header('signature: ' . $signature);
            error_log('Response with signature: ' . $response_json);
            error_log('Signature: ' . $signature);
            error_log('Enckey used for signature: ' . $enckey . ' (length: ' . strlen($enckey) . ')');
        } else {
            error_log('Response without signature: ' . json_encode($response));
            error_log('No enckey provided for signature');
        }
        
        echo json_encode($response);
    } catch (Exception $e) {
        error_log('send_response error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Response generation failed']);
    }
    exit;
}

// Rate limiting check
$identifier = get_ip();
if (!check_rate_limit($identifier)) {
    send_response(false, "Too many requests. Please try again later.");
}

// Handle JSON POST data (KeyAuth C# client sends JSON)
$input_data = $_POST;
if (empty($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    if ($raw_input) {
        $json_data = json_decode($raw_input, true);
        if (is_array($json_data)) {
            $input_data = $json_data;
        }
    }
}

// Log incoming request for debugging
error_log('=== INCOMING REQUEST ===');
error_log('Input Data: ' . json_encode($input_data));
error_log('GET: ' . json_encode($_GET));

// Get request parameters (KeyAuth compatible)
$ownerid = sanitize($input_data['ownerid'] ?? $_GET['ownerid'] ?? '');
$name = sanitize($input_data['name'] ?? $_GET['name'] ?? '');
$ver_param = sanitize($input_data['ver'] ?? $_GET['ver'] ?? '');
$type_param = sanitize($input_data['type'] ?? $_GET['type'] ?? '');
$sessionid_param = sanitize($input_data['sessionid'] ?? $_GET['sessionid'] ?? '');

// Extract other common parameters
$hwid_param = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? '');

error_log("Received - ownerid: '$ownerid', name: '$name', type: '$type_param'");

// Validate app credentials
if (empty($ownerid) || empty($name)) {
    error_log('VALIDATION FAILED - Missing ownerid or name');
    send_response(false, "Invalid application credentials");
}

// Look up app secret from database (KeyAuth doesn't send secret in requests)
$app = get_app_details($ownerid, $name);
if (!$app) {
    error_log('VALIDATION FAILED - App not found in database');
    send_response(false, "Invalid application credentials");
}

$secret = $app['secret'];
$aid_param = $secret; // app identifier
$app_secret = $secret; // Use the secret we already fetched
error_log("App found - secret: " . substr($secret, 0, 8) . "...");

// Route to appropriate endpoint

// Resolve enckey for signing responses (uses session, if provided)
function resolve_enckey($sessionid, $app_secret) {
    if (empty($sessionid)) {
        error_log('resolve_enckey: No sessionid provided');
        return null;
    }
    $session = get_session($sessionid, $app_secret);
    if ($session) {
        error_log('resolve_enckey: Session found, enckey: ' . $session['enckey'] . ' (length: ' . strlen($session['enckey']) . ')');
        return $session['enckey'];
    } else {
        error_log('resolve_enckey: Session not found for sessionid: ' . $sessionid);
        return null;
    }
}

// Enforce session presence for actions that must be signed
function require_session_with_enckey($sessionid, $app_secret) {
    if (empty($sessionid)) {
        error_log('require_session_with_enckey: EMPTY sessionid provided');
        send_response(false, "Session required - no session ID provided");
    }
    $session = get_session($sessionid, $app_secret);
    if (!$session) {
        error_log('require_session_with_enckey: Session not found - sessionid=' . $sessionid . ' app=' . substr($app_secret,0,8));
        send_response(false, "Session expired or not found");
    }
    return $session;
}

$response_enckey = resolve_enckey($sessionid_param, $app_secret);

// Helper function to get app details by ownerid and name
function get_app_details($ownerid, $name) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM apps WHERE ownerid = ? AND name = ?");
    $stmt->bind_param("ss", $ownerid, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// Check if app is paused
if ($app['paused']) {
    send_response(false, "This application is currently paused", null, $response_enckey);
}

// ===== CORE FUNCTIONS =====

function initialize_session($app_secret) {
    global $conn;
    $sessionid = generate_session_id($app_secret, get_ip());
    $expiry = time() + 3600;
    $ip = get_ip();
    $useragent = get_user_agent();
        // Generate enckey in format that C# client expects
        // C# does: enckey.Substring(17, 64) for init
        // So we create: [17 chars prefix] + [64 char key] = 81 chars total
        $prefix = bin2hex(random_bytes(8)) . '0'; // 17 characters
        $key = bin2hex(random_bytes(32)); // 64 characters
        $enckey = $prefix . $key; // 81 characters total
    
    $stmt = $conn->prepare("INSERT INTO `sessions` (id, app, expiry, ip, useragent, enckey) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $sessionid, $app_secret, $expiry, $ip, $useragent, $enckey);
    $stmt->execute();
    
    return ['sessionid' => $sessionid, 'enckey' => $enckey];
}

function register_user($app_secret, $username, $key, $password, $hwid = null, $email = null) {
    global $conn;
    
    // Validate input
    if (strlen($username) < 3 || strlen($username) > 70) {
        return ['success' => false, 'message' => 'Username must be 3-70 characters'];
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE app = ? AND username = ?");
    $stmt->bind_param("ss", $app_secret, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username already taken'];
    }
    
    // Validate license key
    $stmt = $conn->prepare("SELECT * FROM `keys` WHERE app = ? AND license_key = ? AND status = 'active'");
    $stmt->bind_param("ss", $app_secret, $key);
    $stmt->execute();
    $key_result = $stmt->get_result();
    
    if ($key_result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid or expired license key'];
    }
    
    $key_data = $key_result->fetch_assoc();
    
    // Check key expiry
    if ($key_data['expiry'] > 0 && $key_data['expiry'] < time()) {
        return ['success' => false, 'message' => 'License key has expired'];
    }
    
    // Check if blacklisted
    if (is_blacklisted($app_secret, 'username', $username)) {
        return ['success' => false, 'message' => 'Username is blacklisted'];
    }
    
    if ($hwid && is_blacklisted($app_secret, 'hwid', $hwid)) {
        return ['success' => false, 'message' => 'Hardware ID is blacklisted'];
    }
    
    // Hash password
    $password_hash = hash_password($password);
    $ip = get_ip();
    
    // Create user
    $stmt = $conn->prepare("INSERT INTO users (app, username, email, password, hwid, ip) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $app_secret, $username, $email, $password_hash, $hwid, $ip);
    
    if ($stmt->execute()) {
        // Update key as used
        $stmt = $conn->prepare("UPDATE keys SET status = 'inactive', used_by = ?, used_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $username, $key_data['id']);
        $stmt->execute();
        
        // Create subscription from key expiry
        $sub_name = 'default';
        $sub_expiry = intval($key_data['expiry'] ?? 0);
        if ($sub_expiry > 0) {
            $stmt = $conn->prepare("INSERT INTO subs (app, user, sub_name, expiry) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $app_secret, $username, $sub_name, $sub_expiry);
            $stmt->execute();
        }
        
        // Log action
        log_action($app_secret, 'register', $username, 'User registered', $ip);
        
        return ['success' => true, 'message' => 'User registered successfully'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

function login_user($app_secret, $username, $password, $hwid = null) {
    global $conn;
    
    // Sanitize input
    $username = sanitize($username);
    
    // Get user
    $stmt = $conn->prepare("SELECT * FROM users WHERE app = ? AND username = ?");
    $stmt->bind_param("ss", $app_secret, $username);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        return ['success' => false, 'message' => 'Username not found'];
    }
    
    $user = $user_result->fetch_assoc();
    
    // Check if banned
    if (intval($user['banned'] ?? 0) === 1) {
        $reason = $user['ban_reason'] ?? 'Banned';
        return ['success' => false, 'message' => 'User banned: ' . $reason];
    }
    
    // Check if paused
    if (intval($user['paused'] ?? 0) === 1) {
        log_action($app_secret, 'login_paused', $username, 'Paused user attempted login', get_ip());
        return ['success' => false, 'message' => 'User is paused'];
    }
    
    // Verify password
    if (!verify_password($password, $user['password'])) {
        log_action($app_secret, 'login_failed', $username, 'Invalid password', get_ip());
        return ['success' => false, 'message' => 'Invalid password'];
    }
    
    // Check HWID
    if ($user['hwid'] && $hwid && $user['hwid'] !== $hwid) {
        log_action($app_secret, 'hwid_mismatch', $username, "HWID mismatch: {$user['hwid']} vs $hwid", get_ip());
        return ['success' => false, 'message' => 'HWID mismatch'];
    }
    
    // Update last login
    $ip = get_ip();
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), ip = ? WHERE id = ?");
    $stmt->bind_param("si", $ip, $user['id']);
    $stmt->execute();
    
    // Update key used_at to reflect latest login
    $stmt = $conn->prepare("UPDATE `keys` SET used_at = NOW() WHERE app = ? AND used_by = ?");
    $stmt->bind_param("ss", $app_secret, $username);
    $stmt->execute();
    
    // Log action
    log_action($app_secret, 'login', $username, 'User logged in', $ip);
    
    // Get user subscriptions
    $stmt = $conn->prepare("SELECT * FROM subs WHERE app = ? AND user = ? AND expiry > ?");
    $expiry_time = time();
    $stmt->bind_param("ssi", $app_secret, $username, $expiry_time);
    $stmt->execute();
    $subs_result = $stmt->get_result();
    
    $subscriptions = [];
    while ($sub = $subs_result->fetch_assoc()) {
        $subscriptions[] = [
            'subscription' => $sub['sub_name'],
            'expiry' => $sub['expiry'],
            'timeleft' => time_conversion($sub['expiry'] - time())
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Logged in successfully',
        'info' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'hwid' => $user['hwid'],
            'ip' => $ip,
            'created_at' => strtotime($user['created_at']),
            'last_login' => time(),
            'subscriptions' => $subscriptions
        ]
    ];
}

// ===== ACTION HANDLERS =====

switch ($type_param) {
    case 'init':
        // Get client-supplied enckey (optional, max 35 chars like KeyAuth)
        $client_enckey = sanitize($input_data['enckey'] ?? $_GET['enckey'] ?? '');
        if (!empty($client_enckey) && strlen($client_enckey) > 35) {
            send_response(false, "The parameter \"enckey\" is too long. Must be 35 characters or less.", null, null);
        }
        
        // App details already fetched earlier, no need to fetch again
        
        // Version check - if mismatch, include update info but don't block
        $version_mismatch = !empty($ver_param) && !empty($app['version']) && $ver_param != $app['version'];
        
        // Combine client enckey with secret (KeyAuth style)
        $enckey = !empty($client_enckey) ? $client_enckey . "-" . $app_secret : NULL;
        
        // Create session in database
        $sessionid = bin2hex(random_bytes(16));
        $expiry = time() + 3600;
        $ip = get_ip();
        $useragent = get_user_agent();
        
        $stmt = $conn->prepare("INSERT INTO `sessions` (id, app, expiry, ip, useragent, enckey, validated) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssisss", $sessionid, $app_secret, $expiry, $ip, $useragent, $enckey);
        $stmt->execute();
        
        // Calculate stats
        $stmt = $conn->prepare("SELECT 
            (SELECT COUNT(*) FROM users WHERE app = ?) as numUsers,
            (SELECT COUNT(*) FROM `sessions` WHERE app = ? AND validated = 1 AND expiry > ?) as numOnlineUsers,
            (SELECT COUNT(*) FROM `keys` WHERE app = ? AND status = 'active') as numKeys
        ");
        $time = time();
        $stmt->bind_param("sssi", $app_secret, $app_secret, $time, $app_secret);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        // CRITICAL: Init response is signed with just $app_secret (not combined enckey)
        // This matches KeyAuth behavior - client verifies init with app secret only
        // Also flatten fields to top-level to match C# library expectations
        
        // Build appinfo
        $appinfo = [
            "numUsers" => (string)($stats['numUsers'] ?? '0'),
            "numOnlineUsers" => (string)($stats['numOnlineUsers'] ?? '0'),
            "numKeys" => (string)($stats['numKeys'] ?? '0'),
            "version" => $app['version'] ?? '1.0',
            "customerPanelLink" => $app['custom_domain'] ?? ''
        ];
        
        // Get download URL from settings
        $app_settings = !empty($app['settings']) ? json_decode($app['settings'], true) : [];
        $download_url = $app_settings['download'] ?? '';
        $web_download_url = $app_settings['webdownload'] ?? '';
        if (!empty($download_url)) {
            $appinfo['dload'] = $download_url;
        }
        if (!empty($web_download_url)) {
            $appinfo['web_download'] = $web_download_url;
        }
        
        $response_data = [
            "sessionid" => $sessionid,
            "enckey" => $enckey,
            "appinfo" => $appinfo,
            "nonce" => bin2hex(random_bytes(16))
        ];
        
        // If version mismatch, add update info without blocking
        if ($version_mismatch) {
            $response_data['appinfo']['newVersion'] = $app['version'];
            if (!empty($download_url)) {
                $response_data['appinfo']['dload'] = $download_url;
            }
        }
        
        send_response(true, "Initialized", $response_data, $app_secret, true, 'data');  // Sign with secret only, not combined enckey; flatten
        break;
    
    case 'register':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $session_enckey = $session['enckey'] ?? null;
        $username = sanitize($input_data['username'] ?? $_GET['username'] ?? '');
        $password = $input_data['pass'] ?? $_GET['pass'] ?? '';
        $key = sanitize($input_data['key'] ?? $_GET['key'] ?? '');
        $hwid = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? null);
        $email = sanitize($input_data['email'] ?? $_GET['email'] ?? null);
        
        $result = register_user($app_secret, $username, $key, $password, $hwid, $email);
        if ($result['success']) {
            $new_expiry = time() + 3600;
            $stmt = $conn->prepare("UPDATE sessions SET validated = 1, credential = ?, expiry = ? WHERE id = ? AND app = ?");
            $stmt->bind_param("siss", $username, $new_expiry, $sessionid_param, $app_secret);
            $stmt->execute();
            $response_data = ['nonce' => bin2hex(random_bytes(16))];
        } else {
            $response_data = null;
        }
        send_response($result['success'], $result['message'], $response_data, $session_enckey, true, 'info');
        break;
    
    case 'login':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $session_enckey = $session['enckey'] ?? null;
        $username = sanitize($input_data['username'] ?? $_GET['username'] ?? '');
        $password = $input_data['pass'] ?? $_GET['pass'] ?? '';
        $hwid = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? null);
        
        $result = login_user($app_secret, $username, $password, $hwid);
        if ($result['success']) {
            // Extend session expiry on successful login
            $new_expiry = time() + 3600;
            $stmt = $conn->prepare("UPDATE sessions SET validated = 1, credential = ?, expiry = ? WHERE id = ? AND app = ?");
            $stmt->bind_param("siss", $username, $new_expiry, $sessionid_param, $app_secret);
            $stmt->execute();
            $response_data = [
                'info' => $result['info'] ?? [],
                'nonce' => bin2hex(random_bytes(16))
            ];
        } else {
            $response_data = null;
        }
        send_response($result['success'], $result['message'], $response_data, $session_enckey, true, 'info');
        break;
    
    case 'license':
        try {
            // Validate license key directly (KeyAuth style)
            // Note: License validation doesn't require a session in KeyAuth
            $key = sanitize($input_data['key'] ?? $_GET['key'] ?? '');
            $hwid = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? null);

        if (empty($key)) {
            send_response(false, "License key required", null, $response_enckey);
        }
        
        // Get license key from database
        $stmt = $conn->prepare("SELECT * FROM `keys` WHERE app = ? AND license_key = ?");
        $stmt->bind_param("ss", $app_secret, $key);
        $stmt->execute();
        $key_result = $stmt->get_result();
        
        if ($key_result->num_rows === 0) {
            // Extra diagnostics: log available keys for this app
            $diagStmt = $conn->prepare("SELECT license_key, status, expiry FROM `keys` WHERE app = ? ORDER BY id DESC LIMIT 5");
            $diagStmt->bind_param("s", $app_secret);
            $diagStmt->execute();
            $diagRes = $diagStmt->get_result();
            $available = [];
            while ($row = $diagRes->fetch_assoc()) {
                $available[] = $row;
            }
            error_log('License lookup failed. app=' . substr($app_secret,0,8) . ' key=' . $key . ' available_keys=' . json_encode($available));

            log_action($app_secret, 'license_invalid', $key, "Invalid license key attempt", get_ip());
            send_response(false, "Invalid license key", null, $response_enckey);
        }
        
        $license = $key_result->fetch_assoc();
        
        // Check if key is expired
        if ($license['expiry'] > 0 && $license['expiry'] < time()) {
            log_action($app_secret, 'license_expired', $key, "Expired license key attempt", get_ip());
            send_response(false, "License key has expired", null, $response_enckey);
        }

        // Check blacklist
        if (is_blacklisted($app_secret, 'hwid', $hwid) || is_blacklisted($app_secret, 'key', $key)) {
            log_action($app_secret, 'license_blacklist', $key, "Blacklisted HWID/key attempt", get_ip());
            send_response(false, "This license or hardware is blacklisted", null, $response_enckey);
        }
        
        // Create or update user in users table for dashboard visibility
        $license_username = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
        $ip = get_ip();
        $now_time = time();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE app = ? AND username = ?");
        $stmt->bind_param("ss", $app_secret, $license_username);
        $stmt->execute();
        $existing_user = $stmt->get_result()->fetch_assoc();
        
        if ($existing_user) {
            // Check if banned
            if (intval($existing_user['banned'] ?? 0) === 1) {
                log_action($app_secret, 'license_banned', $key, 'Banned user attempted license', get_ip());
                send_response(false, 'User banned: ' . ($existing_user['ban_reason'] ?? 'Banned'), null, $response_enckey);
            }
            // Check if paused
            if (intval($existing_user['paused'] ?? 0) === 1) {
                log_action($app_secret, 'license_paused', $key, 'Paused user attempted license', get_ip());
                send_response(false, 'User is paused', null, $response_enckey);
            }
            // Check HWID binding (if key was already bound to a different HWID)
            $stored_hwid = $existing_user['hwid'] ?? '';
            if (!empty($stored_hwid) && !empty($hwid) && $stored_hwid !== $hwid) {
                log_action($app_secret, 'license_hwid_mismatch', $key, "HWID mismatch: stored=$stored_hwid vs incoming=$hwid", get_ip());
                send_response(false, 'This license is already bound to another PC', null, $response_enckey);
            }
            // Update existing user
            $stmt = $conn->prepare("UPDATE users SET hwid = ?, ip = ?, last_login = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $hwid, $ip, $existing_user['id']);
            $stmt->execute();
        } else {
            // Create new user record
            $stmt = $conn->prepare("INSERT INTO users (app, username, hwid, ip, created_at, last_login) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("ssss", $app_secret, $license_username, $hwid, $ip);
            $stmt->execute();
        }
        
        // Create or update subscription from key expiry
        $sub_expiry = intval($license['expiry'] ?? 0);
        $sub_name = 'default';
        $stmt = $conn->prepare("SELECT id FROM subs WHERE app = ? AND user = ? AND sub_name = ?");
        $stmt->bind_param("sss", $app_secret, $license_username, $sub_name);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            if ($sub_expiry > 0) {
                $stmt = $conn->prepare("UPDATE subs SET expiry = ? WHERE app = ? AND user = ? AND sub_name = ?");
                $stmt->bind_param("isss", $sub_expiry, $app_secret, $license_username, $sub_name);
                $stmt->execute();
            }
        } else {
            if ($sub_expiry > 0) {
                $stmt = $conn->prepare("INSERT INTO subs (app, user, sub_name, expiry) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $app_secret, $license_username, $sub_name, $sub_expiry);
                $stmt->execute();
            }
        }

        // Mark key as used (status stays inactive, but license() still accepts it)
        $stmt = $conn->prepare("UPDATE `keys` SET used_by = 'license_api', used_at = NOW(), status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $license['id']);
        $stmt->execute();

        // Bind key to session
        $stmt = $conn->prepare("UPDATE sessions SET validated = 1, credential = ?, expiry = ? WHERE id = ? AND app = ?");
        $new_expiry = $now_time + 3600;
        $stmt->bind_param("siss", $key, $new_expiry, $sessionid_param, $app_secret);
        $stmt->execute();
        
        // Return license info - align with C# user_data_structure field names
        $user_data = [
            'username'   => $license_username,
            'ip'         => $ip,
            'hwid'       => $hwid ?? '',
            'createdate' => (string)$now_time,
            'lastlogin'  => (string)$now_time,
            'subscriptions' => [
                [
                    'subscription' => 'Premium',
                    'expiry' => (string)$license['expiry'],
                    'timeleft' => ($license['expiry'] > 0) ? time_conversion($license['expiry'] - time()) : 'Lifetime'
                ]
            ]
        ];

        error_log('License response: ' . json_encode($user_data));
        log_action($app_secret, 'license_valid', $key, "Valid license used", $ip);
        send_response(true, "License is valid", $user_data, $response_enckey, false, 'info');  // Place under 'info' for C# client
        } catch (Exception $e) {
            error_log('License endpoint error: ' . $e->getMessage());
            send_response(false, "License validation failed: " . $e->getMessage(), null, $response_enckey);
        }
        break;
    
    case 'check':
        // Check if a user/session is still valid
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        
        if (time() > $session['expiry']) {
            send_response(false, "Session expired", null, $session['enckey']);
        }
        
        send_response(true, "Session valid", [
            'sessionid' => $session['id'],
            'nonce' => bin2hex(random_bytes(16))
        ], $session['enckey']);
        break;
    
    case 'validate':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        
        if (time() > $session['expiry']) {
            send_response(false, "Session expired", null, $session['enckey']);
        }
        
        send_response(true, "Session valid", [
            'sessionid' => $session['id'],
            'nonce' => bin2hex(random_bytes(16))
        ], $session['enckey']);
        break;
    
    case 'fetchstats':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $stmt = $conn->prepare("SELECT
            (SELECT COUNT(*) FROM users WHERE app = ?) as numUsers,
            (SELECT COUNT(*) FROM `sessions` WHERE app = ? AND validated = 1 AND expiry > ?) as numOnlineUsers,
            (SELECT COUNT(*) FROM `keys` WHERE app = ? AND status = 'active') as numKeys
        ");
        $time = time();
        $stmt->bind_param("sssi", $app_secret, $app_secret, $time, $app_secret);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        send_response(true, "Stats fetched", [
            'appinfo' => [
                'numUsers' => (string)$result['numUsers'],
                'numOnlineUsers' => (string)$result['numOnlineUsers'],
                'numKeys' => (string)$result['numKeys'],
                'version' => $app['version'] ?? '1.0'
            ],
            'nonce' => bin2hex(random_bytes(16))
        ], $session['enckey']);
        break;
    
    case 'getvar':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $varid = sanitize($_POST['varid'] ?? $_GET['varid'] ?? '');
        
        if (empty($varid)) {
            send_response(false, "Variable ID required", null, $session['enckey']);
        }
        
        $stmt = $conn->prepare("SELECT msg, authed FROM vars WHERE app = ? AND varid = ?");
        $stmt->bind_param("ss", $app_secret, $varid);
        $stmt->execute();
        $var = $stmt->get_result()->fetch_assoc();
        
        if (!$var) {
            send_response(false, "Variable not found", null, $session['enckey']);
        }
        
        send_response(true, "Variable fetched", [
            'response' => $var['msg'],
            'nonce' => bin2hex(random_bytes(16))
        ], $session['enckey']);
        break;
    
    case 'ban':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        
        if (!$session['validated']) {
            send_response(false, "Session not authenticated", null, $session['enckey']);
        }
        
        $username = $session['credential'];
        $reason = sanitize($_POST['reason'] ?? $_GET['reason'] ?? 'User banned from client');
        
        if (ban_user($app_secret, $username, $reason)) {
            send_response(true, "User banned successfully", [
                'nonce' => bin2hex(random_bytes(16))
            ], $session['enckey']);
        }
        
        send_response(false, "Failed to ban user", null, $session['enckey']);
        break;
    
    case 'checkblacklist':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $type = sanitize($_POST['type'] ?? $_GET['type'] ?? '');
        $value = sanitize($_POST['value'] ?? $_GET['value'] ?? '');
        
        if (is_blacklisted($app_secret, $type, $value)) {
            send_response(true, "Value is blacklisted", [
                'nonce' => bin2hex(random_bytes(16))
            ], $session['enckey']);
        }
        
        send_response(false, "Value is not blacklisted", [
            'nonce' => bin2hex(random_bytes(16))
        ], $session['enckey']);
        break;
    
    default:
        send_response(false, "Unknown action: $type_param", null, $response_enckey);
}

?>
