<?php
// Admin System Logs Page

// Get logs with pagination
$page = max(1, intval($_GET['p'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total logs count
$total_logs = $conn->query("SELECT COUNT(*) as count FROM logs")->fetch_assoc()['count'];
$total_pages = ceil($total_logs / $per_page);

// Get logs with app and user info
$logs_query = $conn->query("
    SELECT
        l.*,
        a.name as app_name,
        acc.username as user_username
    FROM logs l
    LEFT JOIN apps a ON l.app = a.secret
    LEFT JOIN accounts acc ON l.user = acc.username
    ORDER BY l.created_at DESC
    LIMIT $per_page OFFSET $offset
");

// Get log statistics
$log_stats = $conn->query("
    SELECT
        COUNT(*) as total_logs,
        COUNT(DISTINCT app) as unique_apps,
        COUNT(DISTINCT user) as unique_users,
        COUNT(DISTINCT ip) as unique_ips,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) as logs_last_hour,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as logs_last_24h
    FROM logs
")->fetch_assoc();

// Get most active users
$active_users = $conn->query("
    SELECT user, COUNT(*) as log_count
    FROM logs
    WHERE user IS NOT NULL AND user != ''
    GROUP BY user
    ORDER BY log_count DESC
    LIMIT 10
");

// Get most active apps
$active_apps = $conn->query("
    SELECT a.name, COUNT(l.id) as log_count
    FROM logs l
    LEFT JOIN apps a ON l.app = a.secret
    WHERE l.app IS NOT NULL
    GROUP BY l.app, a.name
    ORDER BY log_count DESC
    LIMIT 10
");
?>

<!-- Success/Error Messages -->
<?php if (!empty($action_error)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="mdi mdi-alert text-danger"></i>
    <p class="mb-0 fw-medium"><?= htmlspecialchars($action_error) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($action_success)): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="mdi mdi-check-circle-outline text-success"></i>
    <p class="mb-0 fw-medium"><?= htmlspecialchars($action_success) ?></p>
</div>
<?php endif; ?>

<!-- Log Statistics -->
<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Total Logs</p>
                        <h3 class="text-primary fw-bold mb-0"><?= number_format($log_stats['total_logs']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-primary bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-history text-primary fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-clock-outline text-success me-1"></i> Last 24h: <?= number_format($log_stats['logs_last_24h']) ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Active Apps</p>
                        <h3 class="text-info fw-bold mb-0"><?= number_format($log_stats['unique_apps']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-info bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-cubes text-info fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-account-group text-primary me-1"></i> With activity</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Active Users</p>
                        <h3 class="text-success fw-bold mb-0"><?= number_format($log_stats['unique_users']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-success bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-account text-success fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-clock-outline text-warning me-1"></i> Last hour: <?= number_format($log_stats['logs_last_hour']) ?></small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Unique IPs</p>
                        <h3 class="text-warning fw-bold mb-0"><?= number_format($log_stats['unique_ips']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-warning bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-web text-warning fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-lan text-primary me-1"></i> Different sources</small>
            </div>
        </div>
    </div>
</div>

<!-- Top Active Users & Apps -->
<div class="row g-3 mb-3">
    <!-- Most Active Users -->
    <div class="col-lg-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary bg-transparent px-3 py-2">
                <h5 class="fw-bold mb-0">Most Active Users</h5>
            </div>
            <div>
                <?php if ($active_users->num_rows > 0): ?>
                    <?php while ($user_row = $active_users->fetch_assoc()): ?>
                    <div class="px-3 py-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px" class="rounded-circle bg-success bg-opacity-25 d-flex align-items-center justify-content-center">
                                    <i class="mdi mdi-account text-success small"></i>
                                </div>
                                <div>
                                    <p class="fw-semibold small mb-0"><?= htmlspecialchars($user_row['user']) ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary"><?= number_format($user_row['log_count']) ?> logs</span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <i class="mdi mdi-account-group fs-1 mb-2"></i>
                        <p class="mb-0">No user activity yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Most Active Apps -->
    <div class="col-lg-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary bg-transparent px-3 py-2">
                <h5 class="fw-bold mb-0">Most Active Applications</h5>
            </div>
            <div>
                <?php if ($active_apps->num_rows > 0): ?>
                    <?php while ($app_row = $active_apps->fetch_assoc()): ?>
                    <div class="px-3 py-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px" class="rounded bg-primary bg-opacity-25 d-flex align-items-center justify-content-center">
                                    <i class="mdi mdi-cube-outline text-primary small"></i>
                                </div>
                                <div>
                                    <p class="fw-semibold small mb-0"><?= htmlspecialchars($app_row['name'] ?: 'Unknown App') ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-info"><?= number_format($app_row['log_count']) ?> logs</span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <i class="mdi mdi-cubes fs-1 mb-2"></i>
                        <p class="mb-0">No app activity yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Logs Table -->
<div class="card bg-dark border-secondary">
    <div class="card-header border-secondary bg-transparent px-3 py-2">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0">System Logs</h5>
            <div class="d-flex align-items-center gap-3">
                <small class="text-muted">
                    Page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_logs) ?> total)
                </small>
                <div class="d-flex gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=logs&p=<?= $page - 1 ?>" class="btn btn-sm btn-outline-secondary">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=logs&p=<?= $page + 1 ?>" class="btn btn-sm btn-outline-secondary">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-borderless table-hover align-middle mb-0">
            <thead class="table-secondary">
                <tr>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Time</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Action</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">User</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Application</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">IP Address</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Message</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $logs_query->fetch_assoc()): ?>
                <tr>
                    <td class="px-3 py-1 text-muted small">
                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                        <div class="text-secondary small">
                            <?= date('H:i:s', strtotime($log['created_at'])) ?>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <span class="badge bg-primary"><?= htmlspecialchars($log['action']) ?></span>
                    </td>
                    <td class="px-3 py-1 small">
                        <?= htmlspecialchars($log['user'] ?: 'System') ?>
                    </td>
                    <td class="px-3 py-1 small">
                        <?php if (!empty($log['app_name'])): ?>
                            <span class="text-info"><?= htmlspecialchars($log['app_name']) ?></span>
                        <?php else: ?>
                            <span class="text-secondary">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1 small font-monospace text-muted">
                        <?= htmlspecialchars($log['ip']) ?>
                    </td>
                    <td class="px-3 py-1 small" style="max-width:300px">
                        <div class="text-truncate" title="<?= htmlspecialchars($log['message']) ?>">
                            <?= htmlspecialchars($log['message']) ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($logs_query->num_rows === 0): ?>
        <div class="p-3 text-center text-muted">
            <i class="mdi mdi-history fs-1 mb-2"></i>
            <p class="mb-0">No logs found</p>
        </div>
    <?php endif; ?>
</div>
