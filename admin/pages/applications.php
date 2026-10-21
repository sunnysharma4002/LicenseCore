<?php
// Admin Application Management Page

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_app') {
        $app_secret = sanitize($_POST['app_secret'] ?? '');

        if (!empty($app_secret)) {
            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                $tables = [
                    'subs', 'users', '`keys`', 'sessions', 'uservars', 'vars',
                    'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages',
                    'tokens', 'integrations'
                ];

                foreach ($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM {$table} WHERE app = ?");
                    $stmt->bind_param("s", $app_secret);
                    $stmt->execute();
                }

                $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ?");
                $stmt->bind_param("s", $app_secret);
                if ($stmt->execute()) {
                    $conn->commit();
                    $action_success = 'Application deleted successfully';
                    log_action($app_secret, 'app_deleted_admin', $user['username'], 'Application deleted by admin');
                } else {
                    throw new Exception('Failed to delete application');
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $action_error = 'Failed to delete application: ' . $e->getMessage();
            }
        }
    }
}

// Get applications with stats
$apps_query = $conn->query("
    SELECT
        a.*,
        acc.username as owner_name,
        acc.email as owner_email,
        (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'active') as active_keys,
        (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'inactive') as inactive_keys,
        (SELECT COUNT(*) FROM users WHERE app = a.secret) as total_users,
        (SELECT COUNT(*) FROM logs WHERE app = a.secret) as total_logs
    FROM apps a
    JOIN accounts acc ON a.ownerid = acc.ownerid
    ORDER BY a.created_at DESC
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

<!-- Application Management Table -->
<div class="card bg-dark border-secondary">
    <div class="card-header border-secondary bg-transparent px-3 py-2">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0">Application Management</h5>
            <div class="d-flex align-items-center gap-3">
                <small class="text-muted">
                    Total Apps: <span class="text-info fw-semibold"><?= $apps_query->num_rows ?></span>
                </small>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAppModal">
                    <i class="mdi mdi-plus me-1"></i>Create App
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-borderless table-hover align-middle mb-0">
            <thead class="table-secondary">
                <tr>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Application</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Owner</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Stats</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Status</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Created</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($app = $apps_query->fetch_assoc()): ?>
                <tr>
                    <td class="px-3 py-1">
                        <div class="d-flex align-items-center">
                            <div style="width:48px;height:48px" class="rounded bg-primary bg-opacity-25 d-flex align-items-center justify-content-center me-2">
                                <i class="mdi mdi-cube-outline text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($app['name']) ?></div>
                                <div class="text-muted small">v<?= htmlspecialchars($app['version']) ?></div>
                                <div class="text-secondary small font-monospace">Secret: <?= htmlspecialchars(substr($app['secret'], 0, 16)) ?>...</div>
                                <?php if (!empty($app['description'])): ?>
                                    <div class="text-muted small text-truncate mt-1" style="max-width:250px" title="<?= htmlspecialchars($app['description']) ?>">
                                        <?= htmlspecialchars($app['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <div class="fw-semibold small text-primary"><?= htmlspecialchars($app['owner_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($app['owner_email']) ?></div>
                        <div class="text-secondary small font-monospace">ID: <?= htmlspecialchars(substr($app['ownerid'], 0, 12)) ?>...</div>
                    </td>
                    <td class="px-3 py-1">
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-success small"><i class="mdi mdi-key me-1"></i><?= $app['active_keys'] ?> active</span>
                                <span class="text-muted small"><i class="mdi mdi-key me-1"></i><?= $app['inactive_keys'] ?> inactive</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-warning small"><i class="mdi mdi-account-group me-1"></i><?= $app['total_users'] ?> users</span>
                                <span class="text-primary small"><i class="mdi mdi-history me-1"></i><?= $app['total_logs'] ?> logs</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <?php if ($app['paused']): ?>
                            <span class="badge bg-danger">Paused</span>
                        <?php else: ?>
                            <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1 text-muted small">
                        <?= date('M j, Y', strtotime($app['created_at'])) ?>
                        <div class="text-secondary small">
                            <?= date('H:i', strtotime($app['created_at'])) ?>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <div class="d-flex align-items-center gap-1">
                            <button onclick="viewAppDetails('<?= htmlspecialchars($app['secret']) ?>')" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="mdi mdi-eye-outline"></i>
                            </button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete_app">
                                <input type="hidden" name="app_secret" value="<?= htmlspecialchars($app['secret']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this application and all associated data? This cannot be undone!')" title="Delete App">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($apps_query->num_rows === 0): ?>
        <div class="p-3 text-center text-muted">
            <i class="mdi mdi-cubes fs-1 mb-2"></i>
            <p class="mb-0">No applications found</p>
        </div>
    <?php endif; ?>
</div>

<!-- Create App Modal -->
<div class="modal fade" id="createAppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold">Create New Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create_app">
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Application Name*</label>
                        <input type="text" name="app_name" class="form-control bg-dark text-white border-secondary" placeholder="My Awesome App" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Owner ID*</label>
                        <input type="text" name="ownerid" class="form-control bg-dark text-white border-secondary" placeholder="Owner ID" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Version</label>
                        <input type="text" name="version" value="1.0" class="form-control bg-dark text-white border-secondary" placeholder="1.0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Description</label>
                        <textarea name="description" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Describe your application"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="mdi mdi-plus me-2"></i>Create Application
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- App Details Modal -->
<div class="modal fade" id="appDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold">Application Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="appDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    var modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function closeModal(modalId) {
    var modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}

function viewAppDetails(appSecret) {
    alert('App Secret: ' + appSecret + '\n\nDetailed view would show comprehensive app information, settings, and statistics.');
}
</script>
