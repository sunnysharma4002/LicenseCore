<?php
// ===== DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'licenseauth');

// ===== SITE CONFIGURATION =====
define('SITE_URL', 'http://localhost/LicenseCore');
define('SITE_NAME', 'LicenseCore');
define('SITE_VERSION', '2.0');

// ===== SECURITY CONFIGURATION =====
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);
define('PASSWORD_MIN_LENGTH', 6);
define('ENCRYPTION_KEY', 'Lic3ns3C0r3S3cur3K3y2024!@#$%^&*()');

// ===== API CONFIGURATION =====
define('API_VERSION', '1.2');
define('RATE_LIMIT_REQUESTS', 200);
define('RATE_LIMIT_WINDOW', 60);

// ===== CREATE DATABASE CONNECTION =====
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== SECURITY HEADERS =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// ===== HELPER FUNCTIONS =====

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['username']) && !empty($_SESSION['username']);
    }
}

if (!function_exists('get_current_user_data')) {
    function get_current_user_data() {
        global $conn;
        if (is_logged_in()) {
            $username = $_SESSION['username'];
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }
        return null;
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            header("Location: " . SITE_URL . "/login/");
            exit();
        }
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generate_random')) {
    function generate_random($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('generate_license_key')) {
    function generate_license_key($format = 'random') {
        switch ($format) {
            case 'uuid':
                return sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            case 'alphanum':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $key = '';
                for ($i = 0; $i < 32; $i++) {
                    if ($i > 0 && $i % 8 == 0) $key .= '-';
                    $key .= $chars[random_int(0, strlen($chars) - 1)];
                }
                return $key;
            default:
                return generate_random(32);
        }
    }
}

if (!function_exists('generate_owner_id')) {
    function generate_owner_id() {
        global $conn;
        do {
            $id = strtoupper(substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 10)), 0, 10));
            $stmt = $conn->prepare("SELECT id FROM accounts WHERE ownerid = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $id;
    }
}

if (!function_exists('generate_app_secret')) {
    function generate_app_secret() {
        global $conn;
        do {
            $secret = generate_random(64);
            $stmt = $conn->prepare("SELECT id FROM apps WHERE secret = ?");
            $stmt->bind_param("s", $secret);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $secret;
    }
}

if (!function_exists('generate_slug')) {
    function generate_slug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('get_ip')) {
    function get_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($ip === '::1') $ip = '127.0.0.1';
        if (!preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/', $ip)) {
            return $ip;
        }
        $cache_file = sys_get_temp_dir() . '/public_ip_cache.txt';
        $cache_ttl = 300;
        if (is_file($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
            $public_ip = trim(file_get_contents($cache_file));
            if (filter_var($public_ip, FILTER_VALIDATE_IP)) return $public_ip;
        }
        $services = ['https://api.ipify.org', 'https://icanhazip.com', 'https://checkip.amazonaws.com'];
        foreach ($services as $url) {
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $public_ip = @file_get_contents($url, false, $ctx);
            if ($public_ip) {
                $public_ip = trim($public_ip);
                if (filter_var($public_ip, FILTER_VALIDATE_IP)) {
                    file_put_contents($cache_file, $public_ip);
                    return $public_ip;
                }
            }
        }
        return $ip;
    }
}

if (!function_exists('get_user_agent')) {
    function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}

if (!function_exists('send_json')) {
    function send_json($success, $message, $data = null) {
        header('Content-Type: application/json');
        $response = ['success' => $success, 'message' => $message];
        if ($data !== null) $response['data'] = $data;
        die(json_encode($response));
    }
}

if (!function_exists('check_rate_limit')) {
    function check_rate_limit($identifier, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
        if (!function_exists('apcu_fetch')) return true;
        $key = 'rate_limit:' . $identifier;
        $current = apcu_fetch($key);
        if ($current === false) { apcu_store($key, 1, $window); return true; }
        if ($current >= $limit) return false;
        apcu_inc($key);
        return true;
    }
}

if (!function_exists('log_action')) {
    function log_action($app, $action, $user = null, $message = '', $ip = null) {
        global $conn;
        $ip = $ip ?? get_ip();
        $user = $user ?? ($_SESSION['username'] ?? 'system');
        $stmt = $conn->prepare("INSERT INTO logs (app, action, user, ip, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $app, $action, $user, $ip, $message);
        return $stmt->execute();
    }
}

if (!function_exists('encrypt_data')) {
    function encrypt_data($data, $key = ENCRYPTION_KEY) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
}

if (!function_exists('decrypt_data')) {
    function decrypt_data($data, $key = ENCRYPTION_KEY) {
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
    }
}

if (!function_exists('get_hwid')) {
    function get_hwid($hwid = null) {
        if ($hwid === null) $hwid = $_POST['hwid'] ?? $_GET['hwid'] ?? null;
        return $hwid ? sanitize($hwid) : null;
    }
}

if (!function_exists('ban_user')) {
    function ban_user($app, $username, $reason = 'User banned') {
        global $conn;
        $stmt = $conn->prepare("UPDATE users SET banned = 1, ban_reason = ? WHERE app = ? AND username = ?");
        $stmt->bind_param("sss", $reason, $app, $username);
        return $stmt->execute();
    }
}

if (!function_exists('unban_user')) {
    function unban_user($app, $username) {
        global $conn;
        $stmt = $conn->prepare("UPDATE users SET banned = 0, ban_reason = NULL WHERE app = ? AND username = ?");
        $stmt->bind_param("ss", $app, $username);
        return $stmt->execute();
    }
}

if (!function_exists('is_blacklisted')) {
    function is_blacklisted($app, $type, $value) {
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM blacklist WHERE app = ? AND type = ? AND value = ?");
        $stmt->bind_param("sss", $app, $type, $value);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}

if (!function_exists('add_blacklist')) {
    function add_blacklist($app, $type, $value, $reason = '') {
        global $conn;
        $stmt = $conn->prepare("INSERT IGNORE INTO blacklist (app, type, value, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $app, $type, $value, $reason);
        return $stmt->execute();
    }
}

if (!function_exists('generate_session_id')) {
    function generate_session_id($app, $ip) {
        global $conn;
        do {
            $sessionid = generate_random(32);
            $stmt = $conn->prepare("SELECT id FROM sessions WHERE id = ?");
            $stmt->bind_param("s", $sessionid);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $sessionid;
    }
}

if (!function_exists('get_session')) {
    function get_session($sessionid, $app) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ? AND app = ?");
        $stmt->bind_param("ss", $sessionid, $app);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}

if (!function_exists('time_conversion')) {
    function time_conversion($seconds) {
        if ($seconds <= 0) return "Expired";
        $units = ['year' => 31536000, 'month' => 2592000, 'day' => 86400, 'hour' => 3600, 'minute' => 60];
        foreach ($units as $unit => $value) {
            if ($seconds >= $value) {
                $time = round($seconds / $value);
                return $time . ' ' . $unit . ($time > 1 ? 's' : '');
            }
        }
        return 'Just now';
    }
}
