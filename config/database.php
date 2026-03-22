<?php

/**
 * Database Configuration
 */
date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sms_gateway_v1');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Set database timezone
$conn->query("SET time_zone = '+05:30'");

// Set base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$base_dir = str_replace('\\', '/', dirname($script_name));
$base_dir = rtrim($base_dir, '/');

// If base_dir is somehow just the script name or empty, handle it
if (substr($base_dir, -4) === '.php') {
    $base_dir = '';
}

define('BASE_URL', $protocol . '://' . $host . $base_dir);
define('API_BASE_URL', BASE_URL . '/api');
?>
