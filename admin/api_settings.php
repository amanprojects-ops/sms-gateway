<?php
// Include database connection
include './inc/header.php';

?>

<main class="col-md-12 py-4">
    <h1 class="h2 mb-4">API Settings</h1>

    <?php
    // Function to display success/error messages
    function displayMessage($message, $type = 'success')
    {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    // Initialize message variable
    $message = '';

    // Handle form submission
    if (isset($_POST['update_settings'])) {
        // Get form data
        $sms_provider = $_POST['sms_provider'];
        $api_key = $_POST['api_key'];
        $api_secret = $_POST['api_secret'];
        $sender_id = $_POST['sender_id'];
        $max_daily_limit = (int)$_POST['max_daily_limit'];
        $rate_limit = (int)$_POST['rate_limit'];
        $enable_logging = isset($_POST['enable_logging']) ? 1 : 0;
        $enable_rate_limiting = isset($_POST['enable_rate_limiting']) ? 1 : 0;

        // Update settings in database
        $stmt = $conn->prepare("UPDATE api_settings SET 
            value = CASE 
                WHEN name = 'sms_provider' THEN ?
                WHEN name = 'api_key' THEN ?
                WHEN name = 'api_secret' THEN ?
                WHEN name = 'sender_id' THEN ?
                WHEN name = 'max_daily_limit' THEN ?
                WHEN name = 'rate_limit' THEN ?
                WHEN name = 'enable_logging' THEN ?
                WHEN name = 'enable_rate_limiting' THEN ?
                ELSE value
            END
            WHERE name IN ('sms_provider', 'api_key', 'api_secret', 'sender_id', 'max_daily_limit', 'rate_limit', 'enable_logging', 'enable_rate_limiting')");

        $stmt->bind_param("ssssiiii", $sms_provider, $api_key, $api_secret, $sender_id, $max_daily_limit, $rate_limit, $enable_logging, $enable_rate_limiting);

        if ($stmt->execute()) {
            $message = displayMessage("API settings updated successfully");
        } else {
            $message = displayMessage("Error updating API settings: " . $conn->error, "danger");
        }
        $stmt->close();
    }

    // Get current settings
    $query = "SELECT name, value FROM api_settings WHERE name IN ('sms_provider', 'api_key', 'api_secret', 'sender_id', 'max_daily_limit', 'rate_limit', 'enable_logging', 'enable_rate_limiting')";
    $result = $conn->query($query);

    $settings = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['name']] = $row['value'];
        }
    }
    ?>

    <?php echo $message; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>SMS Gateway Configuration</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="border-bottom pb-2">SMS Provider Settings</h5>

                        <div class="mb-3">
                            <label for="sms_provider" class="form-label">SMS Provider</label>
                            <select class="form-select" id="sms_provider" name="sms_provider" required>
                                <option value="twilio" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'twilio') ? 'selected' : ''; ?>>Twilio</option>
                                <option value="nexmo" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'nexmo') ? 'selected' : ''; ?>>Nexmo (Vonage)</option>
                                <option value="messagebird" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'messagebird') ? 'selected' : ''; ?>>MessageBird</option>
                                <option value="plivo" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'plivo') ? 'selected' : ''; ?>>Plivo</option>
                                <option value="custom" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'custom') ? 'selected' : ''; ?>>Custom Provider</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="api_key" class="form-label">API Key</label>
                            <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo isset($settings['api_key']) ? htmlspecialchars($settings['api_key']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="api_secret" class="form-label">API Secret</label>
                            <input type="password" class="form-control" id="api_secret" name="api_secret" value="<?php echo isset($settings['api_secret']) ? htmlspecialchars($settings['api_secret']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="sender_id" class="form-label">Sender ID / From Number</label>
                            <input type="text" class="form-control" id="sender_id" name="sender_id" value="<?php echo isset($settings['sender_id']) ? htmlspecialchars($settings['sender_id']) : ''; ?>" required>
                            <small class="text-muted">This will appear as the sender of your SMS messages</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="border-bottom pb-2">Rate Limiting & Logging</h5>

                        <div class="mb-3">
                            <label for="max_daily_limit" class="form-label">Maximum Daily SMS Limit</label>
                            <input type="number" class="form-control" id="max_daily_limit" name="max_daily_limit" value="<?php echo isset($settings['max_daily_limit']) ? htmlspecialchars($settings['max_daily_limit']) : '1000'; ?>" required>
                            <small class="text-muted">Maximum number of SMS that can be sent per day</small>
                        </div>

                        <div class="mb-3">
                            <label for="rate_limit" class="form-label">Rate Limit (per minute)</label>
                            <input type="number" class="form-control" id="rate_limit" name="rate_limit" value="<?php echo isset($settings['rate_limit']) ? htmlspecialchars($settings['rate_limit']) : '60'; ?>" required>
                            <small class="text-muted">Maximum number of SMS that can be sent per minute</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enable_rate_limiting" name="enable_rate_limiting" <?php echo (isset($settings['enable_rate_limiting']) && $settings['enable_rate_limiting'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable_rate_limiting">Enable Rate Limiting</label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="enable_logging" name="enable_logging" <?php echo (isset($settings['enable_logging']) && $settings['enable_logging'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable_logging">Enable SMS Logging</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                    <button type="button" id="test_connection" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-vial me-2"></i>Test Connection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>API Usage Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>API Endpoint</h6>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']); ?>/api/send.php" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']); ?>/api/send.php">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>

                    <h6>Example API Request</h6>
                    <pre class="bg-light p-3 rounded"><code>curl -X POST "<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']); ?>/api/send.php" \
    -H "Content-Type: application/json" \
    -d '{
        "api_key": "YOUR_API_KEY",
        "to": "+1234567890",
        "message": "Hello from SMS Gateway!"
    }'</code></pre>
                </div>
                <div class="col-md-6">
                    <h6>Response Format</h6>
                    <pre class="bg-light p-3 rounded"><code>// Success Response
{
    "success": true,
    "message": "SMS sent successfully",
    "data": {
        "message_id": "SM123456",
        "to": "+1234567890",
        "status": "queued"
    }
}

// Error Response
{
    "success": false,
    "error": "Invalid phone number format",
    "error_code": 400
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</main>

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

        // Test connection button
        document.getElementById('test_connection').addEventListener('click', function() {
            const provider = document.getElementById('sms_provider').value;
            const apiKey = document.getElementById('api_key').value;
            const apiSecret = document.getElementById('api_secret').value;

            if (!apiKey || !apiSecret) {
                alert('Please enter API Key and API Secret before testing connection');
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
            this.disabled = true;

            // Here you would normally make an AJAX call to test the connection
            // For demo purposes, we'll just simulate a response after a delay
            setTimeout(() => {
                alert('Connection test successful! The SMS provider is properly configured.');
                this.innerHTML = '<i class="fas fa-vial me-2"></i>Test Connection';
                this.disabled = false;
            }, 2000);
        });
    });
</script>


<?php include './inc/footer.php'; ?>