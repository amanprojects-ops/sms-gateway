<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Get statistics
$total_sms = $conn->query("SELECT COUNT(*) as count FROM sms_logs")->fetch_assoc()['count'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'api_user'")->fetch_assoc()['count'] ?? 0;
$total_sms_today = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$total_sms_success = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE status = 1")->fetch_assoc()['count'] ?? 0;
$success_rate = $total_sms > 0 ? round(($total_sms_success / $total_sms) * 100, 2) : 0;

// Get active provider
$active_provider = $conn->query("SELECT value FROM api_settings WHERE name = 'active_provider'")->fetch_assoc()['value'] ?? 'primary';

// Get API providers
$providers_result = $conn->query("SELECT * FROM api_providers ORDER BY priority ASC");
$providers = [];
if ($providers_result && $providers_result->num_rows > 0) {
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
    }
}

// Get SMS usage statistics for the last 30 days
$stats = getAPIUsageStats(null, 30);

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

// Get recent SMS logs
$recent_logs_result = $conn->query("SELECT l.*, u.username FROM sms_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");
$recent_logs = [];
if ($recent_logs_result && $recent_logs_result->num_rows > 0) {
    while ($row = $recent_logs_result->fetch_assoc()) {
        $recent_logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SMS Gateway</title>
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
                    <p class="mb-0">Admin Dashboard</p>
                </div>
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> API Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sms_logs.php">
                                <i class="fas fa-history"></i> SMS Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pricing.php">
                                <i class="fas fa-tags"></i> Pricing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="api_providers.php">
                                <i class="fas fa-cogs"></i> API Providers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payment_gateways.php">
                                <i class="fas fa-book"></i> Payment Gateways
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../api/docs.php">
                                <i class="fas fa-book"></i> API Documentation
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-group me-2">
                        <a href="api_settings.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-cogs me-2"></i>API Settings
                        </a>
                        <a href="users.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-user-plus me-2"></i>New API User
                        </a>
                    </div>
                </div>