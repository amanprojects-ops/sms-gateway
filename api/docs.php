<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get user information if not admin
$user = null;
if (!isAdmin()) {
    $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
    $user = $user_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - SMS Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/styles/github.min.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0">
                <div class="text-center p-4 mb-4 bg-primary text-white">
                    <h4><i class="fas fa-sms me-2"></i>SMS Gateway</h4>
                    <p class="mb-0"><?php echo isAdmin() ? 'Admin Panel' : 'API User Dashboard'; ?></p>
                </div>
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isAdmin() ? '../admin/dashboard.php' : 'dashboard.php'; ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <?php if (!isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="sms_test.php">
                                    <i class="fas fa-vial"></i> Test SMS
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="otp_test.php">
                                    <i class="fas fa-key"></i> Test OTP
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="docs.php">
                                <i class="fas fa-book"></i> API Documentation
                            </a>
                        </li>
                        <?php if (!isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="logs.php">
                                    <i class="fas fa-history"></i> SMS Logs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="profile.php">
                                    <i class="fas fa-user-cog"></i> Profile
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/users.php">
                                    <i class="fas fa-users"></i> API Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/api_settings.php">
                                    <i class="fas fa-cogs"></i> API Settings
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">API Documentation</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : 'dashboard.php'; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <?php if (!isAdmin()): ?>
                                <a href="sms_test.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-vial me-2"></i> Test SMS
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <nav>
                                    <div class="nav nav-tabs mb-4" id="nav-tab" role="tablist">
                                        <button class="nav-link active" id="nav-overview-tab" data-bs-toggle="tab" data-bs-target="#nav-overview" type="button" role="tab" aria-controls="nav-overview" aria-selected="true">Overview</button>
                                        <button class="nav-link" id="nav-authentication-tab" data-bs-toggle="tab" data-bs-target="#nav-authentication" type="button" role="tab" aria-controls="nav-authentication" aria-selected="false">Authentication</button>
                                        <button class="nav-link" id="nav-sms-tab" data-bs-toggle="tab" data-bs-target="#nav-sms" type="button" role="tab" aria-controls="nav-sms" aria-selected="false">SMS API</button>
                                        <button class="nav-link" id="nav-otp-tab" data-bs-toggle="tab" data-bs-target="#nav-otp" type="button" role="tab" aria-controls="nav-otp" aria-selected="false">OTP API</button>
                                        <button class="nav-link" id="nav-errors-tab" data-bs-toggle="tab" data-bs-target="#nav-errors" type="button" role="tab" aria-controls="nav-errors" aria-selected="false">Error Codes</button>
                                        <button class="nav-link" id="nav-libraries-tab" data-bs-toggle="tab" data-bs-target="#nav-libraries" type="button" role="tab" aria-controls="nav-libraries" aria-selected="false">Client Libraries</button>
                                    </div>
                                </nav>
                                <div class="tab-content" id="nav-tabContent">
                                    <!-- Overview Tab -->
                                    <div class="tab-pane fade show active" id="nav-overview" role="tabpanel" aria-labelledby="nav-overview-tab">
                                        <div class="doc-section">
                                            <h2>Introduction</h2>
                                            <p>
                                                Welcome to the SMS Gateway API documentation. Our API allows you to send SMS messages and generate/verify OTPs (One-Time Passwords) for your applications.
                                                This documentation provides instructions on how to integrate our services into your applications.
                                            </p>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Base URL:</strong> <code>https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/</code>
                                            </div>
                                        </div>

                                        <div class="doc-section">
                                            <h2>API Features</h2>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fas fa-paper-plane text-primary me-2"></i>SMS Sending</h5>
                                                            <p class="card-text">Send SMS messages to any phone number worldwide with high deliverability rates.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fas fa-key text-primary me-2"></i>OTP Services</h5>
                                                            <p class="card-text">Generate and verify One-Time Passwords for secure authentication.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fas fa-exchange-alt text-primary me-2"></i>Fallback Mechanisms</h5>
                                                            <p class="card-text">Automatic fallback to backup providers ensures high availability.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fas fa-chart-line text-primary me-2"></i>Detailed Analytics</h5>
                                                            <p class="card-text">Comprehensive reporting on message delivery and usage statistics.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="doc-section">
                                            <h2>Getting Started</h2>
                                            <p>To get started with our API, follow these simple steps:</p>
                                            <ol>
                                                <li>Make sure you have a valid API key (available in your dashboard)</li>
                                                <li>Include your API key in all requests for authentication</li>
                                                <li>Use the provided endpoints to send SMS or manage OTPs</li>
                                                <li>Process the JSON responses in your application</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <!-- Authentication Tab -->
                                    <div class="tab-pane fade" id="nav-authentication" role="tabpanel" aria-labelledby="nav-authentication-tab">
                                        <div class="doc-section">
                                            <h2>Authentication</h2>
                                            <p>
                                                All API requests require authentication using your API key. You can find your API key in your dashboard.
                                                There are two ways to include your API key in requests:
                                            </p>

                                            <h5 class="mt-4">1. Using Request Headers (Recommended)</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-http">POST /api/send.php HTTP/1.1
                                                Host: <?php echo $_SERVER['HTTP_HOST']; ?>
                                                Content-Type: application/json
                                                X-API-Key: your_api_key_here
                                                </code></pre>
                                            </div>

                                            <h5 class="mt-4">2. Using Request Parameters</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-http">POST /api/send.php?api_key=your_api_key_here HTTP/1.1
                                                Host: <?php echo $_SERVER['HTTP_HOST']; ?>
                                                Content-Type: application/json
                                                </code></pre>
                                            </div>

                                            <div class="alert alert-warning mt-4">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Security Notice:</strong> Keep your API key confidential. Do not expose it in client-side code or public repositories.
                                            </div>

                                            <?php if (!isAdmin() && $user): ?>
                                                <div class="card mt-4">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Your API Key</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="api-key-container">
                                                            <code><?php echo $user['api_key']; ?></code>
                                                            <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $user['api_key']; ?>">
                                                                <i class="fas fa-copy"></i> Copy
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- SMS API Tab -->
                                    <div class="tab-pane fade" id="nav-sms" role="tabpanel" aria-labelledby="nav-sms-tab">
                                        <div class="doc-section">
                                            <h2>Sending SMS Messages</h2>
                                            <p>
                                                The SMS API allows you to send text messages to any phone number worldwide.
                                                Messages are routed through our providers for optimal delivery rates.
                                            </p>

                                            <h5 class="mt-4">Endpoint</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-http">POST https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/send.php</code></pre>
                                            </div>

                                            <h5 class="mt-4">Request Parameters</h5>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Parameter</th>
                                                            <th>Type</th>
                                                            <th>Required</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><code>phone</code></td>
                                                            <td>String</td>
                                                            <td>Yes</td>
                                                            <td>Recipient's phone number in international format (e.g., +1234567890)</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>message</code></td>
                                                            <td>String</td>
                                                            <td>Yes</td>
                                                            <td>The text message to send (max 160 characters for standard SMS)</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>reference</code></td>
                                                            <td>String</td>
                                                            <td>No</td>
                                                            <td>Your reference ID for tracking (optional)</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <h5 class="mt-4">Example Request</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "phone": "+1234567890",
  "message": "Hello, this is a test message from SMS Gateway!",
  "reference": "order-123456"
}</code></pre>
                                            </div>

                                            <h5 class="mt-4">Example Response (Success)</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "success": true,
  "message_id": "sms_12345678901234",
  "status": "sent",
  "response": "Message sent successfully"
}</code></pre>
                                            </div>

                                            <h5 class="mt-4">Example Response (Error)</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "success": false,
  "error_code": "invalid_parameter",
  "error_message": "Invalid phone number format"
}</code></pre>
                                            </div>

                                            <h5 class="mt-4">Code Examples</h5>
                                            <nav>
                                                <div class="nav nav-tabs" id="code-tab" role="tablist">
                                                    <button class="nav-link active" id="code-php-tab" data-bs-toggle="tab" data-bs-target="#code-php" type="button" role="tab" aria-controls="code-php" aria-selected="true">PHP</button>
                                                    <button class="nav-link" id="code-js-tab" data-bs-toggle="tab" data-bs-target="#code-js" type="button" role="tab" aria-controls="code-js" aria-selected="false">JavaScript</button>
                                                    <button class="nav-link" id="code-python-tab" data-bs-toggle="tab" data-bs-target="#code-python" type="button" role="tab" aria-controls="code-python" aria-selected="false">Python</button>
                                                </div>
                                            </nav>
                                            <div class="tab-content" id="code-tabContent">
                                                <div class="tab-pane fade show active" id="code-php" role="tabpanel" aria-labelledby="code-php-tab">
                                                    <div class="doc-code">
                                                        <pre><code class="language-php">&lt;?php
$url = 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/send.php';
$data = [
    'phone' => '+1234567890',
    'message' => 'Hello, this is a test message from SMS Gateway!'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\nX-API-Key: YOUR_API_KEY\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result, true);

if ($response['success']) {
    echo "Message sent successfully with ID: " . $response['message_id'];
} else {
    echo "Error: " . $response['error_message'];
}
?&gt;</code></pre>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="code-js" role="tabpanel" aria-labelledby="code-js-tab">
                                                    <div class="doc-code">
                                                        <pre><code class="language-javascript">// Using fetch API
fetch('https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/send.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'YOUR_API_KEY'
    },
    body: JSON.stringify({
        phone: '+1234567890',
        message: 'Hello, this is a test message from SMS Gateway!'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Message sent successfully with ID:', data.message_id);
    } else {
        console.error('Error:', data.error_message);
    }
})
.catch(error => {
    console.error('API request failed:', error);
});</code></pre>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="code-python" role="tabpanel" aria-labelledby="code-python-tab">
                                                    <div class="doc-code">
                                                        <pre><code class="language-python">import requests
import json

url = "https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/send.php"
headers = {
    "Content-Type": "application/json",
    "X-API-Key": "YOUR_API_KEY"
}
data = {
    "phone": "+1234567890",
    "message": "Hello, this is a test message from SMS Gateway!"
}

response = requests.post(url, headers=headers, data=json.dumps(data))
result = response.json()

if result["success"]:
    print(f"Message sent successfully with ID: {result['message_id']}")
else:
    print(f"Error: {result['error_message']}")</code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- OTP API Tab -->
                                    <div class="tab-pane fade" id="nav-otp" role="tabpanel" aria-labelledby="nav-otp-tab">
                                        <div class="doc-section">
                                            <h2>OTP (One-Time Password) API</h2>
                                            <p>
                                                The OTP API allows you to generate and verify one-time passwords that are sent via SMS to users.
                                                This is useful for two-factor authentication, user verification, and secure transactions.
                                            </p>

                                            <h3 class="mt-5">Generating OTP</h3>

                                            <h5 class="mt-4">Endpoint</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-http">POST https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/generate_otp.php</code></pre>
                                            </div>

                                            <h5 class="mt-4">Request Parameters</h5>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Parameter</th>
                                                            <th>Type</th>
                                                            <th>Required</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><code>phone</code></td>
                                                            <td>String</td>
                                                            <td>Yes</td>
                                                            <td>Recipient's phone number in international format (e.g., +1234567890)</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>template</code></td>
                                                            <td>String</td>
                                                            <td>No</td>
                                                            <td>Custom message template. Use {otp} as a placeholder for the code. Default is "Your OTP is: {otp}. Valid for 10 minutes."</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <h5 class="mt-4">Example Request</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "phone": "+1234567890",
  "template": "Your verification code is {otp}. Please do not share this code with anyone."
}</code></pre>
                                            </div>

                                            <h5 class="mt-4">Example Response (Success)</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "success": true,
  "message": "OTP sent successfully",
  "reference_id": "otp_12345678901234",
  "expires_in": 600
}</code></pre>
                                            </div>

                                            <h3 class="mt-5">Verifying OTP</h3>

                                            <h5 class="mt-4">Endpoint</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-http">POST https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/verify_otp.php</code></pre>
                                            </div>

                                            <h5 class="mt-4">Request Parameters</h5>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Parameter</th>
                                                            <th>Type</th>
                                                            <th>Required</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><code>phone</code></td>
                                                            <td>String</td>
                                                            <td>Yes</td>
                                                            <td>Phone number that received the OTP</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>otp</code></td>
                                                            <td>String</td>
                                                            <td>Yes</td>
                                                            <td>The OTP code entered by the user</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <h5 class="mt-4">Example Request</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "phone": "+1234567890",
  "otp": "123456"
}</code></pre>
                                            </div>

                                            <h5 class="mt-4">Example Response (Success)</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "success": true,
  "message": "OTP verified successfully"
}</code></pre>
                                            </div>

                                            <h5 class="mt-4">Example Response (Error)</h5>
                                            <div class="doc-code">
                                                <pre><code class="language-json">{
  "success": false,
  "error_code": "invalid_otp",
  "error_message": "Invalid OTP or OTP expired"
}</code></pre>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error Codes Tab -->
                                    <div class="tab-pane fade" id="nav-errors" role="tabpanel" aria-labelledby="nav-errors-tab">
                                        <div class="doc-section">
                                            <h2>Error Codes</h2>
                                            <p>When an API request fails, you will receive a JSON response with an error code and message. Here are the possible error codes:</p>

                                            <div class="table-responsive mt-4">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Error Code</th>
                                                            <th>Description</th>
                                                            <th>Possible Solution</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><code>authentication_failed</code></td>
                                                            <td>Invalid or missing API key</td>
                                                            <td>Check your API key or generate a new one from your dashboard</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>invalid_parameter</code></td>
                                                            <td>One or more parameters are invalid or missing</td>
                                                            <td>Check the parameters in your request</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>insufficient_balance</code></td>
                                                            <td>Not enough SMS balance to send the message</td>
                                                            <td>Purchase more SMS credits</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>invalid_phone</code></td>
                                                            <td>Invalid phone number format</td>
                                                            <td>Use international format (e.g., +1234567890)</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>message_too_long</code></td>
                                                            <td>Message exceeds the maximum allowed length</td>
                                                            <td>Shorten your message or split it into multiple messages</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>invalid_otp</code></td>
                                                            <td>OTP is invalid or has expired</td>
                                                            <td>Generate a new OTP</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>too_many_attempts</code></td>
                                                            <td>Too many failed attempts to verify OTP</td>
                                                            <td>Generate a new OTP</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>rate_limit_exceeded</code></td>
                                                            <td>You've exceeded the rate limit for API requests</td>
                                                            <td>Wait before making more requests</td>
                                                        </tr>
                                                        <tr>
                                                            <td><code>server_error</code></td>
                                                            <td>An error occurred on our server</td>
                                                            <td>Please try again later or contact support</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Client Libraries Tab -->
                                    <div class="tab-pane fade" id="nav-libraries" role="tabpanel" aria-labelledby="nav-libraries-tab">
                                        <div class="doc-section">
                                            <h2>Client Libraries</h2>
                                            <p>
                                                To make integration easier, we provide client libraries for various programming languages.
                                                These libraries handle the low-level details of authenticating and communicating with our API.
                                            </p>

                                            <div class="row mt-4">
                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fab fa-php text-primary me-2"></i>PHP Library</h5>
                                                            <p class="card-text">Official PHP client for the SMS Gateway API.</p>
                                                            <div class="doc-code">
                                                                <pre><code class="language-bash">composer require sms-gateway/php-client</code></pre>
                                                            </div>
                                                            <a href="../assets/download/sms-gateway-php-sdk.php" target="_blank" download class="btn btn-sm btn-primary mt-3">
                                                                <i class="fas fa-download me-2"></i>Download
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-outline-secondary mt-3 ms-2">
                                                                <i class="fab fa-github me-2"></i>GitHub
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fab fa-node-js text-primary me-2"></i>Node.js Library</h5>
                                                            <p class="card-text">Official Node.js client for the SMS Gateway API.</p>
                                                            <div class="doc-code">
                                                                <pre><code class="language-bash">npm install sms-gateway-client</code></pre>
                                                            </div>
                                                            <a href="../assets/download/sms-gateway-node-sdk.js" target="_blank" download class="btn btn-sm btn-primary mt-3">
                                                                <i class="fas fa-download me-2"></i>Download
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-outline-secondary mt-3 ms-2">
                                                                <i class="fab fa-github me-2"></i>GitHub
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fab fa-python text-primary me-2"></i>Python Library</h5>
                                                            <p class="card-text">Official Python client for the SMS Gateway API.</p>
                                                            <div class="doc-code">
                                                                <pre><code class="language-bash">pip install sms-gateway-client</code></pre>
                                                            </div>
                                                            <a href="../assets/download/sms_gateway_python_sdk.py" target="_blank" download class="btn btn-sm btn-primary mt-3">
                                                                <i class="fas fa-download me-2"></i>Download
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-outline-secondary mt-3 ms-2">
                                                                <i class="fab fa-github me-2"></i>GitHub
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fab fa-java text-primary me-2"></i>Java Library</h5>
                                                            <p class="card-text">Official Java client for the SMS Gateway API.</p>
                                                            <div class="doc-code">
                                                                <pre><code class="language-bash">mvn install com.smsgateway:sms-gateway-java-sdk:1.0.0</code></pre>
                                                            </div>
                                                            <a href="../assets/download/sms_gateway_java_sdk.java" target="_blank" download class="btn btn-sm btn-primary mt-3">
                                                                <i class="fas fa-download me-2"></i>Download
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-outline-secondary mt-3 ms-2">
                                                                <i class="fab fa-github me-2"></i>GitHub
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fab fa-c++ text-primary me-2"></i>C++ Library</h5>
                                                            <p class="card-text">Official C++ client for the SMS Gateway API.</p>
                                                            <div class="doc-code">
                                                                <pre><code class="language-bash">g++ -o sms_gateway_cpp_sdk sms_gateway_c++.cpp -lcurl -lnlohmann_json</code></pre>
                                                            </div>
                                                            <a href="../assets/download/sms_gateway_c++.cpp" target="_blank" download class="btn btn-sm btn-primary mt-3">
                                                                <i class="fas fa-download me-2"></i>Download
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-outline-secondary mt-3 ms-2">
                                                                <i class="fab fa-github me-2"></i>GitHub
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="fab fa-vb text-primary me-2"></i>VB.net Library</h5>
                                                            <p class="card-text">Official VB.net client for the SMS Gateway API.</p>
                                                            <div class="doc-code">
                                                                <pre><code class="language-bash">mvn install com.smsgateway:sms-gateway-java-sdk:1.0.0</code></pre>
                                                            </div>
                                                            <a href="../assets/download/sms_gateway_vb_sdk.vb" target="_blank" download class="btn btn-sm btn-primary mt-3">
                                                                <i class="fas fa-download me-2"></i>Download
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-outline-secondary mt-3 ms-2">
                                                                <i class="fab fa-github me-2"></i>GitHub
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-info mt-4">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Need a library for another language?</strong> We're continuously adding support for more languages. Contact us if you need assistance with a specific platform.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="pt-5 d-flex justify-content-between">
                    <span>Copyright © 2025 <a href="#">SMS Gateway</a></span>
                    <ul class="nav m-0">
                        <li class="nav-item">
                            <a class="nav-link text-secondary" href="#">Privacy Policy</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-secondary" href="#">Terms of Use</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-secondary" href="#">Support</a>
                        </li>
                    </ul>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.0/highlight.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize code highlighting
            hljs.highlightAll();

            // Initialize tab navigation through URL hash
            let url = document.location.toString();
            if (url.match('#')) {
                let tabId = url.split('#')[1];
                if (document.getElementById(`nav-${tabId}-tab`)) {
                    new bootstrap.Tab(document.getElementById(`nav-${tabId}-tab`)).show();
                }
            }

            // Update URL hash when tab changes
            const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabLinks.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(e) {
                    let id = e.target.id.replace('-tab', '');
                    id = id.replace('nav-', '');
                    window.history.replaceState(null, null, `#${id}`);
                });
            });
        });
    </script>
</body>

</html>