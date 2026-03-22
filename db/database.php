<?php 
/**
 * Database Initialization Script
 * This script creates the necessary tables and inserts default data.
 */

// Include database configuration
require_once(__DIR__ . "/../config/database.php");

// Check if connection was successful
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Connection object not found"));
}

// Function for styled output
function log_msg($msg, $type = 'info') {
    $is_cli = (php_sapi_name() === 'cli');
    $colors = [
        'info' => $is_cli ? "\e[34m[INFO]\e[0m " : "<span style='color:blue'>[INFO]</span> ",
        'success' => $is_cli ? "\e[32m[SUCCESS]\e[0m " : "<span style='color:green'>[SUCCESS]</span> ",
        'error' => $is_cli ? "\e[31m[ERROR]\e[0m " : "<span style='color:red'>[ERROR]</span> ",
    ];
    $newline = $is_cli ? "\n" : "<br>";
    echo ($colors[$type] ?? "") . $msg . $newline;
}

// Array of table creation queries
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'api_user') NOT NULL DEFAULT 'api_user',
        api_key VARCHAR(64) UNIQUE,
        api_secret VARCHAR(64),
        status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
        sms_balance INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "api_settings" => "CREATE TABLE IF NOT EXISTS api_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "sms_logs" => "CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        phone VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        provider VARCHAR(50) NOT NULL,
        provider_name VARCHAR(100) NULL,
        status TINYINT(1) DEFAULT 0,
        response TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    
    "otp_codes" => "CREATE TABLE IF NOT EXISTS otp_codes (
        id VARCHAR(50) PRIMARY KEY,
        user_id INT NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        code VARCHAR(10) NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        verified_at TIMESTAMP NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "otp_verification_attempts" => "CREATE TABLE IF NOT EXISTS otp_verification_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        otp_code VARCHAR(10) NOT NULL,
        ip_address VARCHAR(45),
        success TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "sms_pricing" => "CREATE TABLE IF NOT EXISTS sms_pricing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        sms_count INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        validity_days INT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "subscriptions" => "CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pricing_id INT NOT NULL,
        start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        end_date TIMESTAMP NULL,
        status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
        payment_reference VARCHAR(255),
        amount_paid DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (pricing_id) REFERENCES sms_pricing(id) ON DELETE RESTRICT
    )",
    
    "payment_gateways" => "CREATE TABLE IF NOT EXISTS payment_gateways (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        api_key VARCHAR(255),
        api_secret VARCHAR(255),
        webhook_url VARCHAR(255),
        is_active TINYINT(1) DEFAULT 0,
        is_backup TINYINT(1) DEFAULT 0,
        success_rate DECIMAL(5, 2) DEFAULT 0,
        priority INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "api_providers" => "CREATE TABLE IF NOT EXISTS api_providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        api_url VARCHAR(255) NOT NULL,
        api_key VARCHAR(255),
        api_secret VARCHAR(255),
        method ENUM('GET', 'POST', 'POST_JSON') DEFAULT 'GET',
        headers TEXT NULL,
        post_data TEXT NULL,
        success_keyword VARCHAR(255) NULL,
        is_active TINYINT(1) DEFAULT 0,
        is_backup TINYINT(1) DEFAULT 0,
        priority INT DEFAULT 0,
        success_rate DECIMAL(5, 2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "sms_api_logs" => "CREATE TABLE IF NOT EXISTS sms_api_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        phone VARCHAR(15) NOT NULL,
        message TEXT NOT NULL,
        provider VARCHAR(100) NOT NULL,
        status TINYINT(1) NOT NULL,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "transactions" => "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subscription_id INT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        transaction_reference VARCHAR(255) NOT NULL,
        status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        description TEXT,
        metadata TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
    )",
    
    "sdk_downloads" => "CREATE TABLE IF NOT EXISTS `sdk_downloads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `sdk_type` varchar(50) NOT NULL,
        `downloaded_at` datetime NOT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_sdk_downloads_user_id` (`user_id`),
        CONSTRAINT `fk_sdk_downloads_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

log_msg("Starting Database Initialization...");

// Ensure columns exist on already created tables
$alter_queries = [
    "ALTER TABLE sms_logs ADD COLUMN IF NOT EXISTS provider_name VARCHAR(100) NULL AFTER provider",
    "ALTER TABLE sms_logs ADD COLUMN IF NOT EXISTS sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER response",
    "ALTER TABLE sms_logs MODIFY COLUMN status TINYINT(1) DEFAULT 0",
    "ALTER TABLE api_providers ADD COLUMN IF NOT EXISTS method ENUM('GET', 'POST', 'POST_JSON') DEFAULT 'GET' AFTER api_secret",
    "ALTER TABLE api_providers ADD COLUMN IF NOT EXISTS headers TEXT NULL AFTER method",
    "ALTER TABLE api_providers ADD COLUMN IF NOT EXISTS post_data TEXT NULL AFTER headers",
    "ALTER TABLE api_providers ADD COLUMN IF NOT EXISTS success_keyword VARCHAR(255) NULL AFTER post_data",
    "ALTER TABLE sms_api_logs CHANGE COLUMN success status TINYINT(1) NOT NULL"
];

foreach ($alter_queries as $q) {
    try {
        $conn->query($q);
    } catch (Exception $e) {
        // Ignore column duplicates/exists errors during alter
    }
}

// Execute table creation
$success_count = 0;
foreach ($tables as $name => $query) {
    if ($conn->query($query)) {
        log_msg("Table '$name' verified.", 'success');
        $success_count++;
    } else {
        log_msg("Error creating table '$name': " . $conn->error, 'error');
    }
}

// Default Data (Seeding)
$admin_pass = md5('admin123');
$user_pass = md5('user123');

$seeds = [
    "REPLACE INTO users (username, password, email, full_name, role, status) 
     VALUES ('admin', '$admin_pass', 'admin@example.com', 'Admin User', 'admin', 'active')",
     
    "REPLACE INTO users (username, password, email, full_name, role, api_key, status, sms_balance) 
     VALUES ('apiuser', '$user_pass', 'apiuser@example.com', 'API User', 'api_user', '1234567890abcdef1234567890abcdef', 'active', 1000)",
     
    "REPLACE INTO api_settings (name, value) VALUES 
    ('active_provider', 'primary'),
    ('auto_failover', '1'),
    ('max_sms_per_request', '100'),
    ('otp_expiry_minutes', '10')",
    
    "REPLACE INTO sms_pricing (name, sms_count, price, description) VALUES
    ('Basic', 1000, 29.99, 'Basic package with 1000 SMS messages'),
    ('Standard', 5000, 99.99, 'Standard package with 5000 SMS messages'),
    ('Premium', 10000, 179.99, 'Premium package with 10000 SMS messages'),
    ('Enterprise', 50000, 499.99, 'Enterprise package with 50000 SMS messages')",
    
    "REPLACE INTO api_providers (id, name, api_url, api_key, method, success_keyword, is_active, is_backup, priority) VALUES
    (1, 'APIHome', 'https://sms-api.amanprojects.com/panel/api/bulksms/?key={api_key}&mobile={mobile}&otp={message_encoded}', 'YOUR API KEY', 'GET', '\"status\":\"Success\"', 1, 0, 1),
    (2, 'Backup Provider', 'https://api.backupsms.com/v1/send', 'backup_api_key', 'GET', '', 1, 1, 2)"
];

log_msg("Seeding default data...");
foreach ($seeds as $index => $query) {
    if ($conn->query($query)) {
        log_msg("Seed data #$index inserted/updated.", 'success');
    } else {
        log_msg("Error in seed #$index: " . $conn->error, 'error');
    }
}

// Close connection
$conn->close();
log_msg("Database setup completed successfully! $success_count tables verified.");
?>