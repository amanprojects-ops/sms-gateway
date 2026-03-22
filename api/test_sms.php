<?php
// Include necessary files
require_once '../config/database.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is api_user
if (!isLoggedIn() || isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input data
    $data = json_decode(file_get_contents('php://input'), true);
    // Extract data from JSON request
    $phone = $data['phone'] ?? '';
    $message = $data['message'] ?? '';

    // Check if JSON data was received properly
    if ($data === null) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received']);
        exit;
    }

    // Validate input
    if (empty($phone) || empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Phone number and message are required']);
        exit;
    }

    // Sanitize inputs
    $phone = cleanInput($phone);
    $message = cleanInput($message);
    $sms_parts = calculateSMSParts($message);

    // Format phone number
    $phone = formatPhone($phone);

    // Get the user's current SMS balance
    $user_result = $conn->query("SELECT sms_balance FROM users WHERE id = $user_id");
    $user = $user_result->fetch_assoc();
    $current_balance = $user['sms_balance'];

    // Check balance
    if ($current_balance < $sms_parts) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Insufficient balance. Required: $sms_parts, Available: $current_balance"]);
        exit;
    }

    // Send SMS
    $result = sendSMS($phone, $message, 'primary', $user_id);
    if ($result['success']) {
        // Deduct SMS balance
        $new_balance = $current_balance - $sms_parts; // Assuming each SMS costs 1 credit
        updateUserSMSBalance($user_id, $new_balance); // Function to update the user's SMS balance
        // Return API response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'SMS sent successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to send SMS']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
