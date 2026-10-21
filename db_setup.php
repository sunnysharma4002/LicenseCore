<?php
/**
 * LicenseAuth - Database Setup Script
 * Creates all necessary tables for license management system
 */

require_once __DIR__ . '/includes/config.php';

$setup_conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($setup_conn->connect_error) {
    die("Connection failed: " . $setup_conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($setup_conn->query($sql) === TRUE) {
    echo "✓ Database created successfully<br>";
} else {
    echo "✗ Error creating database: " . $setup_conn->error . "<br>";
}

$setup_conn->select_db(DB_NAME);

$tables = [];

$tables['accounts'] = "
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(70) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `ownerid` VARCHAR(10) UNIQUE NOT NULL,
    `role` ENUM('owner', 'manager', 'developer', 'tester', 'seller') DEFAULT 'owner',
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

echo "\n✅ Database setup completed!\n";
echo "\nDatabase: " . DB_NAME . "\n";
echo "Tables created: " . count($tables) . "\n";

$setup_conn->close();
