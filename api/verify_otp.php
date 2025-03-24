<?php
// Set headers to allow cross-origin requests and define JSON as response type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-Key");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error_code" => "method_not_allowed",
        "error_message" => "Only POST method is allowed"
    ]);
    exit;
}

// Include necessary files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Get the API key from header or request parameters
$api_key = null;
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $api_key = $_SERVER['HTTP_X_API_KEY'];
} elseif (isset($_GET['api_key'])) {
    $api_key = $_GET['api_key'];
}

// Get request body
$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => "invalid_json",
        "error_message" => "Invalid JSON in request body"
    ]);
    exit;
}

// Add API key from request body if not found in header or URL
if (!$api_key && isset($data['api_key'])) {
    $api_key = $data['api_key'];
}

// Validate API key
if (!$api_key) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error_code" => "authentication_failed",
        "error_message" => "API key is required"
    ]);
    exit;
}

// Check if API key exists and is active
$stmt = $conn->prepare("SELECT id FROM users WHERE api_key = ? AND status = 'active'");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error_code" => "authentication_failed",
        "error_message" => "Invalid or inactive API key"
    ]);
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['id'];

// Validate required fields
if (!isset($data['phone']) || !isset($data['otp'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => "invalid_parameter",
        "error_message" => "Phone number and OTP code are required"
    ]);
    exit;
}

$phone = sanitizePhoneNumber($data['phone']);
$otp = $data['otp'];
$reference_id = isset($data['reference_id']) ? $data['reference_id'] : null;

// Validate phone number format
if (!validatePhoneNumber($phone)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => "invalid_phone",
        "error_message" => "Invalid phone number format. Use international format (e.g., +1234567890)"
    ]);
    exit;
}

// Validate OTP format (should be numeric and of the right length)
if (!ctype_digit($otp) || strlen($otp) != 6) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => "invalid_otp",
        "error_message" => "Invalid OTP format. OTP should be a 6-digit number."
    ]);
    exit;
}

// Check for excessive failed attempts from this IP
$ip_address = $_SERVER['REMOTE_ADDR'];
$attempts_query = "SELECT COUNT(*) as count FROM otp_verification_attempts 
                  WHERE ip_address = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$attempts_stmt = $conn->prepare($attempts_query);
$attempts_stmt->bind_param("s", $ip_address);
$attempts_stmt->execute();
$attempts_result = $attempts_stmt->get_result();
$attempts_row = $attempts_result->fetch_assoc();

if ($attempts_row['count'] >= 10) {
    http_response_code(429);
    echo json_encode([
        "success" => false,
        "error_code" => "too_many_attempts",
        "error_message" => "Too many failed verification attempts. Please try again later."
    ]);
    exit;
}

// Find the OTP in the database
$otp_query = "SELECT * FROM otp_codes WHERE phone_number = ? AND code = ? AND expires_at > NOW() AND user_id = ?";
if ($reference_id) {
    $otp_query = "SELECT * FROM otp_codes WHERE id = ? AND phone_number = ? AND code = ? AND expires_at > NOW() AND user_id = ?";
}

if ($reference_id) {
    $otp_stmt = $conn->prepare($otp_query);
    $otp_stmt->bind_param("sssi", $reference_id, $phone, $otp, $user_id);
} else {
    $otp_stmt = $conn->prepare($otp_query);
    $otp_stmt->bind_param("ssi", $phone, $otp, $user_id);
}

$otp_stmt->execute();
$otp_result = $otp_stmt->get_result();

// Record the verification attempt
$attempt_insert = "INSERT INTO otp_verification_attempts (user_id, phone_number, otp_code, ip_address, success, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
$attempt_stmt = $conn->prepare($attempt_insert);

if ($otp_result->num_rows > 0) {
    // OTP is valid, mark the attempt as successful
    $success = 1;
    $attempt_stmt->bind_param("isssi", $user_id, $phone, $otp, $ip_address, $success);
    $attempt_stmt->execute();
    
    // Get the OTP record
    $otp_record = $otp_result->fetch_assoc();
    
    // Mark OTP as used 
    $update_stmt = $conn->prepare("UPDATE otp_codes SET verified = 1, verified_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("s", $otp_record['id']);
    $update_stmt->execute();
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "OTP verified successfully",
        "phone_number" => $phone
    ]);
} else {
    // OTP is invalid, mark the attempt as failed
    $success = 0;
    $attempt_stmt->bind_param("isssi", $user_id, $phone, $otp, $ip_address, $success);
    $attempt_stmt->execute();
    
    // Check if OTP exists but is expired
    $expired_query = "SELECT * FROM otp_codes WHERE phone_number = ? AND code = ? AND expires_at <= NOW() AND user_id = ?";
    $expired_stmt = $conn->prepare($expired_query);
    $expired_stmt->bind_param("ssi", $phone, $otp, $user_id);
    $expired_stmt->execute();
    $expired_result = $expired_stmt->get_result();
    
    if ($expired_result->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error_code" => "expired_otp",
            "error_message" => "OTP has expired. Please request a new one."
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error_code" => "invalid_otp",
            "error_message" => "Invalid OTP. Please check and try again."
        ]);
    }
}

/**
 * Sanitize phone number by removing non-numeric characters except leading +
 */
function sanitizePhoneNumber($phone) {
    // Keep + at the beginning if it exists
    $hasPlus = substr($phone, 0, 1) === '+';
    
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Add + back if it was there
    if ($hasPlus) {
        $phone = '+' . $phone;
    }
    
    return $phone;
}

/**
 * Validate phone number format
 */
function validatePhoneNumber($phone) {
    // Basic validation - should start with + followed by at least 8 digits
    return preg_match('/^\+[0-9]{8,15}$/', $phone);
} 