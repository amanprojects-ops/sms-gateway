<?php include 'inc/header.php'; ?>

<main class="col-md-12 py-4">
    <h1 class="h2 mb-4">SMS Pricing Plans</h1>

    <?php
    // Function to display success/error messages
    function displayMessage($message, $type = 'success')
    {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    // Initialize message variable
    $message = '';

    // Handle form submissions
    if (isset($_POST['add_plan'])) {
        // Get form data for new plan
        $name = $_POST['name'];
        $sms_count = (int)$_POST['sms_count'];
        $price = (float)$_POST['price'];
        $validity_days = (int)$_POST['validity_days'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $description = $_POST['description'];

        // Insert new plan
        $stmt = $conn->prepare("INSERT INTO sms_pricing (name, sms_count, price, validity_days, is_active, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sidiss", $name, $sms_count, $price, $validity_days, $is_active, $description);

        if ($stmt->execute()) {
            $message = displayMessage("New pricing plan added successfully");
        } else {
            $message = displayMessage("Error adding pricing plan: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle plan update
    if (isset($_POST['update_plan'])) {
        $id = $_POST['plan_id'];
        $name = $_POST['name'];
        $sms_count = (int)$_POST['sms_count'];
        $price = (float)$_POST['price'];
        $validity_days = (int)$_POST['validity_days'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $description = $_POST['description'];

        $stmt = $conn->prepare("UPDATE sms_pricing SET name = ?, sms_count = ?, price = ?, validity_days = ?, is_active = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sidissi", $name, $sms_count, $price, $validity_days, $is_active, $description, $id);

        if ($stmt->execute()) {
            $message = displayMessage("Pricing plan updated successfully");
        } else {
            $message = displayMessage("Error updating pricing plan: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Handle plan deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM sms_pricing WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = displayMessage("Pricing plan deleted successfully");
        } else {
            $message = displayMessage("Error deleting pricing plan: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Get all pricing plans
    $query = "SELECT * FROM sms_pricing ORDER BY price ASC";
    $result = $conn->query($query);
    ?>

    <?php echo $message; ?>

    <!-- Add New Pricing Plan Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Pricing Plan</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Plan Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sms_count" class="form-label">SMS Count</label>
                            <input type="number" class="form-control" id="sms_count" name="sms_count" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="validity_days" class="form-label">Validity (Days)</label>
                            <input type="number" class="form-control" id="validity_days" name="validity_days" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="add_plan" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Add Pricing Plan
                </button>
            </form>
        </div>
    </div>

    <!-- Pricing Plans List -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Pricing Plans</h5>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>SMS Count</th>
                                <th>Price</th>
                                <th>Validity</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo number_format($row['sms_count']); ?></td>
                                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo $row['validity_days']; ?> days</td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-plan" 
                                                data-bs-toggle="modal" data-bs-target="#editPlanModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-sms-count="<?php echo $row['sms_count']; ?>"
                                                data-price="<?php echo $row['price']; ?>"
                                                data-validity="<?php echo $row['validity_days']; ?>"
                                                data-active="<?php echo $row['is_active']; ?>"
                                                data-description="<?php echo htmlspecialchars($row['description']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this plan?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No pricing plans found. Add your first plan using the form above.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Edit Plan Modal -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPlanModalLabel">Edit Pricing Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" id="edit_plan_id" name="plan_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Plan Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_sms_count" class="form-label">SMS Count</label>
                                <input type="number" class="form-control" id="edit_sms_count" name="sms_count" min="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_price" class="form-label">Price ($)</label>
                                <input type="number" class="form-control" id="edit_price" name="price" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_validity_days" class="form-label">Validity (Days)</label>
                                <input type="number" class="form-control" id="edit_validity_days" name="validity_days" min="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_plan" class="btn btn-primary">Update Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit plan modal
        const editButtons = document.querySelectorAll('.edit-plan');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const smsCount = this.getAttribute('data-sms-count');
                const price = this.getAttribute('data-price');
                const validity = this.getAttribute('data-validity');
                const active = this.getAttribute('data-active') === '1';
                const description = this.getAttribute('data-description');
                
                document.getElementById('edit_plan_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_sms_count').value = smsCount;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_validity_days').value = validity;
                document.getElementById('edit_is_active').checked = active;
                document.getElementById('edit_description').value = description;
            });
        });
    });
</script>



<?php include 'inc/footer.php'; ?>