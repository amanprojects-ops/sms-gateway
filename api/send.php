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
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

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
$stmt = $conn->prepare("SELECT id, sms_balance,api_key,status FROM users WHERE api_key = ? AND status = 'active'");
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
$sms_balance = $user['sms_balance'];

// Validate required fields
if (empty($data['mobile']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => "invalid_parameter",
        "error_message" => "Phone number and message are required"
    ]);
    exit;
}

$mobile = formatPhone($data['mobile']);
$message = $data['message'];

// Validate phone number format
if (!formatPhone($mobile)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error_code" => "invalid_phone",
        "error_message" => "Invalid phone number format. Use international format (e.g., +91234567890)"
    ]);
    exit;
}

// Check SMS balance
if ($sms_balance <= 100) {
    http_response_code(402);
    echo json_encode([
        "success" => false,
        "error_code" => "insufficient_balance",
        "error_message" => "Insufficient SMS balance"
    ]);
    exit;
}

// Calculate number of SMS parts needed
$sms_parts = calculateSMSParts($message);
if ($sms_parts > $sms_balance) {
    http_response_code(402);
    echo json_encode([
        "success" => false,
        "error_code" => "insufficient_balance",
        "error_message" => "Message requires $sms_parts SMS credits but only $sms_balance available"
    ]);
    exit;
}

if ($user['api_key'] == $api_key) {

    // Send SMS
    if ($user['status'] == 'active') {

        $response = sendSMS($mobile, $message,'primary',$user['id']);
        if ($response['success']) {
            updateLastSMSTime($user['id'], $mobile); // Function to update the last SMS time for the number
            // Deduct SMS balance
            $new_balance = $user['sms_balance'] - 1; // Assuming each SMS costs 1 credit
            updateUserSMSBalance($user['id'], $new_balance); // Function to update the user's SMS balance
        }
        echo json_encode($response);
    } else {
        echo json_encode([
            "success" => false,
            "error_code" => "authentication_failed",
            "error_message" => "Invalid or inactive API key"
        ]);
    }
}
