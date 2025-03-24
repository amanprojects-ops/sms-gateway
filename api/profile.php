<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is not admin
if (!isLoggedIn() || isAdmin()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();

$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $company = $conn->real_escape_string($_POST['company']);
    $phone = $conn->real_escape_string($_POST['phone']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
        $message_type = 'danger';
    } else {
        // Check if email is already in use by a different user
        $email_check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if ($email_check->num_rows > 0) {
            $message = 'Email is already in use by another account';
            $message_type = 'danger';
        } else {
            // Update user profile
            $update_query = "UPDATE users SET 
                full_name = '$name', 
                email = '$email', 
                company = '$company', 
                phone = '$phone', 
                updated_at = NOW() 
                WHERE id = $user_id";
            
            if ($conn->query($update_query)) {
                $message = 'Profile updated successfully';
                $message_type = 'success';
                
                // Refresh user data
                $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
                $user = $user_result->fetch_assoc();
            } else {
                $message = 'Error updating profile: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $message = 'Current password is incorrect';
        $message_type = 'danger';
    } else if (strlen($new_password) < 8) {
        $message = 'New password must be at least 8 characters long';
        $message_type = 'danger';
    } else if ($new_password !== $confirm_password) {
        $message = 'New passwords do not match';
        $message_type = 'danger';
    } else {
        // Hash the new password and update
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = '$hashed_password', updated_at = NOW() WHERE id = $user_id";
        
        if ($conn->query($update_query)) {
            $message = 'Password changed successfully';
            $message_type = 'success';
        } else {
            $message = 'Error changing password: ' . $conn->error;
            $message_type = 'danger';
        }
    }
}

// Handle API key regeneration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_api_key'])) {
    // Generate new API key
    $new_api_key = generateApiKey();
    
    // Update in database
    $update_query = "UPDATE users SET api_key = '$new_api_key', updated_at = NOW() WHERE id = $user_id";
    
    if ($conn->query($update_query)) {
        $message = 'API key regenerated successfully';
        $message_type = 'success';
        
        // Refresh user data
        $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
        $user = $user_result->fetch_assoc();
    } else {
        $message = 'Error regenerating API key: ' . $conn->error;
        $message_type = 'danger';
    }
}

// Get subscription information
$subscription_query = "SELECT * FROM subscriptions WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1";
$subscription_result = $conn->query($subscription_query);
$subscription = $subscription_result->num_rows > 0 ? $subscription_result->fetch_assoc() : null;

// Get recent transactions
$transactions_query = "SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
$transactions_result = $conn->query($transactions_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SMS Gateway</title>
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
                            <a class="nav-link active" href="profile.php">
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
                    <h1 class="h2">My Profile</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Account Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="avatar-circle">
                                        <span class="avatar-text"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                    </div>
                                    <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="fw-bold">Company:</label>
                                    <p><?php echo !empty($user['company']) ? htmlspecialchars($user['company']) : 'Not specified'; ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="fw-bold">Phone:</label>
                                    <p><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not specified'; ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="fw-bold">Account Created:</label>
                                    <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="fw-bold">Last Login:</label>
                                    <p><?php echo !empty($user['last_login']) ? date('F j, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8 mb-4">
                        <div class="card dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="company" class="form-label">Company Name</label>
                                            <input type="text" class="form-control" id="company" name="company" value="<?php echo htmlspecialchars($user['company']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                                            <div class="form-text">Password must be at least 8 characters long</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="change_password" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="mb-0">API Key</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Warning:</strong> Regenerating your API key will invalidate your previous key. You will need to update any applications using the old key.
                                </div>
                                
                                <div class="api-key-container mb-3">
                                    <label class="fw-bold">Your API Key:</label>
                                    <div class="d-flex align-items-center">
                                        <code class="me-2 flex-grow-1"><?php echo $user['api_key']; ?></code>
                                        <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $user['api_key']; ?>">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                                
                                <form method="POST" action="profile.php" onsubmit="return confirm('Are you sure you want to regenerate your API key? This will invalidate your current key.');">
                                    <div class="text-end">
                                        <button type="submit" name="regenerate_api_key" class="btn btn-danger">
                                            <i class="fas fa-sync-alt me-2"></i> Regenerate API Key
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Subscription Details</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($subscription): ?>
                                    <div class="mb-3">
                                        <label class="fw-bold">Plan:</label>
                                        <p><?php echo htmlspecialchars($subscription['plan_name']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="fw-bold">Status:</label>
                                        <span class="badge bg-<?php echo $subscription['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($subscription['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="fw-bold">Start Date:</label>
                                        <p><?php echo date('F j, Y', strtotime($subscription['start_date'])); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="fw-bold">Expiry Date:</label>
                                        <p><?php echo date('F j, Y', strtotime($subscription['end_date'])); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="fw-bold">SMS Balance:</label>
                                        <p><strong><?php echo number_format($subscription['sms_balance']); ?></strong> messages</p>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <a href="#" class="btn btn-primary">
                                            <i class="fas fa-shopping-cart me-2"></i> Upgrade Plan
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-exclamation-circle text-warning fa-3x mb-3"></i>
                                        <h5>No Active Subscription</h5>
                                        <p class="text-muted">You don't have an active subscription plan.</p>
                                        <a href="#" class="btn btn-primary mt-3">
                                            <i class="fas fa-shopping-cart me-2"></i> Purchase a Plan
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Transactions</h5>
                                <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if ($transactions_result->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Description</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                        <td><?php echo '$' . number_format($transaction['amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                                <?php echo ucfirst($transaction['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-receipt text-muted fa-3x mb-3"></i>
                                        <h5>No Transactions</h5>
                                        <p class="text-muted">You don't have any transaction history yet.</p>
                                    </div>
                                <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Copy API key functionality
            document.querySelectorAll('.copy-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const textToCopy = button.getAttribute('data-copy');
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        button.classList.add('btn-success');
                        button.classList.remove('btn-outline-secondary');
                        
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-outline-secondary');
                        }, 2000);
                    });
                });
            });
            
            // Password confirmation validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePassword() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('change', validatePassword);
                confirmPassword.addEventListener('keyup', validatePassword);
            }
        });
    </script>
</body>
</html> 