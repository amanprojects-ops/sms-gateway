<?php
// Start session
session_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user is admin
if ($_SESSION['is_admin'] != 1) {
    header('Location: ../api/dashboard.php');
    exit;
}

// Get period from URL query (default to 'all')
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$validPeriods = ['all', 'today', 'week', 'month'];
if (!in_array($period, $validPeriods)) {
    $period = 'all';
}

// Get download statistics
$downloadStats = getSDKDownloadStats($conn, $period);

// Get libraries statistics
$stats = [
    'php' => [
        'filename' => 'sms-gateway-php-sdk.php',
        'size' => filesize('../assets/download/sms-gateway-php-sdk.php'),
        'last_modified' => filemtime('../assets/download/sms-gateway-php-sdk.php'),
        'downloads' => $downloadStats['by_type']['php'] ?? 0
    ],
    'node' => [
        'filename' => 'sms-gateway-node-sdk.js',
        'size' => filesize('../assets/download/sms-gateway-node-sdk.js'),
        'last_modified' => filemtime('../assets/download/sms-gateway-node-sdk.js'),
        'downloads' => $downloadStats['by_type']['node'] ?? 0
    ],
    'python' => [
        'filename' => 'sms_gateway_python_sdk.py',
        'size' => filesize('../assets/download/sms_gateway_python_sdk.py'),
        'last_modified' => filemtime('../assets/download/sms_gateway_python_sdk.py'),
        'downloads' => $downloadStats['by_type']['python'] ?? 0
    ]
];

// Function to format file size
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Libraries - Admin Dashboard</title>
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
                        <h3 class="text-white">Admin Panel</h3>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="providers.php">
                                <i class="fas fa-server me-2"></i>SMS Providers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pricing.php">
                                <i class="fas fa-tag me-2"></i>Pricing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="transactions.php">
                                <i class="fas fa-money-bill-wave me-2"></i>Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sms_logs.php">
                                <i class="fas fa-history me-2"></i>SMS Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="client_libraries.php">
                                <i class="fas fa-code me-2"></i>Client Libraries
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
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
                    <h1 class="h2">Client Libraries</h1>
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
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">SDK Overview</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLibraryModal">
                                    <i class="fas fa-plus me-1"></i> Add New Library
                                </button>
                            </div>
                            <div class="card-body">
                                <p>These client libraries/SDKs help your users integrate with your SMS Gateway API more easily.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Download Statistics</h5>
                                <div class="btn-group">
                                    <a href="?period=today" class="btn btn-sm btn-<?php echo $period == 'today' ? 'primary' : 'outline-primary'; ?>">Today</a>
                                    <a href="?period=week" class="btn btn-sm btn-<?php echo $period == 'week' ? 'primary' : 'outline-primary'; ?>">This Week</a>
                                    <a href="?period=month" class="btn btn-sm btn-<?php echo $period == 'month' ? 'primary' : 'outline-primary'; ?>">This Month</a>
                                    <a href="?period=all" class="btn btn-sm btn-<?php echo $period == 'all' ? 'primary' : 'outline-primary'; ?>">All Time</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Total Downloads</h5>
                                                <h2 class="display-4"><?php echo $downloadStats['total']; ?></h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Unique Users</h5>
                                                <h2 class="display-4"><?php echo $downloadStats['unique_users']; ?></h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Most Popular</h5>
                                                <h2 class="display-4">
                                                    <?php 
                                                    $max = 0;
                                                    $popular = 'N/A';
                                                    foreach ($downloadStats['by_type'] as $type => $count) {
                                                        if ($count > $max) {
                                                            $max = $count;
                                                            $popular = ucfirst($type);
                                                        }
                                                    }
                                                    echo $popular;
                                                    ?>
                                                </h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card bg-warning text-dark">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Download Rate</h5>
                                                <h2 class="display-4">
                                                    <?php 
                                                    // Calculate average daily downloads for the current period
                                                    $period = $_GET['period'] ?? 'all';
                                                    $days = 1;
                                                    switch ($period) {
                                                        case 'week': $days = 7; break;
                                                        case 'month': $days = 30; break;
                                                        case 'all': $days = count($downloadStats['by_date']) ?: 1; break;
                                                    }
                                                    echo round($downloadStats['total'] / $days, 1);
                                                    ?>
                                                    <small>/day</small>
                                                </h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (count($downloadStats['by_date']) > 0): ?>
                                <div class="row mt-4">
                                    <div class="col-md-8">
                                        <h5>Download Trends</h5>
                                        <canvas id="downloadsChart" height="100"></canvas>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>SDK Comparison</h5>
                                        <canvas id="sdkComparisonChart" height="150"></canvas>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Available Libraries</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Language</th>
                                                <th>Filename</th>
                                                <th>Size</th>
                                                <th>Last Modified</th>
                                                <th>Downloads</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fab fa-php me-1"></i> PHP</td>
                                                <td><?= $stats['php']['filename'] ?></td>
                                                <td><?= formatFileSize($stats['php']['size']) ?></td>
                                                <td><?= date('Y-m-d H:i:s', $stats['php']['last_modified']) ?></td>
                                                <td><?= $stats['php']['downloads'] ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editLibraryModal" data-library="php">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="../api/download.php?file=php" class="btn btn-sm btn-info">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="fab fa-node-js me-1"></i> Node.js</td>
                                                <td><?= $stats['node']['filename'] ?></td>
                                                <td><?= formatFileSize($stats['node']['size']) ?></td>
                                                <td><?= date('Y-m-d H:i:s', $stats['node']['last_modified']) ?></td>
                                                <td><?= $stats['node']['downloads'] ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editLibraryModal" data-library="node">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="../api/download.php?file=node" class="btn btn-sm btn-info">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="fab fa-python me-1"></i> Python</td>
                                                <td><?= $stats['python']['filename'] ?></td>
                                                <td><?= formatFileSize($stats['python']['size']) ?></td>
                                                <td><?= date('Y-m-d H:i:s', $stats['python']['last_modified']) ?></td>
                                                <td><?= $stats['python']['downloads'] ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editLibraryModal" data-library="python">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="../api/download.php?file=python" class="btn btn-sm btn-info">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Documentation Status</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        PHP SDK Documentation
                                        <span class="badge bg-success">Complete</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Node.js SDK Documentation
                                        <span class="badge bg-success">Complete</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Python SDK Documentation
                                        <span class="badge bg-success">Complete</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Java SDK Documentation
                                        <span class="badge bg-warning">Pending</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        C# SDK Documentation
                                        <span class="badge bg-warning">Pending</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Future Development</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Java SDK</h6>
                                            <small class="text-muted">Planned for Q3 2023</small>
                                        </div>
                                        <p class="mb-1">Implement Java SDK for enterprise customers</p>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">C# .NET SDK</h6>
                                            <small class="text-muted">Planned for Q3 2023</small>
                                        </div>
                                        <p class="mb-1">Implement C# SDK for Windows-based applications</p>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Go SDK</h6>
                                            <small class="text-muted">Planned for Q4 2023</small>
                                        </div>
                                        <p class="mb-1">Implement Go SDK for cloud-native applications</p>
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">Ruby SDK</h6>
                                            <small class="text-muted">Planned for Q4 2023</small>
                                        </div>
                                        <p class="mb-1">Implement Ruby SDK for Ruby on Rails applications</p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Library Modal -->
    <div class="modal fade" id="addLibraryModal" tabindex="-1" aria-labelledby="addLibraryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLibraryModalLabel">Add New Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="process_library.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="libraryName" class="form-label">Library Name</label>
                            <input type="text" class="form-control" id="libraryName" name="library_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="language" class="form-label">Programming Language</label>
                            <select class="form-select" id="language" name="language" required>
                                <option value="">Select Language</option>
                                <option value="php">PHP</option>
                                <option value="node">Node.js</option>
                                <option value="python">Python</option>
                                <option value="java">Java</option>
                                <option value="csharp">C#</option>
                                <option value="ruby">Ruby</option>
                                <option value="go">Go</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="version" class="form-label">Version</label>
                            <input type="text" class="form-control" id="version" name="version" placeholder="1.0.0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="libraryFile" class="form-label">Library File</label>
                            <input type="file" class="form-control" id="libraryFile" name="library_file" required>
                            <div class="form-text">Upload the SDK file</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="documentation" class="form-label">Documentation</label>
                            <textarea class="form-control" id="documentation" name="documentation" rows="10"></textarea>
                            <div class="form-text">Usage examples and documentation (HTML/Markdown supported)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addLibraryForm" class="btn btn-primary">Add Library</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Library Modal -->
    <div class="modal fade" id="editLibraryModal" tabindex="-1" aria-labelledby="editLibraryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLibraryModalLabel">Edit Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editLibraryForm" action="process_library.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="library_id" id="editLibraryId">
                        
                        <div class="mb-3">
                            <label for="editLibraryName" class="form-label">Library Name</label>
                            <input type="text" class="form-control" id="editLibraryName" name="library_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editLanguage" class="form-label">Programming Language</label>
                            <select class="form-select" id="editLanguage" name="language" required>
                                <option value="">Select Language</option>
                                <option value="php">PHP</option>
                                <option value="node">Node.js</option>
                                <option value="python">Python</option>
                                <option value="java">Java</option>
                                <option value="csharp">C#</option>
                                <option value="ruby">Ruby</option>
                                <option value="go">Go</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editVersion" class="form-label">Version</label>
                            <input type="text" class="form-control" id="editVersion" name="version" placeholder="1.0.0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editLibraryFile" class="form-label">Library File (leave empty to keep current)</label>
                            <input type="file" class="form-control" id="editLibraryFile" name="library_file">
                            <div class="form-text">Upload a new version of the SDK file</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDocumentation" class="form-label">Documentation</label>
                            <textarea class="form-control" id="editDocumentation" name="documentation" rows="10"></textarea>
                            <div class="form-text">Usage examples and documentation (HTML/Markdown supported)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editLibraryForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // Edit Library Modal - Set fields based on library type
        const editLibraryModal = document.getElementById('editLibraryModal');
        if (editLibraryModal) {
            editLibraryModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const libraryType = button.getAttribute('data-library');
                
                const libraryData = {
                    'php': {
                        name: 'PHP SDK',
                        language: 'php',
                        version: '1.0.0',
                        description: 'Simple PHP library for integrating with our SMS and OTP APIs.'
                    },
                    'node': {
                        name: 'Node.js SDK',
                        language: 'node',
                        version: '1.0.0',
                        description: 'Node.js module for easy API integration with promise-based methods.'
                    },
                    'python': {
                        name: 'Python SDK',
                        language: 'python',
                        version: '1.0.0',
                        description: 'Python library for integrating with our SMS Gateway API.'
                    }
                };
                
                if (libraryData[libraryType]) {
                    const data = libraryData[libraryType];
                    document.getElementById('editLibraryId').value = libraryType;
                    document.getElementById('editLibraryName').value = data.name;
                    document.getElementById('editLanguage').value = data.language;
                    document.getElementById('editVersion').value = data.version;
                    document.getElementById('editDescription').value = data.description;
                }
            });
        }

        // Initialize Downloads Chart
        const downloadsChartEl = document.getElementById('downloadsChart');
        if (downloadsChartEl) {
            <?php
            // Prepare data for the chart
            $chartLabels = array_keys($downloadStats['by_date']);
            $chartData = array_values($downloadStats['by_date']);
            $chartColors = [];
            
            // Set colors for each SDK type
            $sdkColors = [
                'php' => '#8892BF',      // PHP blue
                'node' => '#68A063',     // Node.js green
                'python' => '#3776AB',   // Python blue
                'java' => '#007396',     // Java blue
                'other' => '#6C757D'     // Gray for others
            ];

            // Create datasets for each SDK type if available
            $typeDatasets = [];
            foreach ($downloadStats['by_type'] as $type => $count) {
                if ($count > 0) {
                    $typeDatasets[] = [
                        'label' => ucfirst($type),
                        'backgroundColor' => $sdkColors[$type] ?? '#6C757D',
                        'borderColor' => $sdkColors[$type] ?? '#6C757D',
                        'data' => [$count]
                    ];
                }
            }
            ?>

            // Line chart for downloads by date
            new Chart(downloadsChartEl, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Downloads',
                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        data: <?php echo json_encode($chartData); ?>,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Downloads Over Time'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Downloads'
                            }
                        }
                    }
                }
            });

            // Create a horizontal bar chart for SDK type comparison
            if (document.getElementById('sdkComparisonChart')) {
                new Chart(document.getElementById('sdkComparisonChart'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_map('ucfirst', array_keys($downloadStats['by_type']))); ?>,
                        datasets: [{
                            label: 'Downloads by SDK Type',
                            backgroundColor: Object.values(<?php echo json_encode($sdkColors); ?>).slice(0, <?php echo count($downloadStats['by_type']); ?>),
                            data: <?php echo json_encode(array_values($downloadStats['by_type'])); ?>
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        plugins: {
                            title: {
                                display: true,
                                text: 'Downloads by SDK Type'
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Downloads'
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html> 