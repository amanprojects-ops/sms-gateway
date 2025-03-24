<?php include 'inc/header.php'; ?>


<main class="col-md-12 py-4">
    <h1 class="h2 mb-4">Payment Gateways Management</h1>

    <?php
    // Function to display success/error messages
    function displayMessage($message, $type = 'success')
    {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    // Initialize message variable
    $message = '';

    // Handle form submissions
    if (isset($_POST['add_gateway'])) {
        // Get form data for new gateway
        $name = $_POST['name'];
        $api_key = $_POST['api_key'];
        $api_secret = $_POST['api_secret'];
        $webhook_url = $_POST['webhook_url'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_backup = isset($_POST['is_backup']) ? 1 : 0;
        $priority = (int)$_POST['priority'];

        // Insert new gateway
        $stmt = $conn->prepare("INSERT INTO payment_gateways (name, api_key, api_secret, webhook_url, is_active, is_backup, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssiis", $name, $api_key, $api_secret, $webhook_url, $is_active, $is_backup, $priority);

        if ($stmt->execute()) {
            $message = displayMessage("New payment gateway added successfully");
        } else {
            $message = displayMessage("Error adding payment gateway: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle gateway update
    if (isset($_POST['update_gateway'])) {
        $id = $_POST['gateway_id'];
        $name = $_POST['name'];
        $api_key = $_POST['api_key'];
        $api_secret = $_POST['api_secret'];
        $webhook_url = $_POST['webhook_url'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_backup = isset($_POST['is_backup']) ? 1 : 0;
        $priority = (int)$_POST['priority'];
        $success_rate = (float)$_POST['success_rate'];

        $stmt = $conn->prepare("UPDATE payment_gateways SET name = ?, api_key = ?, api_secret = ?, webhook_url = ?, is_active = ?, is_backup = ?, priority = ?, success_rate = ? WHERE id = ?");
        $stmt->bind_param("ssssiiddi", $name, $api_key, $api_secret, $webhook_url, $is_active, $is_backup, $priority, $success_rate, $id);

        if ($stmt->execute()) {
            $message = displayMessage("Payment gateway updated successfully");
        } else {
            $message = displayMessage("Error updating payment gateway: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle gateway deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        
        $stmt = $conn->prepare("DELETE FROM payment_gateways WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = displayMessage("Payment gateway deleted successfully");
        } else {
            $message = displayMessage("Error deleting payment gateway: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle gateway activation/deactivation
    if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
        $id = (int)$_GET['toggle'];
        
        // Get current status
        $stmt = $conn->prepare("SELECT is_active FROM payment_gateways WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $gateway = $result->fetch_assoc();
        $stmt->close();
        
        // Toggle status
        $new_status = $gateway['is_active'] ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE payment_gateways SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        
        if ($stmt->execute()) {
            $status_text = $new_status ? "activated" : "deactivated";
            $message = displayMessage("Payment gateway {$status_text} successfully");
        } else {
            $message = displayMessage("Error updating payment gateway status: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Fetch all payment gateways
    $sql = "SELECT * FROM payment_gateways ORDER BY priority ASC, name ASC";
    $result = $conn->query($sql);
    ?>

    <?php echo $message; ?>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Gateways</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGatewayModal">
                        <i class="fas fa-plus"></i> Add New Gateway
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Backup</th>
                                    <th>Priority</th>
                                    <th>Success Rate</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['is_backup'] ? 'warning' : 'info'; ?>">
                                                    <?php echo $row['is_backup'] ? 'Backup' : 'Primary'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['priority']; ?></td>
                                            <td><?php echo number_format($row['success_rate'], 2); ?>%</td>
                                            <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-<?php echo $row['is_active'] ? 'warning' : 'success'; ?>" title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-<?php echo $row['is_active'] ? 'toggle-off' : 'toggle-on'; ?>"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-primary edit-gateway" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editGatewayModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                        data-api-key="<?php echo htmlspecialchars($row['api_key']); ?>"
                                                        data-api-secret="<?php echo htmlspecialchars($row['api_secret']); ?>"
                                                        data-webhook-url="<?php echo htmlspecialchars($row['webhook_url']); ?>"
                                                        data-is-active="<?php echo $row['is_active']; ?>"
                                                        data-is-backup="<?php echo $row['is_backup']; ?>"
                                                        data-priority="<?php echo $row['priority']; ?>"
                                                        data-success-rate="<?php echo $row['success_rate']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this gateway?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No payment gateways found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Gateway Modal -->
    <div class="modal fade" id="addGatewayModal" tabindex="-1" aria-labelledby="addGatewayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGatewayModalLabel">Add New Payment Gateway</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Gateway Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">API Key</label>
                                    <input type="text" class="form-control" id="api_key" name="api_key" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="api_secret" class="form-label">API Secret</label>
                                    <input type="text" class="form-control" id="api_secret" name="api_secret" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="webhook_url" class="form-label">Webhook URL</label>
                                    <input type="text" class="form-control" id="webhook_url" name="webhook_url">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <input type="number" class="form-control" id="priority" name="priority" min="1" value="10">
                                    <small class="text-muted">Lower number = higher priority</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_backup" name="is_backup">
                                        <label class="form-check-label" for="is_backup">Backup Gateway</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_gateway" class="btn btn-primary">Add Gateway</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Gateway Modal -->
    <div class="modal fade" id="editGatewayModal" tabindex="-1" aria-labelledby="editGatewayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGatewayModalLabel">Edit Payment Gateway</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <input type="hidden" id="edit_gateway_id" name="gateway_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Gateway Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_api_key" class="form-label">API Key</label>
                                    <input type="text" class="form-control" id="edit_api_key" name="api_key" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_api_secret" class="form-label">API Secret</label>
                                    <input type="text" class="form-control" id="edit_api_secret" name="api_secret" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_webhook_url" class="form-label">Webhook URL</label>
                                    <input type="text" class="form-control" id="edit_webhook_url" name="webhook_url">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_priority" class="form-label">Priority</label>
                                    <input type="number" class="form-control" id="edit_priority" name="priority" min="1">
                                    <small class="text-muted">Lower number = higher priority</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_success_rate" class="form-label">Success Rate (%)</label>
                                    <input type="number" class="form-control" id="edit_success_rate" name="success_rate" min="0" max="100" step="0.01">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                        <label class="form-check-label" for="edit_is_active">Active</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_is_backup" name="is_backup">
                                        <label class="form-check-label" for="edit_is_backup">Backup Gateway</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_gateway" class="btn btn-primary">Update Gateway</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit gateway modal
        const editButtons = document.querySelectorAll('.edit-gateway');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const apiKey = this.getAttribute('data-api-key');
                const apiSecret = this.getAttribute('data-api-secret');
                const webhookUrl = this.getAttribute('data-webhook-url');
                const isActive = this.getAttribute('data-is-active') === '1';
                const isBackup = this.getAttribute('data-is-backup') === '1';
                const priority = this.getAttribute('data-priority');
                const successRate = this.getAttribute('data-success-rate');
                
                document.getElementById('edit_gateway_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_api_key').value = apiKey;
                document.getElementById('edit_api_secret').value = apiSecret;
                document.getElementById('edit_webhook_url').value = webhookUrl;
                document.getElementById('edit_is_active').checked = isActive;
                document.getElementById('edit_is_backup').checked = isBackup;
                document.getElementById('edit_priority').value = priority;
                document.getElementById('edit_success_rate').value = successRate;
            });
        });
    });
</script>



<?php include 'inc/footer.php'; ?>
