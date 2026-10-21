<?php
// Admin User Management Page

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'ban_user') {
        $target_user = sanitize($_POST['username'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');

        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 1, ban_reason = ? WHERE username = ?");
            $stmt->bind_param("ss", $reason, $target_user);
            if ($stmt->execute()) {
                $action_success = 'User banned successfully';
                log_action('', 'user_banned', $user['username'], "User $target_user banned: $reason");
            } else {
                $action_error = 'Failed to ban user';
            }
        }
    }

    if ($action === 'unban_user') {
        $target_user = sanitize($_POST['username'] ?? '');

        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 0, ban_reason = NULL WHERE username = ?");
            $stmt->bind_param("s", $target_user);
            if ($stmt->execute()) {
                $action_success = 'User unbanned successfully';
                log_action('', 'user_unbanned', $user['username'], "User $target_user unbanned");
            } else {
                $action_error = 'Failed to unban user';
            }
        }
    }
}

// Get users with additional stats
$users_query = $conn->query("
    SELECT
        a.*,
        (SELECT COUNT(*) FROM apps WHERE ownerid = a.ownerid) as app_count,
        (SELECT COUNT(*) FROM `keys` k JOIN apps app ON k.app = app.secret WHERE app.ownerid = a.ownerid AND k.status = 'active') as key_count,
        (SELECT COUNT(*) FROM users eu JOIN apps app ON eu.app = app.secret WHERE app.ownerid = a.ownerid) as end_user_count
    FROM accounts a
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

<!-- User Management Table -->
<div class="card bg-dark border-secondary">
    <div class="card-header border-secondary bg-transparent px-3 py-2">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0">User Management</h5>
            <small class="text-muted">
                Total Users: <span class="text-primary fw-semibold"><?= $users_query->num_rows ?></span>
            </small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-borderless table-hover align-middle mb-0">
            <thead class="table-secondary">
                <tr>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">User</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Role</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Stats</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Status</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Joined</th>
                    <th class="small fw-medium text-muted text-uppercase px-3 py-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user_data = $users_query->fetch_assoc()): ?>
                <tr>
                    <td class="px-3 py-1">
                        <div class="d-flex align-items-center">
                            <div style="width:40px;height:40px" class="rounded-circle bg-primary bg-opacity-25 d-flex align-items-center justify-content-center me-2">
                                <i class="mdi mdi-account text-primary small"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($user_data['username']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($user_data['email']) ?></div>
                                <div class="text-secondary small font-monospace">ID: <?= htmlspecialchars(substr($user_data['ownerid'], 0, 12)) ?>...</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <span class="badge bg-primary"><?= htmlspecialchars($user_data['role']) ?></span>
                    </td>
                    <td class="px-3 py-1">
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-info small"><i class="mdi mdi-cube-outline me-1"></i><?= $user_data['app_count'] ?></span>
                            <span class="text-success small"><i class="mdi mdi-key me-1"></i><?= $user_data['key_count'] ?></span>
                            <span class="text-warning small"><i class="mdi mdi-account-group me-1"></i><?= $user_data['end_user_count'] ?></span>
                        </div>
                    </td>
                    <td class="px-3 py-1">
                        <?php if ($user_data['banned']): ?>
                            <span class="badge bg-danger">Banned</span>
                            <?php if (!empty($user_data['ban_reason'])): ?>
                                <div class="text-muted small text-truncate mt-1" style="max-width:120px" title="<?= htmlspecialchars($user_data['ban_reason']) ?>">
                                    <?= htmlspecialchars($user_data['ban_reason']) ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1 text-muted small">
                        <?= date('M j, Y', strtotime($user_data['created_at'])) ?>
                        <?php if (!empty($user_data['last_login'])): ?>
                            <div class="text-secondary small">
                                Last login: <?= date('M j, H:i', strtotime($user_data['last_login'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-1">
                        <div class="d-flex align-items-center gap-1">
                            <?php if ($user_data['banned']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="unban_user">
                                    <input type="hidden" name="username" value="<?= htmlspecialchars($user_data['username']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Unban this user?')">
                                        <i class="mdi mdi-account-check me-1"></i>Unban
                                    </button>
                                </form>
                            <?php else: ?>
                                <button onclick="openBanModal('<?= htmlspecialchars($user_data['username']) ?>')" class="btn btn-sm btn-outline-danger">
                                    <i class="mdi mdi-ban me-1"></i>Ban
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($users_query->num_rows === 0): ?>
        <div class="p-3 text-center text-muted">
            <i class="mdi mdi-account-group fs-1 mb-2"></i>
            <p class="mb-0">No users found</p>
        </div>
    <?php endif; ?>
</div>

<!-- Ban User Modal -->
<div class="modal fade" id="banUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold">Ban User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="ban_user">
                    <input type="hidden" name="username" id="banUsername" value="">
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Username</label>
                        <input type="text" id="banUsernameDisplay" class="form-control bg-dark text-white border-secondary" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-muted">Ban Reason</label>
                        <textarea name="reason" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Reason for banning this user" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Ban User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openBanModal(username) {
    document.getElementById('banUsername').value = username;
    document.getElementById('banUsernameDisplay').value = username;
    var modal = new bootstrap.Modal(document.getElementById('banUserModal'));
    modal.show();
}

function closeModal(modalId) {
    var modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}
</script>
