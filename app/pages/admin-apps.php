<?php
$action_error = '';
$action_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_app_admin'])) {
        $app_secret = sanitize($_POST['secret'] ?? '');
        if (!empty($app_secret)) {
            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");
                $tables = ['subs', 'users', '`keys`', 'sessions', 'uservars', 'vars', 'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages', 'tokens', 'integrations'];
                foreach ($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM {$table} WHERE app = ?");
                    $stmt->bind_param("s", $app_secret);
                    $stmt->execute();
                }
                $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ?");
                $stmt->bind_param("s", $app_secret);
                $stmt->execute();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $conn->commit();
                $action_success = 'Application deleted successfully';
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $action_error = 'Delete failed: ' . $e->getMessage();
            }
        }
    }
}

$apps_query = $conn->query("
    SELECT a.*, acc.username as owner_username,
        (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'active') as active_keys,
        (SELECT COUNT(*) FROM users WHERE app = a.secret) as total_users
    FROM apps a
    LEFT JOIN accounts acc ON a.ownerid = acc.ownerid
    ORDER BY a.created_at DESC
");
?>
<div class="row">
    <div class="col-lg-12">
        <h5 class="mb-3">Admin - Applications</h5>

        <?php if ($action_error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($action_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($action_success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($action_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">All Applications</h5>
                <span class="text-muted small">Total: <?= $apps_query->num_rows ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Name</th><th>Owner</th><th>Version</th><th>Keys</th><th>Users</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($app = $apps_query->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['name']) ?></td>
                                <td><?= htmlspecialchars($app['owner_username'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($app['version']) ?></td>
                                <td><?= $app['active_keys'] ?></td>
                                <td><?= $app['total_users'] ?></td>
                                <td>
                                    <?php if ($app['paused']): ?>
                                    <span class="badge bg-warning-subtle text-warning">Paused</span>
                                    <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d', strtotime($app['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-ghost-danger" data-bs-toggle="modal" data-bs-target="#del-app-<?= substr($app['secret'], 0, 16) ?>"><i class="ri-delete-bin-line"></i></button>

                                    <div class="modal fade" id="del-app-<?= substr($app['secret'], 0, 16) ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title">Delete Application</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form method="post"><div class="modal-body">
                                                <input type="hidden" name="secret" value="<?= htmlspecialchars($app['secret']) ?>">
                                                <div class="alert alert-danger">Delete <strong><?= htmlspecialchars($app['name']) ?></strong> by <?= htmlspecialchars($app['owner_username'] ?? 'Unknown') ?>? All associated data will be permanently removed.</div>
                                            </div><div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_app_admin" class="btn btn-danger">Delete</button>
                                            </div></form>
                                        </div></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>