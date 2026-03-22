<?php include 'inc/header.php'; ?>
<main class="col-md-12 py-4">
    <h1 class="h2 mb-4">API Providers Management</h1>

    <?php
    // Function to display success/error messages
    function displayMessage($message, $type = 'success')
    {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    // Initialize message variable
    $message = '';

    // Handle form submissions
    if (isset($_POST['add_provider'])) {
        // Get form data for new provider
        $name = $_POST['name'];
        $api_url = $_POST['api_url'];
        $api_key = $_POST['api_key'];
        $api_secret = $_POST['api_secret'];
        $method = $_POST['method'] ?? 'GET';
        $headers = $_POST['headers'] ?? '';
        $post_data = $_POST['post_data'] ?? '';
        $success_keyword = $_POST['success_keyword'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_backup = isset($_POST['is_backup']) ? 1 : 0;
        $priority = (int)$_POST['priority'];

        // Insert new provider
        $stmt = $conn->prepare("INSERT INTO api_providers (name, api_url, api_key, api_secret, method, headers, post_data, success_keyword, is_active, is_backup, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssssiii", $name, $api_url, $api_key, $api_secret, $method, $headers, $post_data, $success_keyword, $is_active, $is_backup, $priority);

        if ($stmt->execute()) {
            $message = displayMessage("New API provider added successfully");
        } else {
            $message = displayMessage("Error adding API provider: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle provider update
    if (isset($_POST['update_provider'])) {
        $id = $_POST['provider_id'];
        $name = $_POST['name'];
        $api_url = $_POST['api_url'];
        $api_key = $_POST['api_key'];
        $api_secret = $_POST['api_secret'];
        $method = $_POST['method'] ?? 'GET';
        $headers = $_POST['headers'] ?? '';
        $post_data = $_POST['post_data'] ?? '';
        $success_keyword = $_POST['success_keyword'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_backup = isset($_POST['is_backup']) ? 1 : 0;
        $priority = (int)$_POST['priority'];
        $success_rate = (float)$_POST['success_rate'];

        $stmt = $conn->prepare("UPDATE api_providers SET name = ?, api_url = ?, api_key = ?, api_secret = ?, method = ?, headers = ?, post_data = ?, success_keyword = ?, is_active = ?, is_backup = ?, priority = ?, success_rate = ? WHERE id = ?");
        $stmt->bind_param("ssssssssiiidi", $name, $api_url, $api_key, $api_secret, $method, $headers, $post_data, $success_keyword, $is_active, $is_backup, $priority, $success_rate, $id);

        if ($stmt->execute()) {
            $message = displayMessage("API provider updated successfully");
        } else {
            $message = displayMessage("Error updating API provider: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle provider deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM api_providers WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = displayMessage("API provider deleted successfully");
        } else {
            $message = displayMessage("Error deleting API provider: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle provider status toggle
    if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
        $id = $_GET['toggle'];
        
        // Get current status
        $stmt = $conn->prepare("SELECT is_active FROM api_providers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $provider = $result->fetch_assoc();
        $stmt->close();
        
        // Toggle status
        $new_status = $provider['is_active'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE api_providers SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        
        if ($stmt->execute()) {
            $status_text = $new_status ? "activated" : "deactivated";
            $message = displayMessage("API provider {$status_text} successfully");
        } else {
            $message = displayMessage("Error toggling API provider status: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Get all API providers
    $query = "SELECT * FROM api_providers ORDER BY priority ASC, name ASC";
    $result = $conn->query($query);
    ?>

    <?php echo $message; ?>

    <!-- System Status Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-server me-2"></i>SMS Gateway System Status</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Primary Providers</h5>
                            <?php
                            $primary_query = "SELECT COUNT(*) as count, SUM(is_active) as active FROM api_providers WHERE is_backup = 0";
                            $primary_result = $conn->query($primary_query);
                            $primary_data = $primary_result->fetch_assoc();
                            ?>
                            <p class="display-4"><?php echo $primary_data['active']; ?> / <?php echo $primary_data['count']; ?></p>
                            <p class="text-muted">Active / Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Backup Providers</h5>
                            <?php
                            $backup_query = "SELECT COUNT(*) as count, SUM(is_active) as active FROM api_providers WHERE is_backup = 1";
                            $backup_result = $conn->query($backup_query);
                            $backup_data = $backup_result->fetch_assoc();
                            ?>
                            <p class="display-4"><?php echo $backup_data['active']; ?> / <?php echo $backup_data['count']; ?></p>
                            <p class="text-muted">Active / Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">System Health</h5>
                            <?php
                            $health_status = "Healthy";
                            $health_color = "success";
                            
                            if ($primary_data['active'] == 0) {
                                $health_status = "Critical";
                                $health_color = "danger";
                            } elseif ($backup_data['active'] == 0) {
                                $health_status = "Warning";
                                $health_color = "warning";
                            }
                            ?>
                            <p class="display-4 text-<?php echo $health_color; ?>"><?php echo $health_status; ?></p>
                            <p class="text-muted">Current Status</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New API Provider Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New API Provider</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Provider Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="api_url" class="form-label">API URL</label>
                            <input type="url" class="form-control" id="api_url" name="api_url" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority (Lower number = Higher priority)</label>
                            <input type="number" class="form-control" id="priority" name="priority" min="1" value="10" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="api_key" class="form-label">API Key</label>
                            <input type="text" class="form-control" id="api_key" name="api_key">
                        </div>
                        
                        <div class="mb-3">
                            <label for="api_secret" class="form-label">API Secret</label>
                            <input type="text" class="form-control" id="api_secret" name="api_secret">
                        </div>
                        
                        <div class="mb-3">
                            <label for="method" class="form-label">HTTP Method</label>
                            <select class="form-select" id="method" name="method">
                                <option value="GET">GET</option>
                                <option value="POST">POST (URL Encoded)</option>
                                <option value="POST_JSON">POST (JSON)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="headers" class="form-label">Headers (JSON format)</label>
                            <input type="text" class="form-control" id="headers" name="headers" placeholder='e.g., {"Content-Type": "application/json"}'>
                        </div>
                        <div class="mb-3">
                            <label for="post_data" class="form-label">POST Data Payload</label>
                            <textarea class="form-control" id="post_data" name="post_data" rows="2" placeholder='e.g., {"api_key": "{api_key}", "to": "{mobile}", "msg": "{message_json}"}'></textarea>
                            <small class="text-muted">Variables: {api_key}, {mobile}, {message}, {message_encoded}, {message_json}</small>
                        </div>
                        <div class="mb-3">
                            <label for="success_keyword" class="form-label">Success Keyword</label>
                            <input type="text" class="form-control" id="success_keyword" name="success_keyword" placeholder='e.g., "status":"success"'>
                            <small class="text-muted">If matched in the response, considers sending successful.</small>
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
                                <label class="form-check-label" for="is_backup">Backup Provider</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" name="add_provider" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Add Provider
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- API Providers List -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-server me-2"></i>API Providers</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="providerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="primary-tab" data-bs-toggle="tab" data-bs-target="#primary" type="button" role="tab" aria-controls="primary" aria-selected="true">Primary Providers</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab" aria-controls="backup" aria-selected="false">Backup Providers</button>
                </li>
            </ul>
            
            <div class="tab-content" id="providerTabsContent">
                <!-- Primary Providers Tab -->
                <div class="tab-pane fade show active" id="primary" role="tabpanel" aria-labelledby="primary-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Name</th>
                                    <th>API URL</th>
                                    <th>Success Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) {
                                        if ($row['is_backup'] == 0) {
                                            $status_badge = $row['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                                            echo '<tr>
                                                <td>' . $row['priority'] . '</td>
                                                <td>' . htmlspecialchars($row['name']) . '</td>
                                                <td>' . htmlspecialchars($row['api_url']) . '</td>
                                                <td>' . $row['success_rate'] . '%</td>
                                                <td>' . $status_badge . '</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?toggle=' . $row['id'] . '" class="btn btn-outline-' . ($row['is_active'] ? 'warning' : 'success') . '" title="' . ($row['is_active'] ? 'Deactivate' : 'Activate') . '">
                                                            <i class="fas fa-' . ($row['is_active'] ? 'pause' : 'play') . '"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-primary edit-provider" data-bs-toggle="modal" data-bs-target="#editProviderModal" 
                                                            data-id="' . $row['id'] . '"
                                                            data-name="' . htmlspecialchars($row['name']) . '"
                                                            data-api-url="' . htmlspecialchars($row['api_url']) . '"
                                                            data-api-key="' . htmlspecialchars($row['api_key']) . '"
                                                            data-api-secret="' . htmlspecialchars($row['api_secret']) . '"
                                                            data-method="' . htmlspecialchars($row['method']) . '"
                                                            data-headers="' . htmlspecialchars($row['headers']) . '"
                                                            data-post-data="' . htmlspecialchars($row['post_data']) . '"
                                                            data-success-keyword="' . htmlspecialchars($row['success_keyword']) . '"
                                                            data-is-active="' . $row['is_active'] . '"
                                                            data-is-backup="' . $row['is_backup'] . '"
                                                            data-priority="' . $row['priority'] . '"
                                                            data-success-rate="' . $row['success_rate'] . '">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=' . $row['id'] . '" class="btn btn-outline-danger" onclick="return confirm(\'Are you sure you want to delete this provider?\')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>';
                                        }
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">No primary providers found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Backup Providers Tab -->
                <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Name</th>
                                    <th>API URL</th>
                                    <th>Success Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()) {
                                        if ($row['is_backup'] == 1) {
                                            $status_badge = $row['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                                            echo '<tr>
                                                <td>' . $row['priority'] . '</td>
                                                <td>' . htmlspecialchars($row['name']) . '</td>
                                                <td>' . htmlspecialchars($row['api_url']) . '</td>
                                                <td>' . $row['success_rate'] . '%</td>
                                                <td>' . $status_badge . '</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?toggle=' . $row['id'] . '" class="btn btn-outline-' . ($row['is_active'] ? 'warning' : 'success') . '" title="' . ($row['is_active'] ? 'Deactivate' : 'Activate') . '">
                                                            <i class="fas fa-' . ($row['is_active'] ? 'pause' : 'play') . '"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-primary edit-provider" data-bs-toggle="modal" data-bs-target="#editProviderModal" 
                                                            data-id="' . $row['id'] . '"
                                                            data-name="' . htmlspecialchars($row['name']) . '"
                                                            data-api-url="' . htmlspecialchars($row['api_url']) . '"
                                                            data-api-key="' . htmlspecialchars($row['api_key']) . '"
                                                            data-api-secret="' . htmlspecialchars($row['api_secret']) . '"
                                                            data-method="' . htmlspecialchars($row['method']) . '"
                                                            data-headers="' . htmlspecialchars($row['headers']) . '"
                                                            data-post-data="' . htmlspecialchars($row['post_data']) . '"
                                                            data-success-keyword="' . htmlspecialchars($row['success_keyword']) . '"
                                                            data-is-active="' . $row['is_active'] . '"
                                                            data-is-backup="' . $row['is_backup'] . '"
                                                            data-priority="' . $row['priority'] . '"
                                                            data-success-rate="' . $row['success_rate'] . '">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=' . $row['id'] . '" class="btn btn-outline-danger" onclick="return confirm(\'Are you sure you want to delete this provider?\')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>';
                                        }
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">No backup providers found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Provider Modal -->
<div class="modal fade" id="editProviderModal" tabindex="-1" aria-labelledby="editProviderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editProviderModalLabel">Edit API Provider</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" id="edit_provider_id" name="provider_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Provider Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_api_url" class="form-label">API URL</label>
                                <input type="url" class="form-control" id="edit_api_url" name="api_url" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="edit_priority" name="priority" min="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_success_rate" class="form-label">Success Rate (%)</label>
                                <input type="number" class="form-control" id="edit_success_rate" name="success_rate" min="0" max="100" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_api_key" class="form-label">API Key</label>
                                <input type="text" class="form-control" id="edit_api_key" name="api_key">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_api_secret" class="form-label">API Secret</label>
                                <input type="text" class="form-control" id="edit_api_secret" name="api_secret">
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_method" class="form-label">HTTP Method</label>
                                <select class="form-select" id="edit_method" name="method">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST (URL Encoded)</option>
                                    <option value="POST_JSON">POST (JSON)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_headers" class="form-label">Headers (JSON format)</label>
                                <input type="text" class="form-control" id="edit_headers" name="headers" placeholder='e.g., {"Content-Type": "application/json"}'>
                            </div>
                            <div class="mb-3">
                                <label for="edit_post_data" class="form-label">POST Data Payload</label>
                                <textarea class="form-control" id="edit_post_data" name="post_data" rows="2" placeholder='e.g., {"api_key": "{api_key}", "to": "{mobile}", "msg": "{message_json}"}'></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_success_keyword" class="form-label">Success Keyword</label>
                                <input type="text" class="form-control" id="edit_success_keyword" name="success_keyword">
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
                                    <label class="form-check-label" for="edit_is_backup">Backup Provider</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_provider" class="btn btn-primary">Update Provider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit provider modal
        const editButtons = document.querySelectorAll('.edit-provider');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const apiUrl = this.getAttribute('data-api-url');
                const apiKey = this.getAttribute('data-api-key');
                const apiSecret = this.getAttribute('data-api-secret');
                const method = this.getAttribute('data-method');
                const headers = this.getAttribute('data-headers');
                const postData = this.getAttribute('data-post-data');
                const successKeyword = this.getAttribute('data-success-keyword');
                const isActive = this.getAttribute('data-is-active') === '1';
                const isBackup = this.getAttribute('data-is-backup') === '1';
                const priority = this.getAttribute('data-priority');
                const successRate = this.getAttribute('data-success-rate');
                
                document.getElementById('edit_provider_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_api_url').value = apiUrl;
                document.getElementById('edit_api_key').value = apiKey;
                document.getElementById('edit_api_secret').value = apiSecret;
                document.getElementById('edit_method').value = method || 'GET';
                document.getElementById('edit_headers').value = headers;
                document.getElementById('edit_post_data').value = postData;
                document.getElementById('edit_success_keyword').value = successKeyword;
                document.getElementById('edit_is_active').checked = isActive;
                document.getElementById('edit_is_backup').checked = isBackup;
                document.getElementById('edit_priority').value = priority;
                document.getElementById('edit_success_rate').value = successRate;
            });
        });
    });
</script>

<?php include 'inc/footer.php'; ?>  
