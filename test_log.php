<?php
require_once 'config/database.php';

echo "Latest OTP Code:\n";
$res = $conn->query("SELECT * FROM otp_codes ORDER BY created_at DESC LIMIT 1");
print_r($res->fetch_assoc());

echo "\nLatest SMS Log:\n";
$res = $conn->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 1");
print_r($res->fetch_assoc());

echo "\nLatest SMS API Log:\n";
$res = $conn->query("SELECT * FROM sms_api_logs ORDER BY id DESC LIMIT 1");
print_r($res->fetch_assoc());

echo "\nAPI Providers:\n";
$res = $conn->query("SELECT * FROM api_providers");
print_r($res->fetch_all(MYSQLI_ASSOC));
?>
