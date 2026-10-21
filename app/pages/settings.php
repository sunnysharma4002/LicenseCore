<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-settings-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5></div>';
    return;
}
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Application Settings</h5>

                <?php if ($app_error): ?>
                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-1 align-middle"></i> <?= htmlspecialchars($app_error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                <?php if ($app_success): ?>
                <div class="alert alert-success alert-border-left alert-dismissible fade show" role="alert">
                    <i class="ri-check-double-line me-1 align-middle"></i> <?= htmlspecialchars($app_success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="update_app">
                    <input type="hidden" name="app" value="<?= htmlspecialchars($selected_app_data['secret']) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Application Name</label>
                            <input type="text" name="app_name" value="<?= htmlspecialchars($selected_app_data['name']) ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Version</label>
                            <input type="text" name="version" value="<?= htmlspecialchars($selected_app_data['version']) ?>" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Secret</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars(substr($selected_app_data['secret'], 0, 16)) ?>..." disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($selected_app_data['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Save Changes</button>
                        </div>
                    </div>
                </form>

                <div class="border-top pt-4">
                    <h5 class="text-danger mb-3">Danger Zone</h5>
                    <p class="text-muted small mb-3">Once you delete this application, there is no going back.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this application? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete_app">
                        <input type="hidden" name="app" value="<?= htmlspecialchars($selected_app) ?>">
                        <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i> Delete Application</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
