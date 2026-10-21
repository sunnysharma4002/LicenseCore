<?php
$action_error = '';
$action_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ban_user'])) {
        $target_user = sanitize($_POST['username'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');
        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 1, ban_reason = ? WHERE username = ?");
            $stmt->bind_param("ss", $reason, $target_user);
            $action_success = $stmt->execute() ? 'User banned successfully' : 'Failed to ban user';
        }
    }
    if (isset($_POST['unban_user'])) {
        $target_user = sanitize($_POST['username'] ?? '');
        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 0, ban_reason = NULL WHERE username = ?");
            $stmt->bind_param("s", $target_user);
            $action_success = $stmt->execute() ? 'User unbanned successfully' : 'Failed to unban user';
        }
    }
    if (isset($_POST['change_role'])) {
        $target_user = sanitize($_POST['username'] ?? '');
        $new_role = sanitize($_POST['role'] ?? '');
        $allowed_roles = ['owner', 'manager', 'developer', 'tester', 'seller'];
        if (!empty($target_user) && in_array($new_role, $allowed_roles)) {
            $stmt = $conn->prepare("UPDATE accounts SET role = ? WHERE username = ?");
            $stmt->bind_param("ss", $new_role, $target_user);
            $action_success = $stmt->execute() ? 'Role updated successfully' : 'Failed to update role';
        }
    }
    if (isset($_POST['delete_user'])) {
        $target_user = sanitize($_POST['username'] ?? '');
        $confirm = sanitize($_POST['confirm'] ?? '');
        if ($confirm === 'DELETE' && !empty($target_user)) {
            $stmt = $conn->prepare("DELETE FROM accounts WHERE username = ?");
            $stmt->bind_param("s", $target_user);
            $action_success = $stmt->execute() ? 'User deleted successfully' : 'Failed to delete user';
        } else {
            $action_error = 'Type DELETE to confirm';
        }
    }
}

$users_query = $conn->query("
    SELECT a.*,
        (SELECT COUNT(*) FROM apps WHERE ownerid = a.ownerid) as app_count,
        (SELECT COUNT(*) FROM `keys` k JOIN apps app ON k.app = app.secret WHERE app.ownerid = a.ownerid AND k.status = 'active') as key_count
    FROM accounts a ORDER BY a.created_at DESC
");
$roles = ['owner', 'manager', 'developer', 'tester', 'seller'];
?>
<div class="row">
    <div class="col-lg-12">
        <h5 class="mb-3">Admin - User Management</h5>

        <?php if ($action_error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($action_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($action_success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($action_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">User Management</h5>
                <span class="text-muted small">Total: <?= $users_query->num_rows ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Username</th><th>Email</th><th>Role</th><th>Apps</th><th>Keys</th><th>Status</th><th>Expires</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $users_query->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Change role for <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?')">
                                        <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                                        <select name="role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="change_role" value="1">
                                    </form>
                                </td>
                                <td><?= $u['app_count'] ?></td>
                                <td><?= $u['key_count'] ?></td>
                                <td>
                                    <?php if ($u['banned']): ?>
                                    <span class="badge bg-danger-subtle text-danger">Banned</span>
                                    <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $u['expires'] > 0 ? date('Y-m-d', $u['expires']) : 'Never' ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="ri-more-2-line"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php if ($u['banned']): ?>
                                            <li><form method="post"><input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>"><button class="dropdown-item text-success" name="unban_user"><i class="ri-check-line me-1"></i> Unban</button></form></li>
                                            <?php else: ?>
                                            <li><button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#ban-modal-<?= $u['id'] ?>"><i class="ri-prohibited-line me-1"></i> Ban</button></li>
                                            <?php endif; ?>
                                            <li><button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#delete-modal-<?= $u['id'] ?>"><i class="ri-delete-bin-line me-1"></i> Delete</button></li>
                                        </ul>
                                    </div>

                                    <!-- Ban Modal -->
                                    <div class="modal fade" id="ban-modal-<?= $u['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title">Ban User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form method="post"><div class="modal-body">
                                                <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                                                <div class="mb-3"><label class="form-label">Reason</label><input type="text" class="form-control" name="reason" placeholder="Ban reason" required></div>
                                            </div><div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="ban_user" class="btn btn-danger">Ban User</button>
                                            </div></form>
                                        </div></div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="delete-modal-<?= $u['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title">Delete User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form method="post"><div class="modal-body">
                                                <input type="hidden" name="username" value="<?= htmlspecialchars($u['username']) ?>">
                                                <div class="alert alert-danger">This will permanently delete this user and all associated data.</div>
                                                <div class="mb-3"><label class="form-label">Type "DELETE" to confirm</label><input type="text" class="form-control" name="confirm" placeholder="DELETE" required></div>
                                            </div><div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
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