<?php
// Start session
session_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if user is admin (redirect to admin dashboard if true)
if ($_SESSION['is_admin'] == 1) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Get user information
$user = getUserById($conn, $_SESSION['user_id']);

// Handle download requests
if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $allowed_files = [
        'php' => [
            'path' => '../assets/download/sms-gateway-php-sdk.php',
            'name' => 'PHP SDK'
        ],
        'node' => [
            'path' => '../assets/download/sms-gateway-node-sdk.js',
            'name' => 'Node.js SDK'
        ],
        'python' => [
            'path' => '../assets/download/sms_gateway_python_sdk.py',
            'name' => 'Python SDK'
        ],
        'java' => [
            'path' => '../assets/download/SMSGatewayJavaSDK.java',
            'name' => 'Java SDK'
        ]
    ];
    
    if (array_key_exists($file, $allowed_files) && file_exists($allowed_files[$file]['path'])) {
        $filepath = $allowed_files[$file]['path'];
        $filename = basename($filepath);
        
        // Log the download in the database
        $stmt = $conn->prepare("INSERT INTO sdk_downloads (user_id, sdk_type, downloaded_at, ip_address) VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("iss", $_SESSION['user_id'], $file, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        
        // Set appropriate headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Read and output file
        readfile($filepath);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDK Downloads - SMS Gateway</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
                <div class="position-sticky">
                    <div class="sidebar-header p-3">
                        <h3 class="text-white">SMS Gateway</h3>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sms_test.php">
                                <i class="fas fa-sms me-2"></i>SMS Test
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="otp_test.php">
                                <i class="fas fa-key me-2"></i>OTP Test
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-history me-2"></i>SMS Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="download.php">
                                <i class="fas fa-download me-2"></i>SDK Downloads
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="docs.php">
                                <i class="fas fa-book me-2"></i>API Documentation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">SDK Downloads</h1>
                </div>

                <!-- Alert section -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">SDK Overview</h5>
                            </div>
                            <div class="card-body">
                                <p>Our SDKs provide a simple way to integrate with the SMS Gateway API. Choose the one that best fits your programming language and environment.</p>
                                <p>Each SDK includes functionality for:</p>
                                <ul>
                                    <li>Sending SMS messages</li>
                                    <li>Generating OTP codes</li>
                                    <li>Verifying OTP codes</li>
                                    <li>Error handling and debugging</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- PHP SDK -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fab fa-php me-2"></i>PHP SDK
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Version 1.0.0</h6>
                                <p class="card-text">Simple PHP library for integrating with our SMS and OTP APIs.</p>
                                <h6>Requirements:</h6>
                                <ul>
                                    <li>PHP 7.0 or higher</li>
                                    <li>curl extension</li>
                                </ul>
                                <h6>Features:</h6>
                                <ul>
                                    <li>Easy API authentication</li>
                                    <li>SMS sending</li>
                                    <li>OTP generation & verification</li>
                                    <li>Error handling</li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <a href="download.php?file=php" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Download
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Node.js SDK -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fab fa-node-js me-2"></i>Node.js SDK
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Version 1.0.0</h6>
                                <p class="card-text">Node.js module for easy API integration with promise-based methods.</p>
                                <h6>Requirements:</h6>
                                <ul>
                                    <li>Node.js 12.0 or higher</li>
                                    <li>axios package</li>
                                </ul>
                                <h6>Features:</h6>
                                <ul>
                                    <li>Promise-based API</li>
                                    <li>Async/await support</li>
                                    <li>Comprehensive error handling</li>
                                    <li>Detailed logging options</li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <a href="download.php?file=node" class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>Download
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Python SDK -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fab fa-python me-2"></i>Python SDK
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Version 1.0.0</h6>
                                <p class="card-text">Python library for integrating with our SMS Gateway API.</p>
                                <h6>Requirements:</h6>
                                <ul>
                                    <li>Python 3.6 or higher</li>
                                    <li>requests library</li>
                                </ul>
                                <h6>Features:</h6>
                                <ul>
                                    <li>Simple API client</li>
                                    <li>Exception handling</li>
                                    <li>Support for all API endpoints</li>
                                    <li>Detailed documentation</li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <a href="download.php?file=python" class="btn btn-info">
                                    <i class="fas fa-download me-2"></i>Download
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Java SDK -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fab fa-java me-2"></i>Java SDK
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Version 1.0.0</h6>
                                <p class="card-text">Java library for enterprise applications to integrate with our API.</p>
                                <h6>Requirements:</h6>
                                <ul>
                                    <li>Java 11 or higher</li>
                                    <li>org.json library</li>
                                </ul>
                                <h6>Features:</h6>
                                <ul>
                                    <li>Object-oriented API</li>
                                    <li>Exception handling</li>
                                    <li>Type safety</li>
                                    <li>Javadoc documentation</li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <a href="download.php?file=java" class="btn btn-danger">
                                    <i class="fas fa-download me-2"></i>Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">SDK Usage Information</h5>
                            </div>
                            <div class="card-body">
                                <p>Before using any of our SDKs, please make sure you have:</p>
                                <ol>
                                    <li>Created an account and logged in</li>
                                    <li>Generated an API key in your <a href="profile.php">profile</a></li>
                                    <li>Have sufficient SMS credits in your account</li>
                                </ol>
                                <p>Each SDK file includes detailed usage examples to help you get started quickly. For more information, please refer to our <a href="docs.php">API Documentation</a>.</p>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Need help?</strong> If you have any questions or encounter issues with our SDKs, please <a href="#">contact our support team</a>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Coming Soon</h5>
                            </div>
                            <div class="card-body">
                                <p>We're working on additional SDKs for more languages and platforms:</p>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="fab fa-windows fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">C# .NET SDK</h6>
                                                <small class="text-muted">Coming Q3 2023</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-gem fa-2x text-danger"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">Ruby SDK</h6>
                                                <small class="text-muted">Coming Q4 2023</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-feather fa-2x text-info"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0">Go SDK</h6>
                                                <small class="text-muted">Coming Q4 2023</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-3">Want us to prioritize a specific language? <a href="#">Let us know!</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; 2023 SMS Gateway. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white">Privacy Policy</a> | 
                    <a href="#" class="text-white">Terms of Use</a> | 
                    <a href="#" class="text-white">Support</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html> 