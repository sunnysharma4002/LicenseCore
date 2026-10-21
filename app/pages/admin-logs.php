<?php
$log_filter = sanitize($_GET['filter'] ?? '');
$log_query = "SELECT l.*, a.name as app_name FROM logs l LEFT JOIN apps a ON l.app = a.secret";
$params = [];
$types = '';

if (!empty($log_filter)) {
    $log_query .= " WHERE l.action LIKE ? OR l.user LIKE ? OR a.name LIKE ?";
    $like = "%$log_filter%";
    $params = [$like, $like, $like];
    $types = 'sss';
}
$log_query .= " ORDER BY l.created_at DESC LIMIT 500";

$stmt = $conn->prepare($log_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();
?>
<div class="row">
    <div class="col-lg-12">
        <h5 class="mb-3">Admin - System Logs</h5>

        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col"><h5 class="card-title mb-0">Activity Logs</h5></div>
                    <div class="col-auto">
                        <form method="get" class="d-flex gap-2">
                            <input type="hidden" name="page" value="admin-logs">
                            <input type="text" class="form-control form-control-sm" name="filter" placeholder="Search logs..." value="<?= htmlspecialchars($log_filter) ?>" style="width:200px">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="ri-search-line"></i></button>
                            <?php if ($log_filter): ?>
                            <a href="?page=admin-logs" class="btn btn-sm btn-ghost-secondary"><i class="ri-close-line"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>ID</th><th>Application</th><th>Action</th><th>User</th><th>IP</th><th>Message</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($logs->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No logs found</td></tr>
                            <?php else: ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= htmlspecialchars($log['app_name'] ?? substr($log['app'] ?? '', 0, 12).'...') ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><?= htmlspecialchars($log['user'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($log['ip'] ?? '-') ?></td>
                                <td class="text-truncate" style="max-width:250px"><?= htmlspecialchars($log['message'] ?? '-') ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>