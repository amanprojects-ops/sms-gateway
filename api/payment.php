<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is api_user
if (!isLoggedIn() || isAdmin()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Check if plan_id and amount are provided
if (!isset($_GET['plan_id']) || !isset($_GET['amount'])) {
    $_SESSION['error'] = "Invalid payment request";
    redirect('dashboard.php');
}

$plan_id = (int)$_GET['plan_id'];
$amount = (float)$_GET['amount'];

// Verify the plan exists and the amount is correct
$plan_query = $conn->prepare("SELECT * FROM sms_pricing WHERE id = ? AND is_active = 1");
$plan_query->bind_param("i", $plan_id);
$plan_query->execute();
$plan_result = $plan_query->get_result();

if ($plan_result->num_rows === 0) {
    $_SESSION['error'] = "Selected plan does not exist or is not active";
    redirect('dashboard.php');
}

$plan = $plan_result->fetch_assoc();

// Verify the amount matches the plan price
if ($plan['price'] != $amount) {
    $_SESSION['error'] = "Invalid payment amount";
    redirect('dashboard.php');
}

// Get the active payment gateway with highest priority (lowest number)
$gateway_query = $conn->query("SELECT * FROM payment_gateways WHERE is_active = 1 ORDER BY priority ASC, success_rate DESC LIMIT 1");

if ($gateway_query->num_rows === 0) {
    $_SESSION['error'] = "No payment gateway available. Please contact support.";
    redirect('dashboard.php');
}

$gateway = $gateway_query->fetch_assoc();

// Create a payment record
$reference = 'SMS_' . time() . '_' . $user_id;
$payment_query = $conn->prepare("INSERT INTO payments (user_id, plan_id, amount, reference, status, gateway_id, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
$payment_query->bind_param("iidsi", $user_id, $plan_id, $amount, $reference, $gateway['id']);
$payment_query->execute();
$payment_id = $conn->insert_id;

// Get user information
$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

// Prepare payment data based on the gateway
$payment_data = [
    'amount' => $amount,
    'currency' => 'USD',
    'reference' => $reference,
    'description' => 'Purchase of ' . $plan['name'] . ' SMS Plan',
    'customer_email' => $user['email'],
    'customer_name' => $user['name'],
    'callback_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/payment_callback.php',
    'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/dashboard.php',
    'metadata' => [
        'payment_id' => $payment_id,
        'user_id' => $user_id,
        'plan_id' => $plan_id
    ]
];

// Process payment based on gateway type
$payment_url = '';
switch ($gateway['name']) {
    case 'Stripe':
        // Stripe integration
        $payment_url = processStripePayment($gateway, $payment_data);
        break;
    case 'PayPal':
        // PayPal integration
        $payment_url = processPayPalPayment($gateway, $payment_data);
        break;
    default:
        // Generic payment processor
        $payment_url = processGenericPayment($gateway, $payment_data);
        break;
}

if (!$payment_url) {
    $_SESSION['error'] = "Payment processing failed. Please try again later.";
    redirect('dashboard.php');
}

// Redirect to payment page
header("Location: $payment_url");
exit;

/**
 * Process payment with Stripe
 */
function processStripePayment($gateway, $payment_data) {
    // This would typically use Stripe SDK
    $api_url = 'https://api.stripe.com/v1/checkout/sessions';
    
    $headers = [
        'Authorization: Bearer ' . $gateway['api_key'],
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    $data = [
        'payment_method_types[]' => 'card',
        'line_items[0][price_data][currency]' => $payment_data['currency'],
        'line_items[0][price_data][unit_amount]' => $payment_data['amount'] * 100, // Stripe uses cents
        'line_items[0][price_data][product_data][name]' => $payment_data['description'],
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $payment_data['callback_url'] . '?reference=' . $payment_data['reference'],
        'cancel_url' => $payment_data['cancel_url'],
        'client_reference_id' => $payment_data['reference']
    ];
    
    $response = makeApiRequest($api_url, $headers, $data);
    
    if (isset($response['url'])) {
        return $response['url'];
    }
    
    return false;
}

/**
 * Process payment with PayPal
 */
function processPayPalPayment($gateway, $payment_data) {
    // This would typically use PayPal SDK
    $api_url = 'https://api.paypal.com/v2/checkout/orders';
    
    // First get access token
    $token_url = 'https://api.paypal.com/v1/oauth2/token';
    $token_headers = [
        'Authorization: Basic ' . base64_encode($gateway['api_key'] . ':' . $gateway['api_secret']),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    $token_data = ['grant_type' => 'client_credentials'];
    $token_response = makeApiRequest($token_url, $token_headers, $token_data);
    
    if (!isset($token_response['access_token'])) {
        return false;
    }
    
    $headers = [
        'Authorization: Bearer ' . $token_response['access_token'],
        'Content-Type: application/json'
    ];
    
    $data = json_encode([
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => $payment_data['currency'],
                    'value' => $payment_data['amount']
                ],
                'description' => $payment_data['description'],
                'reference_id' => $payment_data['reference']
            ]
        ],
        'application_context' => [
            'return_url' => $payment_data['callback_url'] . '?reference=' . $payment_data['reference'],
            'cancel_url' => $payment_data['cancel_url']
        ]
    ]);
    
    $response = makeApiRequest($api_url, $headers, $data, 'POST', true);
    
    if (isset($response['links'])) {
        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
    }
    
    return false;
}

/**
 * Process payment with a generic gateway
 */
function processGenericPayment($gateway, $payment_data) {
    // Generic payment gateway integration
    $api_url = $gateway['webhook_url'] ?: 'https://api.payment-gateway.com/process';
    
    $headers = [
        'Authorization: Bearer ' . $gateway['api_key'],
        'Content-Type: application/json'
    ];
    
    $data = json_encode([
        'amount' => $payment_data['amount'],
        'currency' => $payment_data['currency'],
        'reference' => $payment_data['reference'],
        'description' => $payment_data['description'],
        'customer' => [
            'email' => $payment_data['customer_email'],
            'name' => $payment_data['customer_name']
        ],
        'callback_url' => $payment_data['callback_url'],
        'cancel_url' => $payment_data['cancel_url'],
        'metadata' => $payment_data['metadata']
    ]);
    
    $response = makeApiRequest($api_url, $headers, $data, 'POST', true);
    
    if (isset($response['payment_url'])) {
        return $response['payment_url'];
    }
    
    return false;
}

/**
 * Make API request to payment gateway
 */
function makeApiRequest($url, $headers, $data, $method = 'POST', $json = false) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($json) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        error_log("Payment API Error: " . $error);
        return false;
    }
    
    return json_decode($response, true);
}
?>
