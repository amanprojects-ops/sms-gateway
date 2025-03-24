<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is api_user
if (!isLoggedIn() || isAdmin()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();

// Process OTP generation if form is submitted
$result = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'] ?? '';

    if (empty($phone)) {
        $result = [
            'success' => false,
            'message' => 'Phone number is required'
        ];
    } else {
        // Sanitize input
        $phone = cleanInput($phone);

        // Format phone number
        $phone = formatPhone($phone);

        // Generate OTP
        $success = generateOTP($phone);

        // Debit wallet balance
        if ($success) {
            $wallet_balance = getUserById($conn,$user_id); // Assuming a function to get wallet balance
            $otp_cost = 1; // Cost of sending OTP

            if ($wallet_balance >= $otp_cost) {
                updateUserSMSBalance($user_id, $wallet_balance['sms_balance'] - $otp_cost); // Assuming a function to deduct from wallet
                $result = [
                    'success' => true,
                    'message' => 'OTP sent successfully to ' . $phone . '. Check your phone for the code.'
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Insufficient wallet balance to send OTP.'
                ];
            }
        } else {
            $result = [
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.'
            ];
        }
    }
}

// Process OTP verification
$verify_result = null;
if (isset($_POST['otp']) && isset($_POST['verify_phone'])) {
    $otp = cleanInput($_POST['otp']);
    $phone = cleanInput($_POST['verify_phone']);
    $phone = formatPhone($phone);

    if (validateOTP($phone, $otp)) {
        $verify_result = [
            'success' => true,
            'message' => 'OTP verified successfully!'
        ];
    } else {
        $verify_result = [
            'success' => false,
            'message' => 'Invalid OTP or OTP expired.'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test OTP - SMS Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0">
                <div class="text-center p-4 mb-4 bg-primary text-white">
                    <h4><i class="fas fa-sms me-2"></i>SMS Gateway</h4>
                    <p class="mb-0">API User Dashboard</p>
                </div>
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sms_test.php">
                                <i class="fas fa-vial"></i> Test SMS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="otp_test.php">
                                <i class="fas fa-key"></i> Test OTP
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="docs.php">
                                <i class="fas fa-book"></i> API Documentation
                            </a>
                        </li>
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
                    <h1 class="h2">OTP Testing Tool</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <a href="sms_test.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-vial me-2"></i> Test SMS
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card dashboard-card api-test-form">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-key me-2"></i>Generate & Verify OTP</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Test our OTP (One-Time Password) service. An OTP will be sent to the provided phone number and will be valid for 10 minutes.
                                </p>

                                <!-- Step 1: Generate OTP -->
                                <div class="mb-5">
                                    <h5 class="border-bottom pb-2 mb-3">Step 1: Generate OTP</h5>

                                    <?php if ($result && !isset($_POST['otp'])): ?>
                                        <?php if ($result['success']): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i> <?php echo $result['message']; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-danger">
                                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $result['message']; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <form id="otpTestForm" method="post" action="">
                                        <div class="mb-3">
                                            <label for="otpPhone" class="form-label">Phone Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                <input type="text" class="form-control" id="otpPhone" name="phone" placeholder="Enter phone number (e.g. +1234567890)" required value="<?php echo $_POST['phone'] ?? ''; ?>">
                                            </div>
                                            <small class="text-muted">International format preferred (e.g. +1234567890)</small>
                                        </div>

                                        <div class="text-end">
                                            <div class="d-inline-block me-2">
                                                <span id="otpTestLoader" class="d-none">
                                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <span class="ms-1">Sending...</span>
                                                </span>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Send OTP
                                            </button>
                                        </div>
                                    </form>

                                    <div id="otpTestResult" class="mt-3 d-none"></div>
                                </div>

                                <!-- Step 2: Verify OTP -->
                                <div>
                                    <h5 class="border-bottom pb-2 mb-3">Step 2: Verify OTP</h5>

                                    <?php if ($verify_result): ?>
                                        <?php if ($verify_result['success']): ?>
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i> <?php echo $verify_result['message']; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-danger">
                                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $verify_result['message']; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <form id="verifyOtpForm" method="post" action="">
                                        <input type="hidden" name="verify_phone" value="<?php echo $_POST['phone'] ?? ''; ?>">

                                        <div class="mb-3">
                                            <label for="otpCode" class="form-label">OTP Code</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="text" class="form-control" id="otpCode" name="otp" placeholder="Enter the OTP code you received" required>
                                            </div>
                                            <small class="text-muted">Enter the 6-digit code you received via SMS</small>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check-circle me-2"></i>Verify OTP
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>OTP API Information</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-3">Your API Key</h6>
                                <div class="api-key-container mb-4">
                                    <code><?php echo $user['api_key']; ?></code>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $user['api_key']; ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>

                                <h6 class="mb-2">Generate OTP Endpoint</h6>
                                <div class="api-key-container mb-3">
                                    <code><?php echo $_SERVER['HTTP_HOST']; ?>/api/generate_otp.php</code>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $_SERVER['HTTP_HOST']; ?>/api/generate_otp.php">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>

                                <h6 class="mb-2">Verify OTP Endpoint</h6>
                                <div class="api-key-container mb-4">
                                    <code><?php echo $_SERVER['HTTP_HOST']; ?>/api/verify_otp.php</code>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $_SERVER['HTTP_HOST']; ?>/api/verify_otp.php">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb me-2"></i>Quick Tip</h6>
                                    <p class="mb-0 small">OTP codes are valid for 10 minutes and can only be used once. For more details, visit the <a href="docs.php">API Documentation</a> page.</p>
                                </div>
                            </div>
                        </div>

                        <div class="card dashboard-card mt-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-question-circle me-2"></i>Common OTP Use Cases</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex">
                                        <i class="fas fa-user-check me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">User Verification</h6>
                                            <p class="mb-0 small text-muted">Verify user identity during registration</p>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex">
                                        <i class="fas fa-shield-alt me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Two-Factor Authentication</h6>
                                            <p class="mb-0 small text-muted">Add an extra layer of security</p>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex">
                                        <i class="fas fa-key me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Password Reset</h6>
                                            <p class="mb-0 small text-muted">Securely reset user passwords</p>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex">
                                        <i class="fas fa-shopping-cart me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Transaction Confirmation</h6>
                                            <p class="mb-0 small text-muted">Verify payment or purchase</p>
                                        </div>
                                    </li>
                                </ul>
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
    <script src="../assets/js/main.js"></script>
</body>

</html>