<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-apps-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM `keys` WHERE app = ? AND status = 'active'");
$stmt->bind_param("s", $selected_app_data['secret']);
$stmt->execute();
$active_keys = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM `users` WHERE app = ?");
$stmt->bind_param("s", $selected_app_data['secret']);
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['count'];
?>

<?php if (!empty($app_success)): ?>
<div class="alert alert-success alert-border-left alert-dismissible fade show" role="alert">
    <i class="ri-check-double-line me-1 align-middle"></i> <?= htmlspecialchars($app_success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if (!empty($app_error)): ?>
<div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
    <i class="ri-error-warning-line me-1 align-middle"></i> <?= htmlspecialchars($app_error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="avatar-lg">
                <span class="avatar-title rounded bg-primary bg-gradient text-white fs-24">
                    <i class="ri-cube-line"></i>
                </span>
            </div>
            <div>
                <h4 class="mb-0"><?= htmlspecialchars($selected_app_data['name']) ?></h4>
                <p class="text-muted mb-0">Version <?= htmlspecialchars($selected_app_data['version']) ?></p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded bg-primary-subtle text-primary fs-18"><i class="ri-key-line"></i></span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-0">Active Keys</p>
                                <h5 class="mb-0"><?= $active_keys ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded bg-success-subtle text-success fs-18"><i class="ri-group-line"></i></span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-0">Total Users</p>
                                <h5 class="mb-0"><?= $total_users ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded bg-info-subtle text-info fs-18"><i class="ri-calendar-line"></i></span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-0">Created</p>
                                <h6 class="mb-0"><?= date('M d, Y', strtotime($selected_app_data['created_at'])) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded bg-success-subtle text-success fs-18"><i class="ri-checkbox-circle-line"></i></span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-0">Status</p>
                                <h6 class="mb-0 text-success">Active</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Application Details</h5>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">App Secret</label>
                            <div class="p-3 bg-light rounded">
                                <code class="small text-break"><?= htmlspecialchars($selected_app_data['secret']) ?></code>
                            </div>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <strong>Reset App Secret</strong>
                                <p class="small mb-0">Rotate the secret to invalidate old clients. You must update all clients with the new secret.</p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Reset secret? Existing clients must be updated.');">
                                <input type="hidden" name="action" value="reset_secret">
                                <input type="hidden" name="app" value="<?= htmlspecialchars($selected_app_data['secret']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="ri-refresh-line me-1"></i>Reset</button>
                            </form>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Description</label>
                            <p class="mb-0"><?= htmlspecialchars($selected_app_data['description'] ?? 'No description') ?></p>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="text-muted small mb-1">Created At</label>
                                <p class="mb-0"><?= date('M d, Y H:i', strtotime($selected_app_data['created_at'])) ?></p>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small mb-1">Last Updated</label>
                                <p class="mb-0"><?= date('M d, Y H:i', strtotime($selected_app_data['modified_at'] ?? $selected_app_data['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Quick Links</h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="?page=licenses&app=<?= urlencode($selected_app_data['secret']) ?>" class="btn btn-soft-primary text-start"><i class="ri-key-line me-2"></i> License Keys</a>
                            <a href="?page=users&app=<?= urlencode($selected_app_data['secret']) ?>" class="btn btn-soft-success text-start"><i class="ri-group-line me-2"></i> Users</a>
                            <a href="?page=vars&app=<?= urlencode($selected_app_data['secret']) ?>" class="btn btn-soft-info text-start"><i class="ri-code-line me-2"></i> Variables</a>
                            <a href="?page=webhooks&app=<?= urlencode($selected_app_data['secret']) ?>" class="btn btn-soft-warning text-start"><i class="ri-send-to-back-line me-2"></i> Webhooks</a>
                            <a href="?page=logs&app=<?= urlencode($selected_app_data['secret']) ?>" class="btn btn-soft-secondary text-start"><i class="ri-archive-line me-2"></i> Logs</a>
                            <a href="?page=app-settings&app=<?= urlencode($selected_app_data['secret']) ?>" class="btn btn-soft-dark text-start"><i class="ri-settings-line me-2"></i> Settings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
