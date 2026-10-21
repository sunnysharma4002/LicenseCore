<?php
// Applications Management Page
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold">Manage Applications</h4>
    <a href="?page=applications&create=true" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="mdi mdi-plus"></i> New Application
    </a>
</div>

<?php if ($create_error): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="mdi mdi-alert-circle-outline"></i>
    <span><?= htmlspecialchars($create_error) ?></span>
</div>
<?php endif; ?>

<?php if ($create_success): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="mdi mdi-check-circle-outline"></i>
    <span><?= htmlspecialchars($create_success) ?></span>
</div>
<?php endif; ?>

<?php if (isset($_GET['create']) && $_GET['create'] === 'true'): ?>
<!-- Create Application Form -->
<div class="card bg-dark border-secondary mb-4">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-3">Create New Application</h5>

        <form method="POST">
            <input type="hidden" name="action" value="create_app">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Application Name *</label>
                    <input
                        type="text"
                        name="app_name"
                        placeholder="My Awesome Application"
                        required
                        class="form-control bg-dark border-secondary text-light"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label small mb-1">Version</label>
                    <input
                        type="text"
                        name="version"
                        placeholder="1.0.0"
                        value="1.0"
                        class="form-control bg-dark border-secondary text-light"
                    >
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small mb-1">Description</label>
                <textarea
                    name="description"
                    placeholder="Describe your application..."
                    rows="4"
                    class="form-control bg-dark border-secondary text-light"
                ></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="mdi mdi-check"></i> Create Application
                </button>
                <a href="?page=applications" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Applications Grid -->
<div class="row g-3">
    <?php if (empty($applications)): ?>
        <div class="col-12">
            <div class="card bg-dark border-secondary p-5 text-center">
                <i class="mdi mdi-inbox fs-1 text-muted mb-3"></i>
                <p class="text-muted mb-3">No applications created yet.</p>
                <a href="?page=applications&create=true" class="btn btn-primary d-inline-block mx-auto">
                    Create Your First App
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($applications as $app): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card bg-dark border-secondary h-100">
                <!-- Header -->
                <div class="card-header bg-transparent border-secondary" style="background:linear-gradient(to right,rgba(59,130,246,0.2),rgba(124,58,237,0.2))">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($app['name']) ?></h5>
                            <p class="small text-muted mt-1 mb-0">v<?= htmlspecialchars($app['version']) ?></p>
                        </div>
                        <span class="badge bg-success bg-opacity-25 text-success rounded-pill fw-medium">Active</span>
                    </div>
                </div>

                <!-- Body -->
                <div class="card-body d-flex flex-column">
                    <p class="small text-muted mb-3">
                        <?= htmlspecialchars($app['description'] ?? 'No description') ?>
                    </p>

                    <!-- Statistics -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-secondary rounded p-3">
                                <p class="small text-muted fw-medium mb-0">License Keys</p>
                                <h5 class="fw-bold mt-1 mb-0"><?= $app['active_keys'] ?></h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-secondary rounded p-3">
                                <p class="small text-muted fw-medium mb-0">Users</p>
                                <h5 class="fw-bold mt-1 mb-0"><?= $app['total_users'] ?></h5>
                            </div>
                        </div>
                    </div>

                    <!-- App Secret -->
                    <div class="mb-3 p-3 bg-secondary rounded small font-monospace text-muted text-truncate">
                        <?= htmlspecialchars(substr($app['secret'], 0, 64)) ?>...
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-column gap-2 mt-auto">
                        <a href="?page=app-overview&app=<?= urlencode($app['secret']) ?>" class="btn btn-primary btn-sm fw-medium w-100">
                            <i class="mdi mdi-chart-pie me-2"></i> View Details
                        </a>
                        <a href="?page=licenses&app=<?= urlencode($app['secret']) ?>" class="btn btn-secondary btn-sm fw-medium w-100">
                            <i class="mdi mdi-certificate-outline me-2"></i> Licenses
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
