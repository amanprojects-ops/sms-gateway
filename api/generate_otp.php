<?php
// Set headers to allow cross-origin requests and define JSON as response type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With, X-API-Key");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include necessary files
require_once '../config/database.php';
require_once '../includes/functions.php';

/**
 * Main function to handle OTP generation and sending
 */
function handleOTPRequest() {
    global $conn;
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return sendErrorResponse(405, "method_not_allowed", "Only POST method is allowed");
    }
    
    // Get and validate API key
    $api_key = getApiKey();
    if (!$api_key) {
        return sendErrorResponse(401, "authentication_failed", "API key is required");
    }
    
    // Authenticate user
    $user = authenticateUser($api_key);
    if (!$user) {
        return sendErrorResponse(401, "authentication_failed", "Invalid or inactive API key");
    }
    
    // Get request data
    $data = getRequestData();
    if (!$data) {
        return sendErrorResponse(400, "invalid_request", "Invalid request data");
    }
    
    // Validate phone number
    if (!isset($data['phone'])) {
        return sendErrorResponse(400, "invalid_parameter", "Phone number is required");
    }
    
    $phone = sanitizePhoneNumber($data['phone']);
    if (!validatePhoneNumber($phone)) {
        return sendErrorResponse(400, "invalid_phone", "Invalid phone number format. Use international format (e.g., +1234567890)");
    }
    
    // Get template
    $template = isset($data['template']) ? $data['template'] : "{otp}";
    
    // Check SMS balance
    if ($user['sms_balance'] <= 0) {
        return sendErrorResponse(402, "insufficient_balance", "Insufficient SMS balance");
    }
    
    // Check rate limiting
    if (isRateLimited($phone)) {
        return sendErrorResponse(429, "rate_limit_exceeded", "Too many OTP requests for this number. Please try again later.");
    }
    
    // Generate and store OTP
    $otpData = generateAndStoreOTP($user['id'], $phone);
    if (!$otpData) {
        return sendErrorResponse(500, "server_error", "Failed to generate OTP: " . $conn->error);
    }
    
    // Prepare message
    $message = str_replace('{otp}', $otpData['otp'], $template);
    $sms_parts = calculateSMSParts($message);
    
    // Check if user has enough balance for this message
    if ($sms_parts > $user['sms_balance']) {
        // Delete the OTP as it cannot be sent
        deleteOTP($otpData['reference_id']);
        return sendErrorResponse(402, "insufficient_balance", 
            "Message requires $sms_parts SMS credits but only {$user['sms_balance']} available");
    }
    
    // Log SMS
    $message_id = logSMS($user['id'], $phone, $message, $otpData['reference_id'], $sms_parts);
    if (!$message_id) {
        deleteOTP($otpData['reference_id']);
        return sendErrorResponse(500, "server_error", "Failed to log SMS: " . $conn->error);
    }
    
    // Get SMS provider
    $provider = getActiveProvider();
    if (!$provider) {
        updateSMSLog($message_id, 'failed', null, null, 'No active SMS providers available');
        deleteOTP($otpData['reference_id']);
        return sendErrorResponse(503, "service_unavailable", "SMS service temporarily unavailable");
    }
    
    // Try to send SMS
    try {
        $result = sendSMS($provider, $phone, $message, $message_id);
        
        if ($result['success']) {
            // Update SMS log and deduct balance
            updateSMSLog($message_id, 'delivered', $provider['name'], $result['provider_message_id'], $result['response_json']);
            updateUserBalance($user['id'], $user['sms_balance'] - $sms_parts);
            
            // Return success response
            return sendSuccessResponse([
                "message" => "OTP sent successfully",
                "reference_id" => $otpData['reference_id'],
                "expires_in" => 600 // 10 minutes in seconds
            ]);
        } else {
            // Try fallback provider
            $fallback_provider = getFallbackProvider($provider['id']);
            
            if ($fallback_provider) {
                $fallback_result = sendSMS($fallback_provider, $phone, $message, $message_id);
                
                if ($fallback_result['success']) {
                    // Update SMS log and deduct balance
                    updateSMSLog($message_id, 'delivered', $fallback_provider['name'], 
                        $fallback_result['provider_message_id'], $fallback_result['response_json']);
                    updateUserBalance($user['id'], $user['sms_balance'] - $sms_parts);
                    
                    // Return success response
                    return sendSuccessResponse([
                        "message" => "OTP sent successfully using fallback provider",
                        "reference_id" => $otpData['reference_id'],
                        "expires_in" => 600 // 10 minutes in seconds
                    ]);
                }
            }
            
            // Both providers failed or no fallback available
            deleteOTP($otpData['reference_id']);
            $error_message = "Provider failed: " . $result['error'];
            if (isset($fallback_result)) {
                $error_message = "Primary and fallback providers failed: " . $result['error'] . ", " . $fallback_result['error'];
            }
            
            updateSMSLog($message_id, 'failed', null, null, $error_message);
            return sendErrorResponse(500, "sending_failed", 
                "Failed to send OTP" . (isset($fallback_result) ? " through available providers" : " and no fallback provider available"));
        }
    } catch (Exception $e) {
        // Handle exceptions
        deleteOTP($otpData['reference_id']);
        updateSMSLog($message_id, 'failed', null, null, "Exception: " . $e->getMessage());
        return sendErrorResponse(500, "server_error", "Internal server error: " . $e->getMessage());
    }
}

/**
 * Get API key from various sources
 */
function getApiKey() {
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        return $_SERVER['HTTP_X_API_KEY'];
    } elseif (isset($_GET['api_key'])) {
        return $_GET['api_key'];
    }
    
    $data = getRequestData();
    if ($data && isset($data['api_key'])) {
        return $data['api_key'];
    }
    
    return null;
}

/**
 * Get request data from POST or JSON body
 */
function getRequestData() {
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If JSON parsing fails, try to get data from POST
        if (!empty($_POST)) {
            return $_POST;
        }
        return null;
    }
    return $data;
}

/**
 * Authenticate user with API key
 */
function authenticateUser($api_key) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, sms_balance FROM users WHERE api_key = ? AND status = 'active'");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Check if phone number is rate limited
 */
function isRateLimited($phone) {
    global $conn;
    
    $rate_limit_query = "SELECT COUNT(*) as count FROM otp_codes WHERE phone_number = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $rate_stmt = $conn->prepare($rate_limit_query);
    $rate_stmt->bind_param("s", $phone);
    $rate_stmt->execute();
    $rate_result = $rate_stmt->get_result();
    $rate_row = $rate_result->fetch_assoc();
    
    return ($rate_row['count'] >= 5);
}

/**
 * Generate and store OTP in database
 */
function generateAndStoreOTP($user_id, $phone) {
    global $conn;
    
    $otp = generateOTP();
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $reference_id = 'otp_' . uniqid();
    
    $otp_insert = "INSERT INTO otp_codes (id, user_id, phone_number, code, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $otp_stmt = $conn->prepare($otp_insert);
    $otp_stmt->bind_param("sisss", $reference_id, $user_id, $phone, $otp, $expires_at);
    $otp_inserted = $otp_stmt->execute();
    
    if (!$otp_inserted) {
        return null;
    }
    
    return [
        'otp' => $otp,
        'reference_id' => $reference_id,
        'expires_at' => $expires_at
    ];
}

/**
 * Delete OTP from database
 */
function deleteOTP($reference_id) {
    global $conn;
    
    $delete_stmt = $conn->prepare("DELETE FROM otp_codes WHERE id = ?");
    $delete_stmt->bind_param("s", $reference_id);
    return $delete_stmt->execute();
}

/**
 * Log SMS in database
 */
function logSMS($user_id, $phone, $message, $reference_id, $sms_parts) {
    global $conn;
    
    $message_id = 'sms_' . uniqid();
    $stmt = $conn->prepare("INSERT INTO sms_logs (id, user_id, phone_number, message, reference, status, parts, created_at, sent_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())");
    $stmt->bind_param("sisssi", $message_id, $user_id, $phone, $message, $reference_id, $sms_parts);
    
    if (!$stmt->execute()) {
        return null;
    }
    
    return $message_id;
}

/**
 * Update SMS log status
 */
function updateSMSLog($message_id, $status, $provider = null, $provider_message_id = null, $provider_response = null) {
    global $conn;
    
    if ($provider && $provider_message_id) {
        $update_stmt = $conn->prepare("UPDATE sms_logs SET status = ?, provider = ?, provider_message_id = ?, provider_response = ? WHERE id = ?");
        $update_stmt->bind_param("sssss", $status, $provider, $provider_message_id, $provider_response, $message_id);
    } else {
        $update_stmt = $conn->prepare("UPDATE sms_logs SET status = ?, provider_response = ? WHERE id = ?");
        $update_stmt->bind_param("sss", $status, $provider_response, $message_id);
    }
    
    return $update_stmt->execute();
}

/**
 * Update user SMS balance
 */
function updateUserBalance($user_id, $new_balance) {
    global $conn;
    
    $balance_stmt = $conn->prepare("UPDATE users SET sms_balance = ? WHERE id = ?");
    $balance_stmt->bind_param("ii", $new_balance, $user_id);
    return $balance_stmt->execute();
}

/**
 * Get active SMS provider
 */
function getActiveProvider() {
    global $conn;
    
    $provider_query = "SELECT * FROM sms_providers WHERE status = 'active' ORDER BY priority ASC LIMIT 1";
    $provider_result = $conn->query($provider_query);
    
    if (!$provider_result) {
        return null;
    }
    
    return $provider_result->fetch_assoc();
}

/**
 * Get fallback SMS provider
 */
function getFallbackProvider($primary_provider_id) {
    global $conn;
    
    $fallback_query = "SELECT * FROM sms_providers WHERE status = 'active' AND id != ? ORDER BY priority ASC LIMIT 1";
    $fallback_stmt = $conn->prepare($fallback_query);
    $fallback_stmt->bind_param("i", $primary_provider_id);
    $fallback_stmt->execute();
    $fallback_result = $fallback_stmt->get_result();
    
    if ($fallback_result->num_rows === 0) {
        return null;
    }
    
    return $fallback_result->fetch_assoc();
}

/**
 * Send error response
 */
function sendErrorResponse($status_code, $error_code, $error_message) {
    http_response_code($status_code);
    echo json_encode([
        "success" => false,
        "error_code" => $error_code,
        "error_message" => $error_message
    ]);
    return false;
}

/**
 * Send success response
 */
function sendSuccessResponse($data) {
    http_response_code(200);
    echo json_encode(array_merge(["success" => true], $data));
    return true;
}

/**
 * Generate a random OTP code
 * 
 * @param int $length Length of the OTP code
 * @return string The generated OTP
 */
function generateOTP($length = 6)
{
    // Generate a random numeric OTP
    $otp = "";
    for ($i = 0; $i < $length; $i++) {
        $otp .= mt_rand(0, 9);
    }
    return $otp;
}

/**
 * Send SMS through the specified provider
 * 
 * @param array $provider The provider details
 * @param string $phone The phone number
 * @param string $message The message content
 * @param string $message_id The internal message ID
 * @return array Result with success flag and additional info
 */
function sendSMS($provider, $phone, $message, $message_id)
{
    // Simulating API call to the provider
    $provider_type = $provider['type'];

    switch ($provider_type) {
        case 'twilio':
            return sendTwilioSMS($provider, $phone, $message, $message_id);

        case 'nexmo':
            return sendNexmoSMS($provider, $phone, $message, $message_id);

        case 'messagebird':
            return sendMessageBirdSMS($provider, $phone, $message, $message_id);

        default:
            // Mock a generic provider
            $success = (rand(1, 10) > 2); // 80% success rate

            if ($success) {
                $provider_message_id = 'ext_' . uniqid();
                $response = [
                    'id' => $provider_message_id,
                    'status' => 'sent',
                    'to' => $phone,
                    'timestamp' => date('Y-m-d H:i:s')
                ];

                return [
                    'success' => true,
                    'provider_message_id' => $provider_message_id,
                    'response_json' => json_encode($response)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Generic provider error: delivery failed'
                ];
            }
    }
}

/**
 * Mock Twilio SMS sending
 */
function sendTwilioSMS($provider, $phone, $message, $message_id)
{
    // In a real implementation, this would use the Twilio API
    // This is a mock implementation

    $success = (rand(1, 10) > 2); // 80% success rate

    if ($success) {
        $provider_message_id = 'tw_' . md5($message_id . time());
        $response = [
            'sid' => $provider_message_id,
            'status' => 'queued',
            'to' => $phone,
            'from' => $provider['sender_id'],
            'date_created' => date('Y-m-d H:i:s'),
            'error_code' => null
        ];

        return [
            'success' => true,
            'provider_message_id' => $provider_message_id,
            'response_json' => json_encode($response)
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Twilio error: Unable to queue message'
        ];
    }
}

/**
 * Mock Nexmo SMS sending
 */
function sendNexmoSMS($provider, $phone, $message, $message_id)
{
    // In a real implementation, this would use the Nexmo/Vonage API
    // This is a mock implementation

    $success = (rand(1, 10) > 2); // 80% success rate

    if ($success) {
        $provider_message_id = 'nx_' . substr(md5($message_id . time()), 0, 16);
        $response = [
            'message-count' => '1',
            'messages' => [
                [
                    'to' => $phone,
                    'message-id' => $provider_message_id,
                    'status' => '0',
                    'remaining-balance' => '10.000000',
                    'message-price' => '0.030000',
                    'network' => '12345'
                ]
            ]
        ];

        return [
            'success' => true,
            'provider_message_id' => $provider_message_id,
            'response_json' => json_encode($response)
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Nexmo error: Message submission failed'
        ];
    }
}

/**
 * Mock MessageBird SMS sending
 */
function sendMessageBirdSMS($provider, $phone, $message, $message_id)
{
    // In a real implementation, this would use the MessageBird API
    // This is a mock implementation

    $success = (rand(1, 10) > 2); // 80% success rate

    if ($success) {
        $provider_message_id = 'mb_' . uniqid();
        $response = [
            'id' => $provider_message_id,
            'href' => "https://rest.messagebird.com/messages/{$provider_message_id}",
            'direction' => 'mt',
            'type' => 'sms',
            'originator' => $provider['sender_id'],
            'body' => $message,
            'reference' => $message_id,
            'createdDatetime' => date('c'),
            'recipients' => [
                'totalCount' => 1,
                'totalSentCount' => 1,
                'totalDeliveredCount' => 0,
                'items' => [
                    [
                        'recipient' => str_replace('+', '', $phone),
                        'status' => 'sent',
                        'statusDatetime' => date('c')
                    ]
                ]
            ]
        ];

        return [
            'success' => true,
            'provider_message_id' => $provider_message_id,
            'response_json' => json_encode($response)
        ];
    } else {
        return [
            'success' => false,
            'error' => 'MessageBird error: Message not accepted'
        ];
    }
}

/**
 * Sanitize phone number by removing non-numeric characters except leading +
 */
function sanitizePhoneNumber($phone)
{
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
function validatePhoneNumber($phone)
{
    // Basic validation - should start with + followed by at least 8 digits
    return preg_match('/^\+[0-9]{8,15}$/', $phone);
}

/**
 * Calculate how many SMS parts a message will require
 */
function calculateSMSParts($message)
{
    $length = mb_strlen($message, 'UTF-8');

    // Check if message contains non-GSM characters
    $containsUnicode = false;
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($message, $i, 1, 'UTF-8');
        if (ord($char) > 127) {
            $containsUnicode = true;
            break;
        }
    }

    // Unicode messages have different length limits
    if ($containsUnicode) {
        $maxLength = 70;
    } else {
        $maxLength = 160;
    }

    // Calculate parts
    if ($length <= $maxLength) {
        return 1;
    } else {
        // For multi-part messages, each part is smaller due to header info
        $charPerPart = $containsUnicode ? 67 : 153;
        return ceil($length / $charPerPart);
    }
}

// Execute the main function
handleOTPRequest();

?>