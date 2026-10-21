<?php
// Dashboard Home Page
?>

<!-- Statistics Section -->
<div class="row g-3 mb-4">
    <!-- Total Apps -->
    <div class="col-md-4">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small fw-medium mb-0">Total Applications</p>
                        <h2 class="fw-bold mt-1 mb-0"><?= $stats['total_apps'] ?? 0 ?></h2>
                    </div>
                    <div class="rounded bg-primary bg-opacity-25 d-flex align-items-center justify-content-center" style="width:64px;height:64px">
                        <i class="mdi mdi-cube-outline text-primary fs-4"></i>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0"><i class="mdi mdi-arrow-up text-success me-1"></i> 12% increase</p>
            </div>
        </div>
    </div>

    <!-- Active License Keys -->
    <div class="col-md-4">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small fw-medium mb-0">Active License Keys</p>
                        <h2 class="fw-bold mt-1 mb-0"><?= $stats['total_keys'] ?? 0 ?></h2>
                    </div>
                    <div class="rounded bg-success bg-opacity-25 d-flex align-items-center justify-content-center" style="width:64px;height:64px">
                        <i class="mdi mdi-key text-success fs-4"></i>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0"><i class="mdi mdi-arrow-up text-success me-1"></i> 8% increase</p>
            </div>
        </div>
    </div>

    <!-- Total Users -->
    <div class="col-md-4">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small fw-medium mb-0">Total Users</p>
                        <h2 class="fw-bold mt-1 mb-0"><?= $stats['total_users'] ?? 0 ?></h2>
                    </div>
                    <div class="rounded bg-info bg-opacity-25 d-flex align-items-center justify-content-center" style="width:64px;height:64px">
                        <i class="mdi mdi-account-group text-info fs-4"></i>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0"><i class="mdi mdi-arrow-up text-success me-1"></i> 5% increase</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div class="row g-3">
    <!-- Recent Applications -->
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h5 class="card-title fw-bold mb-0">Recent Applications</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($applications)): ?>
                    <?php foreach (array_slice($applications, 0, 5) as $app): ?>
                    <div class="p-3 border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#7c3aed)">
                                    <i class="mdi mdi-puzzle text-white"></i>
                                </div>
                                <div>
                                    <h6 class="fw-semibold mb-0"><?= htmlspecialchars($app['name']) ?></h6>
                                    <p class="small text-muted mt-1 mb-0">v<?= htmlspecialchars($app['version']) ?></p>
                                </div>
                            </div>
                            <div class="text-end">
                                <p class="small fw-semibold text-primary mb-0"><?= $app['active_keys'] ?> Keys</p>
                                <p class="small text-muted mb-0"><?= $app['total_users'] ?> Users</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="mdi mdi-inbox fs-3 mb-2"></i>
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
                <h5 class="card-title fw-bold mb-3">Quick Actions</h5>
                <div class="d-flex flex-column gap-2">
                    <a href="?page=applications" class="btn btn-secondary text-center fw-medium">
                        <i class="mdi mdi-plus me-2"></i> Create App
                    </a>
                    <a href="?page=api-keys" class="btn btn-secondary text-center fw-medium">
                        <i class="mdi mdi-key me-2"></i> API Keys
                    </a>
                    <a href="?page=documentation" class="btn btn-secondary text-center fw-medium">
                        <i class="mdi mdi-book-open-variant me-2"></i> Documentation
                    </a>
                    <a href="?page=support" class="btn btn-secondary text-center fw-medium">
                        <i class="mdi mdi-headset me-2"></i> Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
