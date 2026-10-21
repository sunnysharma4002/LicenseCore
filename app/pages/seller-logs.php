<?php
// Get logs for this seller's applications
$seller_logs = [];
if (!empty($applications)) {
    $app_secrets = array_column($applications, 'secret');
    $placeholders = implode(',', array_fill(0, count($app_secrets), '?'));
    $types = str_repeat('s', count($app_secrets));
    $stmt = $conn->prepare("SELECT l.*, a.name as app_name FROM logs l LEFT JOIN apps a ON l.app = a.secret WHERE l.app IN ($placeholders) ORDER BY l.created_at DESC LIMIT 500");
    $stmt->bind_param($types, ...$app_secrets);
    $stmt->execute();
    $seller_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Seller Logs</h5>
                <span class="badge bg-primary"><?= count($seller_logs) ?> entries</span>
            </div>
            <div class="card-body">
                <?php if (empty($seller_logs)): ?>
                <div class="text-center py-5">
                    <i class="ri-archive-line fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No logs found</h5>
                    <p class="text-muted mb-0">Logs will appear here once your applications have activity.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="sellerLogsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Application</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>IP</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seller_logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= htmlspecialchars($log['app_name'] ?? $log['app']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><?= htmlspecialchars($log['user'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['ip'] ?? '-') ?></td>
                                <td class="text-truncate" style="max-width:300px"><?= htmlspecialchars($log['message'] ?? '-') ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>