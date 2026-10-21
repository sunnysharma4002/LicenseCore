<?php
/**
 * Enhanced License Authentication System
 * Based on KeyAuth Architecture with Advanced Features
 * Database Setup Script
 */

$conn = new mysqli('localhost', 'root', '', 'licenseauth');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = [];

// Users/Accounts Table
$tables['accounts'] = "
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(70) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `ownerid` VARCHAR(10) UNIQUE NOT NULL,
    `role` ENUM('owner', 'manager', 'developer', 'tester', 'reseller') DEFAULT 'owner',
    `expires` BIGINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
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

// Applications Table
$tables['apps'] = "
CREATE TABLE IF NOT EXISTS `apps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `secret` VARCHAR(64) UNIQUE NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `ownerid` VARCHAR(10) NOT NULL,
    `version` VARCHAR(20) DEFAULT '1.0',
    `description` TEXT,
    `icon` LONGBLOB,
    `paused` BOOLEAN DEFAULT 0,
    `custom_domain` VARCHAR(255),
    `webhook_url` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `modified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ownerid) REFERENCES accounts(ownerid),
    INDEX idx_secret (secret),
    INDEX idx_ownerid (ownerid),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Subscriptions/Plans Table
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

// End Users Table
$tables['end_users'] = "
CREATE TABLE IF NOT EXISTS `end_users` (
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
    `subscription` VARCHAR(50),
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    UNIQUE KEY unique_user (app, username),
    INDEX idx_app (app),
    INDEX idx_username (username),
    INDEX idx_hwid (hwid),
    INDEX idx_ip (ip),
    INDEX idx_banned (banned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Licenses/Keys Table
$tables['keys'] = "
CREATE TABLE IF NOT EXISTS `keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `license_key` VARCHAR(70) UNIQUE NOT NULL,
    `owner` VARCHAR(100),
    `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    `expiry` BIGINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_by` VARCHAR(70),
    `used_by` VARCHAR(70),
    `used_at` TIMESTAMP NULL,
    `reset` BOOLEAN DEFAULT 0,
    `format` ENUM('uuid', 'random', 'alphanum') DEFAULT 'random',
    FOREIGN KEY (app) REFERENCES apps(secret) ON DELETE CASCADE,
    INDEX idx_app (app),
    INDEX idx_license_key (license_key),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Sessions Table
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

// User Variables Table
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

// App Variables Table
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

// Files Table
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

// Webhooks Table
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

// Webhook Logs Table
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

// Logs/Audit Logs Table
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

// Blacklist Table (Bans by HWID, IP, etc)
$tables['blacklist'] = "
CREATE TABLE IF NOT EXISTS `blacklist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `app` VARCHAR(64) NOT NULL,
    `type` ENUM('hwid', 'ip', 'username') NOT NULL,
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

// Chat Channels Table
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

// Chat Messages Table
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

// Tokens Table (API Tokens)
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

// Integrations Table
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

// Subs (Subscriptions for Users) Table
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

// Create all tables
echo "Creating database tables...\n";
foreach ($tables as $name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Table '$name' created/exists\n";
    } else {
        echo "❌ Error creating table '$name': " . $conn->error . "\n";
    }
}

echo "\n✅ Database setup completed!\n";
echo "\nDatabase: licenseauth\n";
echo "Tables created: " . count($tables) . "\n";
echo "\nNext steps:\n";
echo "1. Visit http://localhost/licenceauth/register.php to create your account\n";
echo "2. Create your first application\n";
echo "3. Generate and manage licenses\n";
echo "4. Integrate with the API\n";

$conn->close();
?>
