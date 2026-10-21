<?php
$admin_stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM accounts")->fetch_assoc()['count'],
    'total_apps' => $conn->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'],
    'total_keys' => $conn->query("SELECT COUNT(*) as count FROM `keys` WHERE status = 'active'")->fetch_assoc()['count'],
    'banned_users' => $conn->query("SELECT COUNT(*) as count FROM accounts WHERE banned = 1")->fetch_assoc()['count'],
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_sessions' => $conn->query("SELECT COUNT(*) as count FROM sessions")->fetch_assoc()['count'],
];
$recent_users = $conn->query("SELECT username, email, role, created_at FROM accounts ORDER BY created_at DESC LIMIT 5");
$recent_logs = $conn->query("SELECT l.*, a.name as app_name FROM logs l LEFT JOIN apps a ON l.app = a.secret ORDER BY l.created_at DESC LIMIT 10");
?>
<div class="row">
    <div class="col-xxl-12">
        <h5 class="mb-3">Admin Dashboard</h5>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-primary border-opacity-25 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted small fw-medium mb-1">Total Users</p>
                                <h3 class="text-primary fw-bold mb-0"><?= number_format($admin_stats['total_users']) ?></h3>
                            </div>
                            <div class="rounded bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                <i class="ri-group-line text-primary fs-4"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="ri-prohibited-line text-danger me-1"></i>
                            <?= number_format($admin_stats['banned_users']) ?> banned
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info border-opacity-25 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted small fw-medium mb-1">Applications</p>
                                <h3 class="text-info fw-bold mb-0"><?= number_format($admin_stats['total_apps']) ?></h3>
                            </div>
                            <div class="rounded bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                <i class="ri-apps-line text-info fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success border-opacity-25 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted small fw-medium mb-1">Active Keys</p>
                                <h3 class="text-success fw-bold mb-0"><?= number_format($admin_stats['total_keys']) ?></h3>
                            </div>
                            <div class="rounded bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                <i class="ri-key-line text-success fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning border-opacity-25 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted small fw-medium mb-1">End Users</p>
                                <h3 class="text-warning fw-bold mb-0"><?= number_format($admin_stats['total_users']) ?></h3>
                            </div>
                            <div class="rounded bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                                <i class="ri-user-line text-warning fs-4"></i>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="ri-time-line me-1"></i>
                            <?= number_format($admin_stats['total_sessions']) ?> sessions
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Recent Users</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
                                <tbody>
                                    <?php while ($u = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($u['role']) ?></span></td>
                                        <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Recent Activity</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>App</th><th>Action</th><th>User</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php while ($log = $recent_logs->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['app_name'] ?? substr($log['app'], 0, 8).'...') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                                        <td><?= htmlspecialchars($log['user'] ?? '-') ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>