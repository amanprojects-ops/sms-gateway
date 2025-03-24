<?php include 'inc/header.php'; ?>
<?php

// Function to display success/error messages
function displayMessage($message, $type = 'success')
{
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

// Handle API user operations
$message = '';

// Delete API user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'api_user'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = displayMessage("API user deleted successfully");
    } else {
        $message = displayMessage("Error deleting API user: " . $conn->error, "danger");
    }
    $stmt->close();
}

// Add new API user
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $api_key = bin2hex(random_bytes(16)); // Generate random API key
    $status = isset($_POST['status']) ? 1 : 0;

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = displayMessage("Username already exists", "danger");
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, api_key, status, role, created_at) VALUES (?, ?, ?, 'api_user', NOW())");
        $stmt->bind_param("ssi", $username, $api_key, $status);

        if ($stmt->execute()) {
            $message = displayMessage("API user added successfully");
        } else {
            $message = displayMessage("Error adding API user: " . $conn->error, "danger");
        }
        $stmt->close();
    }
    $check->close();
}

// Update API user
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $status = isset($_POST['status']) ? 1 : 0;
    $regenerate_key = isset($_POST['regenerate_key']) ? true : false;

    if ($regenerate_key) {
        $api_key = bin2hex(random_bytes(16));
        $stmt = $conn->prepare("UPDATE users SET username = ?, api_key = ?, status = ? WHERE id = ? AND role = 'api_user'");
        $stmt->bind_param("ssii", $username, $api_key, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, status = ? WHERE id = ? AND role = 'api_user'");
        $stmt->bind_param("sii", $username, $status, $id);
    }

    if ($stmt->execute()) {
        $message = displayMessage("API user updated successfully");
    } else {
        $message = displayMessage("Error updating API user: " . $conn->error, "danger");
    }
    $stmt->close();
}

// Get all API users
$query = "SELECT * FROM users WHERE role = 'api_user' ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<main class="col-md-12 py-4">
    <h1 class="h2 mb-4">API Users Management</h1>

    <?php echo $message; ?>

    <!-- Add New API User Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New API User</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="status" name="status" checked>
                    <label class="form-check-label" for="status">Active</label>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Add API User
                </button>
            </form>
        </div>
    </div>

    <!-- API Users List -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-users me-2"></i>API Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>API Key</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control api-key-field" value="<?php echo $row['api_key']; ?>" readonly>
                                            <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="<?php echo $row['api_key']; ?>">
                                                <i class="fas fa-copy me-1"></i>Copy
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] ? 'success' : 'danger'; ?>">
                                            <?php echo $row['status'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-btn"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                            data-status="<?php echo $row['status']; ?>">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this API user?')">
                                            <i class="fas fa-trash-alt me-1"></i>Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No API users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit API User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i>Edit API User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="edit_status" name="status">
                        <label class="form-check-label" for="edit_status">Active</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="regenerate_key" name="regenerate_key">
                        <label class="form-check-label" for="regenerate_key">Regenerate API Key</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize clipboard.js
        new ClipboardJS('.copy-btn');

        // Handle copy button click
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });

        // Handle edit button click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_user_id').value = this.getAttribute('data-id');
                document.getElementById('edit_username').value = this.getAttribute('data-username');
                document.getElementById('edit_status').checked = this.getAttribute('data-status') == 1;
                document.getElementById('regenerate_key').checked = false;
                
                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            });
        });
    });
</script>

<?php include 'inc/footer.php'; ?>