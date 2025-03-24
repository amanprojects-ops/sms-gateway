<?php
// Include database connection
include './inc/header.php';

?>
<!-- API Provider Switch -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Active API Provider</h5>
                    <p class="card-text">Currently using: <strong><?php echo ucfirst($active_provider); ?> Provider</strong></p>
                </div>
                <div>
                    <label class="toggle-switch me-2">
                        <input type="checkbox" id="apiProviderSwitch" <?php echo $active_provider == 'backup' ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="ms-2">Switch to <?php echo $active_provider == 'backup' ? 'Primary' : 'Backup'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stats-label">Total SMS Sent</h6>
                        <p class="stats-number"><?php echo number_format($total_sms); ?></p>
                    </div>
                    <i class="fas fa-paper-plane stats-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stats-label">Success Rate</h6>
                        <p class="stats-number"><?php echo $success_rate; ?>%</p>
                    </div>
                    <i class="fas fa-check-circle stats-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stats-label">API Users</h6>
                        <p class="stats-number"><?php echo number_format($total_users); ?></p>
                    </div>
                    <i class="fas fa-users stats-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stats-label">SMS Today</h6>
                        <p class="stats-number"><?php echo number_format($total_sms_today); ?></p>
                    </div>
                    <i class="fas fa-calendar-day stats-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- API Providers Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card dashboard-card">
            <div class="card-header">
                <h5 class="card-title">API Providers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-custom">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Balance</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($provider['name']); ?></strong>
                                        <?php if ($active_provider == ($provider['is_backup'] ? 'backup' : 'primary')): ?>
                                            <span class="badge bg-success ms-2">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $provider['priority']; ?></td>
                                    <td>
                                        <?php if ($provider['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $balance = checkAPIBalance($provider['is_backup'] ? 'backup' : 'primary');
                                        echo number_format($balance);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $provider['success_rate']; ?>%"></div>
                                        </div>
                                        <small class="d-block text-end mt-1"><?php echo $provider['success_rate']; ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart and Recent Activities -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card dashboard-card">
            <div class="card-header">
                <h5 class="card-title">SMS Usage Statistics</h5>
            </div>
            <div class="card-body">
                <canvas id="smsChart" data-labels='<?php echo $chart_labels_json; ?>' data-sent='<?php echo $chart_sent_json; ?>' data-failed='<?php echo $chart_failed_json; ?>'></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Recent SMS Logs</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <?php if ($log['status']): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                    <?php endif; ?>
                                    <?php echo substr(htmlspecialchars($log['phone']), 0, 12); ?>
                                </h6>
                                <small><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo substr(htmlspecialchars($log['message']), 0, 30) . (strlen($log['message']) > 30 ? '...' : ''); ?></p>
                            <small>User: <?php echo htmlspecialchars($log['username'] ?? 'System'); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="p-3 text-center">
                    <a href="sms_logs.php" class="btn btn-sm btn-outline-primary">View All Logs</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './inc/footer.php'; ?>