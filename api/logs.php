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

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions
$conditions = ["user_id = $user_id"];
$params = [];

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $conditions[] = "(phone LIKE '%$search%' OR message LIKE '%$search%')";
}

if (!empty($status)) {
    $status = $conn->real_escape_string($status);
    $conditions[] = "status = '$status'";
}

if (!empty($date_from)) {
    $date_from = $conn->real_escape_string($date_from);
    $conditions[] = "sent_at >= '$date_from 00:00:00'";
}

if (!empty($date_to)) {
    $date_to = $conn->real_escape_string($date_to);
    $conditions[] = "sent_at <= '$date_to 23:59:59'";
}

$where_clause = implode(' AND ', $conditions);

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM sms_logs WHERE $where_clause";
$count_result = $conn->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Get logs with pagination
$logs_query = "SELECT * FROM sms_logs WHERE $where_clause ORDER BY sent_at DESC LIMIT $offset, $limit";
$logs_result = $conn->query($logs_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Logs - SMS Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
                            <a class="nav-link active" href="logs.php">
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
                    <h1 class="h2">SMS Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                                <i class="fas fa-download me-2"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Search Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="logs.php" class="row">
                            <div class="col-md-3 mb-3">
                                <label for="search" class="form-label">Search (Phone or Message)</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="" <?php echo empty($status) ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="text" class="form-control date-picker" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="text" class="form-control date-picker" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-12 text-end">
                                <a href="logs.php" class="btn btn-outline-secondary me-2">Clear Filters</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Phone Number</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Provider</th>
                                        <th>Sent At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs_result->num_rows > 0): ?>
                                        <?php while ($log = $logs_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $log['id']; ?></td>
                                                <td><?php echo htmlspecialchars($log['phone']); ?></td>
                                                <td>
                                                    <?php
                                                    $message = htmlspecialchars($log['message']);
                                                    echo (strlen($message) > 50) ? substr($message, 0, 50) . '...' : $message;
                                                    ?>
                                                    <a href="#" class="view-message" data-message="<?php echo htmlspecialchars($log['message']); ?>">
                                                        <i class="fas fa-eye ms-2"></i>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo match ($log['status']) {
                                                        '1' => 'success',
                                                        '0' => 'warning'
                                                    }; ?>">
                                                    <?php echo $log['status'] == 1 ? 'Sent' : 'Failed';?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['provider_name']); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['sent_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary resend-sms" data-id="<?php echo $log['id']; ?>" data-phone="<?php echo htmlspecialchars($log['phone']); ?>" title="Resend">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details" data-id="<?php echo $log['id']; ?>" title="View Details">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No SMS logs found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&status=' . urlencode($status) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $active = ($i == $page) ? 'active' : '';
                                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) . '">' . $i . '</a></li>';
                                    }

                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '&status=' . urlencode($status) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to) . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>

                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                        <div class="mt-3 text-center text-muted">
                            Showing <?php echo min($offset + 1, $total_records); ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
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

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">SMS Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="messageContent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">SMS Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="detailsContent" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="fw-bold">Message ID:</label>
                                    <p id="details-id"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Phone Number:</label>
                                    <p id="details-phone"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Status:</label>
                                    <p id="details-status"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Sent At:</label>
                                    <p id="details-sent-at"></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="fw-bold">Provider:</label>
                                    <p id="details-provider"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Provider Response:</label>
                                    <p id="details-provider-response"></p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Delivery Status Updated:</label>
                                    <p id="details-updated-at"></p>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Message:</label>
                            <p id="details-message"></p>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Provider Message ID:</label>
                            <p id="details-provider-message-id"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resend SMS Modal -->
    <div class="modal fade" id="resendModal" tabindex="-1" aria-labelledby="resendModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resendModalLabel">Resend SMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to resend this SMS to <span id="resendPhone"></span>?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will use additional SMS credits from your account.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmResend">Yes, Resend</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            flatpickr(".date-picker", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",
                allowInput: true
            });

            // View message modal
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            document.querySelectorAll('.view-message').forEach(item => {
                item.addEventListener('click', event => {
                    event.preventDefault();
                    const message = item.getAttribute('data-message');
                    document.getElementById('messageContent').innerText = message;
                    messageModal.show();
                });
            });

            // View details modal
            const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
            document.querySelectorAll('.view-details').forEach(item => {
                item.addEventListener('click', event => {
                    event.preventDefault();
                    const id = item.getAttribute('data-id');

                    // Reset and show loading spinner
                    document.getElementById('detailsContent').classList.add('d-none');
                    const spinner = detailsModal._element.querySelector('.spinner-border').parentNode;
                    spinner.classList.remove('d-none');

                    detailsModal.show();

                    // Fetch details via AJAX
                    fetch(`get_sms_details.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('details-id').innerText = data.details.id;
                                document.getElementById('details-phone').innerText = data.details.phone_number;
                                document.getElementById('details-status').innerHTML = `<span class="badge bg-${data.details.status === 'delivered' ? 'success' : (data.details.status === 'failed' ? 'danger' : 'warning')}">${data.details.status.charAt(0).toUpperCase() + data.details.status.slice(1)}</span>`;
                                document.getElementById('details-sent-at').innerText = data.details.sent_at;
                                document.getElementById('details-provider').innerText = data.details.provider;
                                document.getElementById('details-provider-response').innerText = data.details.provider_response || 'N/A';
                                document.getElementById('details-updated-at').innerText = data.details.updated_at || 'N/A';
                                document.getElementById('details-message').innerText = data.details.message;
                                document.getElementById('details-provider-message-id').innerText = data.details.provider_message_id || 'N/A';

                                // Hide spinner and show content
                                spinner.classList.add('d-none');
                                document.getElementById('detailsContent').classList.remove('d-none');
                            } else {
                                alert('Failed to load SMS details. Please try again.');
                                detailsModal.hide();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while fetching SMS details.');
                            detailsModal.hide();
                        });
                });
            });

            // Resend SMS modal
            const resendModal = new bootstrap.Modal(document.getElementById('resendModal'));
            let currentSmsId = null;

            document.querySelectorAll('.resend-sms').forEach(item => {
                item.addEventListener('click', event => {
                    event.preventDefault();
                    currentSmsId = item.getAttribute('data-id');
                    const phone = item.getAttribute('data-phone');
                    document.getElementById('resendPhone').innerText = phone;
                    resendModal.show();
                });
            });

            document.getElementById('confirmResend').addEventListener('click', () => {
                if (currentSmsId) {
                    // Disable button and show loading
                    const button = document.getElementById('confirmResend');
                    const originalText = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

                    // Send the request
                    fetch('resend_sms.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${currentSmsId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            resendModal.hide();
                            if (data.success) {
                                alert('SMS has been resent successfully!');
                                location.reload(); // Reload the page to show updated status
                            } else {
                                alert('Failed to resend SMS: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while trying to resend the SMS.');
                        })
                        .finally(() => {
                            // Restore button state
                            button.disabled = false;
                            button.innerHTML = originalText;
                        });
                }
            });

            // Export CSV
            document.getElementById('exportBtn').addEventListener('click', () => {
                window.location.href = `export_logs.php?search=${encodeURIComponent('<?php echo $search; ?>')}&status=${encodeURIComponent('<?php echo $status; ?>')}&date_from=${encodeURIComponent('<?php echo $date_from; ?>')}&date_to=${encodeURIComponent('<?php echo $date_to; ?>')}`;
            });
        });
    </script>
</body>

</html>