<?php include './inc/header.php'; ?>

<main class="col-md-12 py-4">
    <h1 class="h2 mb-4">SMS Logs</h1>

    <?php
    // Function to display success/error messages
    function displayMessage($message, $type = 'success')
    {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    // Initialize message variable
    $message = '';

    // Check if logging is enabled
    $query = "SELECT value FROM api_settings WHERE name = 'enable_logging'";
    $result = $conn->query($query);
    $logging_enabled = false;
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $logging_enabled = (bool)$row['value'];
    }

    // Handle log deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM sms_logs WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = displayMessage("Log entry deleted successfully");
        } else {
            $message = displayMessage("Error deleting log entry: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle bulk deletion
    if (isset($_POST['bulk_delete']) && isset($_POST['selected_logs'])) {
        $selected = $_POST['selected_logs'];
        $ids = implode(',', array_map('intval', $selected));
        
        $query = "DELETE FROM sms_logs WHERE id IN ($ids)";
        if ($conn->query($query)) {
            $message = displayMessage(count($selected) . " log entries deleted successfully");
        } else {
            $message = displayMessage("Error deleting log entries: " . $conn->error, "danger");
        }
    }

    // Pagination setup
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Search functionality
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $search_condition = '';
    if (!empty($search)) {
        $search_condition = " WHERE phone_number LIKE '%$search%' OR message LIKE '%$search%' OR status LIKE '%$search%'";
    }

    // Get total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM sms_logs" . $search_condition;
    $count_result = $conn->query($count_query);
    $total_records = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);

    // Get logs with pagination
    $query = "SELECT * FROM sms_logs" . $search_condition . " ORDER BY created_at DESC LIMIT $offset, $limit";
    $result = $conn->query($query);
    ?>

    <?php echo $message; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>SMS Logs</h5>
            <div>
                <?php if (!$logging_enabled): ?>
                    <div class="alert alert-warning py-1 px-3 mb-0">
                        <small><i class="fas fa-exclamation-triangle me-1"></i>SMS Logging is currently disabled. Enable it in <a href="api_settings.php">API Settings</a>.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="get" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search phone, message or status..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="sms_logs.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                        <i class="fas fa-trash-alt me-1"></i>Delete Selected
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <form method="post" id="logsForm">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Phone Number</th>
                                <th>Message</th>
                                <th>Provider Name</th>
                                <th>Status</th>
                                <th>Sent At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input log-select" type="checkbox" name="selected_logs[]" value="<?php echo $row['id']; ?>">
                                            </div>
                                        </td>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td>
                                            <?php 
                                            $message = htmlspecialchars($row['message']);
                                            echo (strlen($message) > 50) ? substr($message, 0, 50) . '...' : $message; 
                                            ?>
                                            <button type="button" class="btn btn-sm btn-link view-message" data-bs-toggle="modal" data-bs-target="#messageModal" data-message="<?php echo htmlspecialchars($row['message']); ?>">
                                                View
                                            </button>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['provider_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($row['status'] == '1') ? 'success' : (($row['status'] == '0') ? 'info' : ''); ?>">
                                                <?php echo ucfirst(htmlspecialchars(($row['status']) == 1) ? "Send" : 'failed'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i:s', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this log entry?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No SMS logs found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="bulk_delete" value="1">
            </form>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all checkbox
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.log-select');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const logsForm = document.getElementById('logsForm');

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkDeleteButton();
        });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkDeleteButton);
        });

        function updateBulkDeleteButton() {
            const selectedCount = document.querySelectorAll('.log-select:checked').length;
            bulkDeleteBtn.disabled = selectedCount === 0;
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash-alt me-1"></i>Delete Selected (${selectedCount})`;
        }

        bulkDeleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete the selected log entries?')) {
                logsForm.submit();
            }
        });

        // Handle view message modal
        const viewButtons = document.querySelectorAll('.view-message');
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const message = this.getAttribute('data-message');
                document.getElementById('messageContent').textContent = message;
            });
        });
    });
</script>

<?php include './inc/footer.php'; ?>