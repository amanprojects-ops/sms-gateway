-- SMS Gateway - Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS sms_gateway;
USE sms_gateway;

-- Users table
CREATE TABLE IF NOT EXISTS users (
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
);

-- API Settings table
CREATE TABLE IF NOT EXISTS api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- SMS Logs table
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    success TINYINT(1) DEFAULT 0,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- OTP table
CREATE TABLE IF NOT EXISTS otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SMS Pricing table
CREATE TABLE IF NOT EXISTS sms_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sms_count INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    validity_days INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- API User Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
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
);

-- Payment Gateways table
CREATE TABLE IF NOT EXISTS payment_gateways (
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
);


-- API Provider table
CREATE TABLE IF NOT EXISTS api_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    api_url VARCHAR(255) NOT NULL,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    is_active TINYINT(1) DEFAULT 0,
    is_backup TINYINT(1) DEFAULT 0,
    priority INT DEFAULT 0,
    success_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Provider API log table
CREATE TABLE IF NOT EXISTS sms_api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(15) NOT NULL,
    message TEXT NOT NULL,
    provider VARCHAR(100) NOT NULL,
    success TINYINT(1) NOT NULL,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions table to track payment history
CREATE TABLE IF NOT EXISTS transactions (
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
);

-- Create sdk_downloads table
CREATE TABLE `sdk_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sdk_type` varchar(50) NOT NULL COMMENT 'php, node, python, java, etc.',
  `downloaded_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sdk_downloads_user_id` (`user_id`),
  CONSTRAINT `fk_sdk_downloads_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role, api_key, status) 
VALUES ('admin', '$2y$10$3fJXs0LF89JknVh.O/Ib4eIUVPGiM8xqW8ZuoRvOQQKrf.9OXbL1O', 'admin@example.com', 'Admin User', 'admin', NULL, 'active');

-- Insert default API user (password: user123)
INSERT INTO users (username, password, email, full_name, role, api_key, status, sms_balance) 
VALUES ('apiuser', '$2y$10$QCYf1X1CtwlXCO5YMGKAo.cLYIWxMxzRnSFCpIcA1dNvHRgowXNey', 'apiuser@example.com', 'API User', 'api_user', '1234567890abcdef1234567890abcdef', 'active', 1000);

-- Insert default API settings
INSERT INTO api_settings (name, value) VALUES 
('active_provider', 'primary'),
('auto_failover', '1'),
('max_sms_per_request', '100'),
('otp_expiry_minutes', '10');

-- Insert SMS pricing tiers
INSERT INTO sms_pricing (name, sms_count, price, description) VALUES
('Basic', 1000, 29.99, 'Basic package with 1000 SMS messages'),
('Standard', 5000, 99.99, 'Standard package with 5000 SMS messages'),
('Premium', 10000, 179.99, 'Premium package with 10000 SMS messages'),
('Enterprise', 50000, 499.99, 'Enterprise package with 50000 SMS messages');

-- Insert API providers
INSERT INTO api_providers (name, api_url, api_key, api_secret, is_active, is_backup, priority) VALUES
('Primary Provider', 'https://api.primarysms.com/v1/send', 'primary_api_key', 'primary_api_secret', 1, 0, 1),
('Backup Provider', 'https://api.backupsms.com/v1/send', 'backup_api_key', 'backup_api_secret', 1, 1, 2); 