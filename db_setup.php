<?php
/**
 * LicenseCore - Database Setup Script
 * Unified database setup (including the former db_enhanced.php schema).
 * Creates missing tables without replacing existing data.
 */

define('LICENSECORE_DATABASE_SETUP', true);
require_once __DIR__ . '/includes/config.php';
if (PHP_SAPI !== 'cli') header('Content-Type: text/plain; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {

$setup_conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

$setup_conn->set_charset('utf8mb4');
$setup_conn->query("SET time_zone = '+05:30'");
try {
    // Hosting accounts may select an existing database without CREATE privileges.
    $setup_conn->select_db(DB_NAME);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() !== 1049) throw $e;
    $database = '`' . str_replace('`', '``', DB_NAME) . '`';
    $setup_conn->query("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $setup_conn->select_db(DB_NAME);
}

$tables = [];

$tables['accounts'] = "
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(70) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `ownerid` VARCHAR(10) UNIQUE NOT NULL,
    `role` ENUM('owner', 'manager', 'developer', 'tester', 'seller', 'reseller') DEFAULT 'owner',
    `expires` BIGINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    `ip` VARCHAR(45),
    `twofactor_enabled` BOOLEAN DEFAULT 0,
    `twofactor_secret` VARCHAR(255),
    `banned` BOOLEAN DEFAULT 0,
    `ban_reason` TEXT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_ownerid (ownerid),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['apps'] = "
CREATE TABLE IF NOT EXISTS `apps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `secret` VARCHAR(64) UNIQUE NOT NULL,
    `seller_key` CHAR(32) CHARACTER SET ascii COLLATE ascii_bin UNIQUE DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `ownerid` VARCHAR(10) NOT NULL,
    `version` VARCHAR(20) DEFAULT '1.0',
    `description` TEXT,
    `icon` LONGBLOB,
    `paused` BOOLEAN DEFAULT 0,
    `custom_domain` VARCHAR(255),
    `webhook_url` VARCHAR(500),
    `settings` TEXT DEFAULT NULL,
    `hash` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `modified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ownerid) REFERENCES accounts(ownerid),
    INDEX idx_secret (secret),
    INDEX idx_ownerid (ownerid),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['subscriptions'] = "
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `level` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_sub (app, name),
    INDEX idx_app (app)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['users'] = "
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `username` VARCHAR(70) NOT NULL,
    `email` VARCHAR(255),
    `password` VARCHAR(255) NOT NULL,
    `hwid` VARCHAR(255),
    `ip` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    `banned` BOOLEAN DEFAULT 0,
    `ban_reason` TEXT,
    `paused` BOOLEAN DEFAULT 0,
    `subscription` VARCHAR(50),
    `subscription_expiry` DATETIME NULL,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_user (app, username),
    INDEX idx_app (app),
    INDEX idx_username (username),
    INDEX idx_hwid (hwid),
    INDEX idx_ip (ip),
    INDEX idx_banned (banned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Retain the enhanced install's end_users table for legacy integrations.
$tables['end_users'] = str_replace('CREATE TABLE IF NOT EXISTS `users`', 'CREATE TABLE IF NOT EXISTS `end_users`', $tables['users']);

$tables['keys'] = "
CREATE TABLE IF NOT EXISTS `keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `license_key` VARCHAR(70) UNIQUE NOT NULL,
    `owner` VARCHAR(100),
    `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    `expiry` BIGINT DEFAULT 0,
    `duration_seconds` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `max_devices` INT UNSIGNED NOT NULL DEFAULT 1,
    `note` VARCHAR(255) DEFAULT 'N/A',
    `level` INT DEFAULT 1,
    `banned` BOOLEAN DEFAULT 0,
    `ban_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(70),
    `used_by` VARCHAR(70),
    `used_at` TIMESTAMP NULL,
    `reset` BOOLEAN DEFAULT 0,
    `format` ENUM('uuid', 'random', 'alphanum', 'mask', 'imported') DEFAULT 'random',
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_license_key (license_key),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['key_devices'] = "
CREATE TABLE IF NOT EXISTS `key_devices` (
    `key_id` INT NOT NULL,
    `hwid_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (key_id, hwid_hash),
    FOREIGN KEY (key_id) REFERENCES `keys`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['sessions'] = "
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `credential` VARCHAR(255),
    `validated` BOOLEAN DEFAULT 0,
    `expiry` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip` VARCHAR(45),
    `useragent` VARCHAR(500),
    `enckey` VARCHAR(255),
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_credential (credential),
    INDEX idx_expiry (expiry),
    INDEX idx_validated (validated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['uservars'] = "
CREATE TABLE IF NOT EXISTS `uservars` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `user` VARCHAR(70) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `data` LONGTEXT,
    `readonly` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `modified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_uservar (app, user, name),
    INDEX idx_app (app),
    INDEX idx_user (user),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['vars'] = "
CREATE TABLE IF NOT EXISTS `vars` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `varid` VARCHAR(255) UNIQUE NOT NULL,
    `msg` LONGTEXT,
    `authed` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_varid (varid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['files'] = "
CREATE TABLE IF NOT EXISTS `files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `authed` BOOLEAN DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['webhooks'] = "
CREATE TABLE IF NOT EXISTS `webhooks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `webid` VARCHAR(255) UNIQUE NOT NULL,
    `baselink` VARCHAR(500) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['webhooks_logs'] = "
CREATE TABLE IF NOT EXISTS `webhooks_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `webid` VARCHAR(255) NOT NULL,
    `useragent` VARCHAR(500),
    `authed` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webid) REFERENCES webhooks(webid) ON DELETE CASCADE,
    INDEX idx_webid (webid),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['logs'] = "
CREATE TABLE IF NOT EXISTS `logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64),
    `logapp` VARCHAR(64),
    `action` VARCHAR(255),
    `user` VARCHAR(70),
    `ip` VARCHAR(45),
    `message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_user (user),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['blacklist'] = "
CREATE TABLE IF NOT EXISTS `blacklist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `type` ENUM('hwid', 'ip', 'username', 'key') NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_blacklist (app, type, value),
    INDEX idx_app (app),
    INDEX idx_type (type),
    INDEX idx_value (value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['chats'] = "
CREATE TABLE IF NOT EXISTS `chats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `delay` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_chat (app, name),
    INDEX idx_app (app)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['chat_messages'] = "
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `channel` VARCHAR(100) NOT NULL,
    `user` VARCHAR(70) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_channel (channel),
    INDEX idx_user (user),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['tokens'] = "
CREATE TABLE IF NOT EXISTS `tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `token` VARCHAR(255) UNIQUE NOT NULL,
    `type` ENUM('user', 'app', 'seller') DEFAULT 'user',
    `user` VARCHAR(70),
    `ip` VARCHAR(45),
    `valid` BOOLEAN DEFAULT 1,
    `expiry` BIGINT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_token (token),
    INDEX idx_user (user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['integrations'] = "
CREATE TABLE IF NOT EXISTS `integrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `type` ENUM('discord', 'telegram', 'email', 'slack') DEFAULT 'discord',
    `config` JSON,
    `enabled` BOOLEAN DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_integration (app, type),
    INDEX idx_app (app)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$tables['subs'] = "
CREATE TABLE IF NOT EXISTS `subs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `user` VARCHAR(70) NOT NULL,
    `sub_name` VARCHAR(50) NOT NULL,
    `expiry` BIGINT NOT NULL,
    `paused` BOOLEAN DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_user (user),
    INDEX idx_sub_name (sub_name),
    INDEX idx_expiry (expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

echo "Creating database tables...\n";
foreach ($tables as $name => $sql) {
    if ($setup_conn->query($sql) === TRUE) {
        echo "✅ Table '$name' created/exists\n";
    } else {
        echo "❌ Error creating table '$name': " . $setup_conn->error . "\n";
    }
}

// Upgrade existing installations for independently refreshable seller keys.
if ($setup_conn->query("SHOW COLUMNS FROM apps LIKE 'seller_key'")->num_rows === 0) {
    $setup_conn->query("ALTER TABLE apps ADD seller_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin UNIQUE DEFAULT NULL");
}

echo "\n✅ Database setup completed!\n";
echo "\nDatabase: " . DB_NAME . "\n";
echo "Tables created: " . count($tables) . "\n";
echo "\nNext steps:\n";
echo "1. Visit " . SITE_URL . "/register.php to create your account\n";
echo "2. Create your first application\n";
echo "3. Generate and manage licenses\n";
echo "4. Integrate with the API\n";

$setup_conn->close();
} catch (mysqli_sql_exception $e) {
    if (PHP_SAPI !== 'cli') http_response_code(500);
    echo "\nDatabase setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
