<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$_SESSION['user_id'] = 8;
$success = generateOTP('7061029304');

echo "Success? " . ($success ? "YES" : "NO") . "\n";

$res = $conn->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 2");
print_r($res->fetch_all(MYSQLI_ASSOC));

// $msg = sendSMS('7061029304', '38745', 'primary', 8);
// print_r($msg);
?>