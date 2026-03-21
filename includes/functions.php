<?php

/**
 * SMS Gateway - Helper Functions
 */
// Check if a user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Check if a user is an admin
function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Secure redirect
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Clean input data
function cleanInput($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Generate API Key
function generateApiKey()
{
    return bin2hex(random_bytes(16));
}

/**
 * Send SMS
 *
 * This function connects to SMS APIs to send a message to a specified phone number.
 * It retrieves the active SMS provider, validates the phone number and message,
 * constructs the API request, and logs the SMS sending attempt.
 *
 * @param string $phone The phone number to which the SMS will be sent. 
 *                      Must be in international format (e.g., +91234567890).
 * @param string $message The content of the SMS message to be sent.
 * @param string $api_provider (optional) The name of the SMS provider to use. 
 *                             Defaults to 'primary'.
 * @param int $user_id The ID of the user sending the SMS.
 *
 * @return array An associative array containing the success status and response message.
 *               - 'success' (bool): Indicates whether the SMS was sent successfully.
 *               - 'response' (string): A message detailing the result of the SMS sending attempt.
 */
function sendSMS($phone, $message, $api_provider = 'primary', $user_id)
{
    global $conn;

    // Retrieve the SMS provider
    $sms_provider = getSmsProvider($conn) ?: getSmsProvider($conn, 1);

    // Check if a valid SMS provider is available
    if (!$sms_provider) {
        return [
            'success' => false,
            'response' => 'No active SMS provider available.'
        ];
    }

    $api_pro_url = $sms_provider['api_url'];
    $api_pro_name = $sms_provider['name'];
    $api_pro_key = $sms_provider['api_key'];
    $api_pro_status = $sms_provider['is_active'];

    // Validate the message and phone number
    if (empty($message) || !preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
        return [
            'success' => false,
            'response' => 'Invalid phone number or message.'
        ];
    }

    $msg = urlencode($message);
    $api_url = str_replace(['{api_key}', '{mobile}', '{message}'], [$api_pro_key, $phone, $msg], $api_pro_url);

    // Check API status and send the message
    if ($api_pro_status) {
        $response = file_get_contents($api_url);
        $res = json_decode($response, true);
        $success = isset($res['status']);

        // Log the SMS
        logAPISMS($phone, $message, $api_pro_name, $success, $response, $user_id);
        logSMS($phone, $message, $api_provider, $api_pro_name, $success, $success ? "Message sent successfully" : "Failed to send message",$user_id);
    } else {
        $success = false;
    }

    return [
        'success' => $success,
        'response' => $success ? "Message sent successfully" : "Failed to send message"
    ];
}

/**
 * Log SMS
 *
 * This function records the details of an SMS sent through the system into the database.
 * It logs the user ID, phone number, message content, provider used, success status,
 * and the response received from the SMS API.
 *
 * @param string $phone The phone number to which the SMS was sent.
 * @param string $message The content of the SMS message.
 * @param string $provider The name of the SMS provider used.
 * @param string $provider_name The name of the SMS provider for logging purposes.
 * @param bool $success Indicates whether the SMS was sent successfully.
 * @param string $response The response received from the SMS API.
 * @param int|null $user_id (optional) The ID of the user sending the SMS. 
 *                          Defaults to 0 if not provided.
 *
 * @return void
 */
function logSMS($phone, $message, $provider, $provider_name, $success, $response, $user_id)
{
    global $conn;

    $phone = cleanInput($phone);
    $message = cleanInput($message);
    $provider = cleanInput($provider);
    $response = cleanInput($response);
    $user_id = $user_id;
    $success = $success ? 1 : 0;

    $sql = "INSERT INTO sms_logs (user_id, phone, message, provider, provider_name, status, response, created_at) 
            VALUES ('$user_id', '$phone', '$message', '$provider', '$provider_name', '$success', '$response', NOW())";

    $conn->query($sql);
}


/**
 * Logs the details of an SMS sent through an API.
 *
 * This function records the SMS sending attempt in the database, including
 * the user ID, phone number, message content, provider used, success status,
 * and the response received from the API.
 *
 * @param string $phone The phone number to which the SMS was sent.
 * @param string $message The content of the SMS message.
 * @param string $provider The name of the SMS provider used.
 * @param bool $success Indicates whether the SMS was sent successfully.
 * @param string $response The response received from the SMS API.
 * @param int $user_id (optional) The ID of the user sending the SMS. 
 *                     Defaults to the user ID stored in the session.
 *
 * @return void
 */
function logAPISMS($phone, $message, $provider, $success, $response, $user_id)
{
    global $conn;

    $phone = cleanInput($phone);
    $message = cleanInput($message);
    $provider = cleanInput($provider);
    $response = cleanInput($response);
    $user_id = $user_id ?? 0;
    $success = $success ? 1 : 0;

    $sql = "INSERT INTO sms_api_logs (user_id, phone, message, provider, status, response, created_at) 
            VALUES ('$user_id', '$phone', '$message', '$provider', '$success', '$response', NOW())";

    $conn->query($sql);
}


// Format phone number
function formatPhone($phone)
{
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Check if country code is missing
    if (strlen($phone) == 10) {
        // Add default country code (assuming US +91)
        $phone = "91" . $phone;
    }

    return $phone;
}

// Check API balance
function checkAPIBalance($provider = 'primary')
{
    // This would connect to the actual SMS API to check balance
    // For now, we'll return a simulated balance
    if ($provider == 'primary') {
        return 5000; // Simulated balance of 5000 SMS
    } else {
        return 3000; // Simulated balance of 3000 SMS for backup provider
    }
}

// Get SMS pricing
function getSMSPricing()
{
    global $conn;

    $sql = "SELECT * FROM sms_pricing ORDER BY sms_count ASC";
    $result = $conn->query($sql);

    $pricing = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pricing[] = $row;
        }
    }

    return $pricing;
}

// Validate OTP
function validateOTP($phone, $otp)
{
    global $conn;

    $phone = cleanInput($phone);
    $otp = cleanInput($otp);

    $sql = "SELECT * FROM otp_codes WHERE phone = '$phone' AND otp = '$otp' AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND used = 0";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Mark OTP as used
        $sql = "UPDATE otp_codes SET used = 1 WHERE phone = '$phone' AND otp = '$otp'";
        $conn->query($sql);
        return true;
    }

    return false;
}
/**
 * Generate a One-Time Password (OTP) and send it via SMS.
 *
 * This function generates a 6-digit OTP, saves it to the database associated with the provided phone number,
 * and sends the OTP to the user via SMS.
 *
 * @param string $phone The phone number to which the OTP will be sent.
 * @return bool Returns true if the OTP was successfully sent, false otherwise.
 */
function generateOTP($phone)
{
    global $conn;

    // Clean the phone number input to prevent SQL injection
    $phone = cleanInput($phone);
    
    // Generate a 6-digit OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO otp_codes (phone, otp, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $phone, $otp);
    
    // Execute the statement and check for success
    if (!$stmt->execute()) {
        return false; // Return false if the insertion fails
    }

    // Send OTP via SMS
    //$message = "Your OTP is: $otp. Valid for 10 minutes."; // Message format
    $message = $otp;
    $result = sendSMS($phone,$message,'primary',$_SESSION['user_id']); // Call sendSMS with the correct parameters

    return $result['success']; // Return the success status of the SMS sending
}

// Get API usage statistics
function getAPIUsageStats($user_id = null, $days = 30)
{
    global $conn;

    $user_condition = "";
    if ($user_id) {
        $user_id = (int)$user_id;
        $user_condition = "AND user_id = $user_id";
    }

    $sql = "SELECT 
                DATE(created_at) as date, 
                COUNT(*) as total, 
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as failed
            FROM sms_logs 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY) $user_condition
            GROUP BY DATE(created_at)
            ORDER BY date ASC";

    $result = $conn->query($sql);

    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
    }

    return $stats;
}

/**
 * Get user by ID
 *
 * @param object $conn Database connection
 * @param int $userId User ID
 * @return array|false User data or false if not found
 */
function getUserById($conn, $userId)
{
    $userId = (int) $userId;

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return false;
    }

    return $result->fetch_assoc();
}

/**
 * Get Sms Provider is primary
 * 
 * @param object $conn Database Connection 
 * @param Provider Call Type : primary[0] or backup[1]
 * @return array|false SMS Provider data or false if not found
 */
function getSmsProvider($conn, $backup = 0)
{
    // Check if $conn is a MySQLi object
    if (!($conn instanceof mysqli)) {
        throw new InvalidArgumentException('Database connection is not a valid MySQLi object.');
    }

    $backup = $backup ? 1 : 0;

    $sql = "SELECT * FROM api_providers WHERE is_backup = ?";
    $stmt = $conn->prepare($sql);

    $is_backup_param = $backup == 0 ? 0 : 1; // Use a variable to hold the value
    $stmt->bind_param("i", $is_backup_param); // Pass the variable instead

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return false;
    }

    $provider = $result->fetch_assoc();

    // Check if the provider is active
    if (!$provider['is_active']) {
        return false; // Return false if the provider is not active
    }

    return $provider;
}

/**
 * Get SDK download statistics
 *
 * @param object $conn Database connection
 * @param string $period Period to get stats for (all, today, week, month)
 * @return array SDK download statistics
 */
function getSDKDownloadStats($conn, $period = 'all')
{
    $whereClause = '';

    switch ($period) {
        case 'today':
            $whereClause = "WHERE DATE(downloaded_at) = CURDATE()";
            break;
        case 'week':
            $whereClause = "WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $whereClause = "WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'all':
        default:
            // No where clause needed
            break;
    }

    // Get total downloads by SDK type
    $sql = "SELECT sdk_type, COUNT(*) as download_count 
            FROM sdk_downloads 
            $whereClause 
            GROUP BY sdk_type 
            ORDER BY download_count DESC";

    $result = $conn->query($sql);
    $sdkStats = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sdkStats[$row['sdk_type']] = $row['download_count'];
        }
    }

    // Get overall total
    $sql = "SELECT COUNT(*) as total_downloads FROM sdk_downloads $whereClause";
    $result = $conn->query($sql);
    $totalDownloads = 0;

    if ($result && $row = $result->fetch_assoc()) {
        $totalDownloads = $row['total_downloads'];
    }

    // Get unique users count
    $sql = "SELECT COUNT(DISTINCT user_id) as unique_users FROM sdk_downloads $whereClause";
    $result = $conn->query($sql);
    $uniqueUsers = 0;

    if ($result && $row = $result->fetch_assoc()) {
        $uniqueUsers = $row['unique_users'];
    }

    // Get downloads by date (for charts)
    $sql = "SELECT DATE(downloaded_at) as download_date, COUNT(*) as download_count 
            FROM sdk_downloads 
            $whereClause 
            GROUP BY DATE(downloaded_at) 
            ORDER BY download_date ASC";

    $result = $conn->query($sql);
    $downloadsByDate = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $downloadsByDate[$row['download_date']] = $row['download_count'];
        }
    }

    return [
        'total' => $totalDownloads,
        'unique_users' => $uniqueUsers,
        'by_type' => $sdkStats,
        'by_date' => $downloadsByDate
    ];
}

function calculateSMSParts($message) {
    // Calculate the number of SMS parts needed based on message length
    $messageLength = strlen($message);
    $maxLength = 160; // Standard SMS length

    // If the message is longer than the max length, calculate parts
    if ($messageLength <= $maxLength) {
        return 1; // Only one part needed
    } else {
        // Calculate the number of parts needed for longer messages
        return ceil($messageLength / $maxLength);
    }
}

/**
 * Retrieves the timestamp of the last SMS sent to a specific phone number by a user.
 *
 * This function queries the sms_logs table to find the most recent SMS sent by the user
 * to the specified phone number. It returns the timestamp of the last SMS if found,
 * or null if no SMS has been sent to that number.
 *
 * @param int $user_id The ID of the user whose SMS history is being queried.
 * @param string $phone The phone number to check for the last SMS sent.
 *
 * @return string|null The timestamp of the last SMS sent, or null if no SMS has been sent.
 */
function getLastSMSTime($user_id, $phone) {
    global $conn;

    $phone = cleanInput($phone);
    $sql = "SELECT created_at FROM sms_logs WHERE user_id = ? AND phone = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        return $row['created_at']; // Return the timestamp of the last SMS sent
    }

    return null; // Return null if no SMS has been sent to this number
}

/**
 * Updates the timestamp of the last SMS sent to a specific phone number for a user.
 *
 * This function inserts a new record into the sms_logs table with the current timestamp
 * for the specified user and phone number. If a record already exists for the user and phone
 * number, it updates the existing record's timestamp to the current time.
 *
 * @param int $user_id The ID of the user sending the SMS.
 * @param string $phone The phone number to which the SMS was sent.
 *
 * @return void
 */
function updateLastSMSTime($user_id, $phone) {
    global $conn;

    $phone = cleanInput($phone);
    $sql = "INSERT INTO sms_logs (user_id, phone, created_at) VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE created_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $phone);
    $stmt->execute();
}

/**
 * Updates the SMS balance for a user in the database.
 *
 * This function updates the sms_balance field for the specified user in the users table.
 *
 * @param int $user_id The ID of the user whose SMS balance is being updated.
 * @param int $new_balance The new SMS balance to set for the user.
 *
 * @return void
 */
function updateUserSMSBalance($user_id, $new_balance) {
    global $conn;

    $sql = "UPDATE users SET sms_balance = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $new_balance, $user_id);
    $stmt->execute();
}



?>