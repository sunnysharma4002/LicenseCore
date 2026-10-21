<?php
// Admin Dashboard Home Page
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

<!-- Statistics Section -->
<div class="row g-3 mb-3">
    <!-- Total Users -->
    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Total Users</p>
                        <h3 class="text-primary fw-bold mb-0"><?= number_format($stats['total_users']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-primary bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-account-group text-primary fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="mdi mdi-shield-account text-danger me-1"></i>
                    <?= number_format($stats['banned_users']) ?> banned
                </small>
            </div>
        </div>
    </div>

    <!-- Total Applications -->
    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Total Applications</p>
                        <h3 class="text-info fw-bold mb-0"><?= number_format($stats['total_apps']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-info bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-cubes text-info fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-arrow-up text-success me-1"></i> Active systems</small>
            </div>
        </div>
    </div>

    <!-- Active License Keys -->
    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">Active Licenses</p>
                        <h3 class="text-success fw-bold mb-0"><?= number_format($stats['total_keys']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-success bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-key text-success fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-arrow-up text-success me-1"></i> Valid keys</small>
            </div>
        </div>
    </div>

    <!-- Total End Users -->
    <div class="col-md-6 col-lg-3">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted small fw-medium mb-1">End Users</p>
                        <h3 class="text-warning fw-bold mb-0"><?= number_format($stats['total_users']) ?></h3>
                    </div>
                    <div style="width:48px;height:48px" class="rounded bg-warning bg-opacity-25 d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-account-group text-warning fs-4"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2"><i class="mdi mdi-clock-outline text-primary me-1"></i> Last 24h: <?= number_format($stats['recent_logs']) ?> logs</small>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div class="row g-3">
    <!-- Recent Applications -->
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary bg-transparent px-3 py-2">
                <h5 class="fw-bold mb-0">Recent Applications</h5>
            </div>
            <div>
                <?php if ($recent_apps->num_rows > 0): ?>
                    <?php while ($app = $recent_apps->fetch_assoc()): ?>
                    <div class="px-3 py-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:48px;height:48px" class="rounded bg-primary bg-opacity-25 d-flex align-items-center justify-content-center">
                                    <i class="mdi mdi-cube-outline text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold mb-0"><?= htmlspecialchars($app['name']) ?></h6>
                                    <small class="text-muted">Owner: <?= htmlspecialchars($app['owner_name']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="fw-semibold text-primary"><?= htmlspecialchars(substr($app['secret'], 0, 12)) ?>...</small><br>
                                <small class="text-muted"><?= date('M j, H:i', strtotime($app['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <i class="mdi mdi-inbox fs-1 mb-2"></i>
                        <p class="mb-0">No applications yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100 text-start" data-bs-toggle="modal" data-bs-target="#createAppModal">
                        <i class="mdi mdi-plus me-2"></i> Create App
                    </button>
                    <a href="?page=users" class="btn btn-secondary w-100 text-start">
                        <i class="mdi mdi-account-group me-2"></i> Manage Users
                    </a>
                    <a href="?page=applications" class="btn btn-secondary w-100 text-start">
                        <i class="mdi mdi-cubes me-2"></i> View Apps
                    </a>
                    <a href="?page=logs" class="btn btn-secondary w-100 text-start">
                        <i class="mdi mdi-history me-2"></i> System Logs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Users & License Keys -->
<div class="row g-3 mt-3">
    <!-- Recent Users -->
    <div class="col-lg-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary bg-transparent px-3 py-2">
                <h5 class="fw-bold mb-0">Recent Users</h5>
            </div>
            <div>
                <?php if ($recent_users->num_rows > 0): ?>
                    <?php while ($user_data = $recent_users->fetch_assoc()): ?>
                    <div class="px-3 py-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:40px;height:40px" class="rounded-circle bg-success bg-opacity-25 d-flex align-items-center justify-content-center">
                                    <i class="mdi mdi-account text-success small"></i>
                                </div>
                                <div>
                                    <p class="fw-semibold small mb-0"><?= htmlspecialchars($user_data['username']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($user_data['email']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <?php if ($user_data['banned']): ?>
                                    <span class="badge bg-danger">Banned</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <i class="mdi mdi-account-group fs-1 mb-2"></i>
                        <p class="mb-0">No users yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent License Keys -->
    <div class="col-lg-6">
        <div class="card bg-dark border-secondary">
            <div class="card-header border-secondary bg-transparent px-3 py-2">
                <h5 class="fw-bold mb-0">Recent License Keys</h5>
            </div>
            <div>
                <?php if ($recent_keys->num_rows > 0): ?>
                    <?php while ($key = $recent_keys->fetch_assoc()): ?>
                    <div class="px-3 py-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:40px;height:40px" class="rounded bg-warning bg-opacity-25 d-flex align-items-center justify-content-center">
                                    <i class="mdi mdi-key text-warning small"></i>
                                </div>
                                <div>
                                    <p class="font-monospace small fw-semibold mb-0"><?= htmlspecialchars(substr($key['license_key'], 0, 20)) ?>...</p>
                                    <small class="text-muted"><?= htmlspecialchars($key['app_name']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= $key['status'] === 'active' ? 'success' : 'danger' ?>"><?= htmlspecialchars($key['status']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <i class="mdi mdi-key fs-1 mb-2"></i>
                        <p class="mb-0">No license keys yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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

<script>
function openModal(modalId) {
    var modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function closeModal(modalId) {
    var modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}
</script>
