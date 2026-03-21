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

// Process SMS test if form is submitted
$result = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($phone) || empty($message)) {
        $result = [
            'success' => false,
            'message' => 'Phone number and message are required'
        ];
    } else {
        // Sanitize inputs
        $phone = cleanInput($phone);
        $message = cleanInput($message);
        
        // Format phone number
        $phone = formatPhone($phone);
        
        // Send SMS
        $result = sendSMS($phone, $message,'primary',$user_id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMS - SMS Gateway</title>
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
                            <a class="nav-link active" href="sms_test.php">
                                <i class="fas fa-vial"></i> Test SMS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="otp_test.php">
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
                    <h1 class="h2">SMS Testing Tool</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <a href="otp_test.php" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-key me-2"></i> Test OTP
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card dashboard-card api-test-form">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-vial me-2"></i>Send Test SMS</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Use this form to test sending SMS messages. This will deduct from your SMS balance and will be logged in your account.
                                </p>
                                
                                <?php if ($result): ?>
                                    <?php if ($result['success']): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i> <?php echo $result['response']; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $result['response']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <form id="smsTestForm" method="post" action="">
                                    <div class="mb-3">
                                        <label for="testPhone" class="form-label">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control" id="testPhone" name="phone" placeholder="Enter phone number (e.g. +1234567890)" required value="<?php echo $_POST['phone'] ?? ''; ?>">
                                        </div>
                                        <small class="text-muted">International format preferred (e.g. +1234567890)</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="testMessage" class="form-label">Message</label>
                                        <textarea class="form-control" id="testMessage" name="message" rows="4" placeholder="Enter your SMS message here" required><?php echo $_POST['message'] ?? ''; ?></textarea>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-muted">Maximum 160 characters for standard SMS</small>
                                            <small id="charCount">0/160</small>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <div class="d-inline-block me-2">
                                            <span id="smsTestLoader" class="d-none">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <span class="ms-1">Sending...</span>
                                            </span>
                                        </div>
                                        <button type="reset" class="btn btn-outline-secondary me-2">Clear</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Send SMS
                                        </button>
                                    </div>
                                </form>
                                
                                <div id="smsTestResult" class="mt-3 d-none"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-info-circle me-2"></i>SMS API Information</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-3">Your API Key</h6>
                                <div class="api-key-container mb-4">
                                    <code><?php echo $user['api_key']; ?></code>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $user['api_key']; ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                
                                <h6 class="mb-2">API Endpoint</h6>
                                <div class="api-key-container mb-4">
                                    <code><?php echo $_SERVER['HTTP_HOST']; ?>/api/send.php</code>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $_SERVER['HTTP_HOST']; ?>/api/send.php">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb me-2"></i>Quick Tip</h6>
                                    <p class="mb-0 small">For more details on how to integrate our SMS API into your applications, visit the <a href="docs.php">API Documentation</a> page.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card dashboard-card mt-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="fas fa-balance-scale me-2"></i>SMS Balance</h5>
                            </div>
                            <div class="card-body text-center">
                                <h1 class="display-4 fw-bold text-primary"><?php echo number_format($user['sms_balance']); ?></h1>
                                <p class="text-muted">Available SMS credits</p>
                                <a href="dashboard.php#pricing" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Purchase More
                                </a>
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
    <script>
        // Character counter for SMS message
        document.addEventListener('DOMContentLoaded', function() {
            const messageTextarea = document.getElementById('testMessage');
            const charCount = document.getElementById('charCount');
            
            function updateCharCount() {
                const count = messageTextarea.value.length;
                charCount.textContent = count + '/160';
                
                if(count > 160) {
                    charCount.classList.add('text-danger');
                } else {
                    charCount.classList.remove('text-danger');
                }
            }
            
            messageTextarea.addEventListener('input', updateCharCount);
            updateCharCount(); // Initial count
        });
    </script>
</body>
</html> 