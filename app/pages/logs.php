<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-archive-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
$app_secret = $selected_app_data['secret'];

$stmt = $conn->prepare("SELECT * FROM logs WHERE logapp = ? ORDER BY created_at DESC LIMIT 500");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$logs_result = $stmt->get_result();
$logs = [];
while ($log = $logs_result->fetch_assoc()) {
    $logs[] = $log;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Activity Logs</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" style="width:auto" onchange="filterLogs()">
                            <option value="">All Events</option>
                            <option value="init">Init</option>
                            <option value="login">Login</option>
                            <option value="register">Register</option>
                            <option value="license">License</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Event</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="ri-archive-line fs-1 text-muted mb-3 d-block"></i>
                                    <h6 class="text-muted">No activity logs yet</h6>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><span class="badge bg-info-subtle text-info"><?= htmlspecialchars($log['type'] ?? 'N/A') ?></span></td>
                                    <td><small><?= htmlspecialchars($log['username'] ?? 'N/A') ?></small></td>
                                    <td><code class="small text-muted"><?= htmlspecialchars($log['ip'] ?? 'N/A') ?></code></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($log['info'] ?? '') ?></small></td>
                                    <td><small class="text-muted"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
