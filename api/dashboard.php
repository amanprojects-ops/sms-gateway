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

// Generate API key if not exists
if (empty($user['api_key'])) {
    $api_key = generateApiKey();
    $conn->query("UPDATE users SET api_key = '$api_key' WHERE id = $user_id");
    $user['api_key'] = $api_key;
}

// Get user's SMS statistics
$total_sms = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$total_sms_success = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE user_id = $user_id AND status = 1")->fetch_assoc()['count'] ?? 0;
$total_sms_today = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE user_id = $user_id AND DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$success_rate = $total_sms > 0 ? round(($total_sms_success / $total_sms) * 100, 2) : 0;

// Get SMS usage statistics for the last 30 days
$stats = getAPIUsageStats($user_id, 30);

// Prepare data for charts
$chart_labels = [];
$chart_sent = [];
$chart_failed = [];

foreach ($stats as $day) {
    $chart_labels[] = $day['date'];
    $chart_sent[] = $day['successful'];
    $chart_failed[] = $day['failed'];
}

// Convert to JSON for JavaScript
$chart_labels_json = json_encode($chart_labels);
$chart_sent_json = json_encode($chart_sent);
$chart_failed_json = json_encode($chart_failed);

// Get recent logs
$recent_logs_result = $conn->query("SELECT * FROM sms_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");
$recent_logs = [];
if ($recent_logs_result && $recent_logs_result->num_rows > 0) {
    while ($row = $recent_logs_result->fetch_assoc()) {
        $recent_logs[] = $row;
    }
}

// Get pricing tiers
$pricing = getSMSPricing();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API User Dashboard - SMS Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a class="nav-link active" href="dashboard.php">
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
                    <h1 class="h2">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="sms_test.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-vial me-2"></i>Test SMS
                            </a>
                            <a href="otp_test.php" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-key me-2"></i>Test OTP
                            </a>
                        </div>
                    </div>
                </div>

                <!-- API Key Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title">Your API Key</h5>
                            </div>
                            <div class="card-body">
                                <div class="api-key-container">
                                    <code><?php echo $user['api_key']; ?></code>
                                    <button class="btn btn-sm btn-outline-secondary copy-btn" data-copy="<?php echo $user['api_key']; ?>">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <p class="card-text text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    This key is used to authenticate your API requests. Keep it confidential and secure.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="stats-label">SMS Balance</h6>
                                        <p class="stats-number"><?php echo number_format($user['sms_balance']); ?></p>
                                    </div>
                                    <i class="fas fa-coins stats-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="stats-label">Success Rate</h6>
                                        <p class="stats-number"><?php echo $success_rate; ?>%</p>
                                    </div>
                                    <i class="fas fa-check-circle stats-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="stats-label">Total SMS Sent</h6>
                                        <p class="stats-number"><?php echo number_format($total_sms); ?></p>
                                    </div>
                                    <i class="fas fa-paper-plane stats-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="stats-label">SMS Today</h6>
                                        <p class="stats-number"><?php echo number_format($total_sms_today); ?></p>
                                    </div>
                                    <i class="fas fa-calendar-day stats-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart and Recent Activities -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title">SMS Usage Statistics</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="smsChart" data-labels='<?php echo $chart_labels_json; ?>' data-sent='<?php echo $chart_sent_json; ?>' data-failed='<?php echo $chart_failed_json; ?>'></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title">Recent SMS Logs</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_logs as $log): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php if ($log['status']): ?>
                                                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                                    <?php else: ?>
                                                        <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                                    <?php endif; ?>
                                                    <?php echo substr(htmlspecialchars($log['phone']), 0, 12); ?>
                                                </h6>
                                                <small><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo substr(htmlspecialchars($log['message']), 0, 30) . (strlen($log['message']) > 30 ? '...' : ''); ?></p>
                                            <small>Provider: <?php echo ucfirst(htmlspecialchars($log['provider'])); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="p-3 text-center">
                                    <a href="logs.php" class="btn btn-sm btn-outline-primary">View All Logs</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="card-title">SMS Pricing</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($pricing as $price): ?>
                                        <div class="col-md-3 mb-4">
                                            <div class="card h-100 <?php echo $price['name'] == 'Premium' ? 'border-primary' : ''; ?>">
                                                <div class="card-header text-center <?php echo $price['name'] == 'Premium' ? 'bg-primary text-white' : ''; ?>">
                                                    <h5 class="my-0 fw-bold"><?php echo htmlspecialchars($price['name']); ?></h5>
                                                </div>
                                                <div class="card-body text-center">
                                                    <h1 class="card-title pricing-card-title">
                                                        $<?php echo htmlspecialchars($price['price']); ?>
                                                    </h1>
                                                    <ul class="list-unstyled mt-3 mb-4">
                                                        <li><strong><?php echo number_format($price['sms_count']); ?></strong> SMS</li>
                                                        <li><?php echo htmlspecialchars($price['description']); ?></li>
                                                    </ul>
                                                    <button type="button" class="w-100 btn <?php echo $price['name'] == 'Premium' ? 'btn-primary' : 'btn-outline-primary'; ?>" 
                                                        onclick="window.location.href='payment.php?plan_id=<?php echo $price['id']; ?>&amount=<?php echo $price['price']; ?>'">
                                                        Purchase
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
    <script src="../assets/js/main.js"></script>
</body>

</html>