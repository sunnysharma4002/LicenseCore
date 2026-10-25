<?php
if (!$selected_app_data) {
    echo '<div class="text-center py-5"><i class="ri-apps-line fs-1 text-muted mb-3 d-block"></i><h5 class="text-muted">Select an application first</h5><p class="text-muted">Choose an application to view its seller key and link.</p></div>';
    return;
}
$seller_key = $selected_app_data['seller_key'] ?? substr(hash('sha256', 'LicenseCore:seller:' . $selected_app_data['secret']), 0, 32);
if (empty($_SESSION['seller_csrf'])) $_SESSION['seller_csrf'] = bin2hex(random_bytes(32));
$seller_link = SITE_URL . '/api/seller/?sellerkey=' . $seller_key . '&type=add&expiry=1&mask=******-******-******-******-******-******&level=1&amount=1&character=&note=&format=json';
?>
<div class="row">
    <div class="col-lg-12">
        <?php if (!empty($seller_error)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($seller_error) ?></div>
        <?php endif; ?>
        <?php if (!empty($seller_success)): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($seller_success) ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Seller Settings</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    Seller API for <strong><?= htmlspecialchars($selected_app_data['name']) ?></strong>.
                    This key generates licenses only for this application. Use Refresh Seller Key below to replace it.
                    <br>
                    Join Our Discord server <a href="./?page=help" target="_blank"><strong>For Support</strong></a>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary border-opacity-25 mb-0">
                            <div class="card-body text-center">
                                <i class="ri-user-star-line fs-2 text-primary mb-2 d-block"></i>
                                <h6 class="fw-semibold mb-0"><?= htmlspecialchars($user['role'] ?? 'seller') ?></h6>
                                <small class="text-muted">Account Role</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success border-opacity-25 mb-0">
                            <div class="card-body text-center">
                                <i class="ri-calendar-check-line fs-2 text-success mb-2 d-block"></i>
                                <h6 class="fw-semibold mb-0"><?= isset($user['expires']) && $user['expires'] > 0 ? date('Y-m-d', $user['expires']) : 'Expired' ?></h6>
                                <small class="text-muted">Expiry Date</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info border-opacity-25 mb-0">
                            <div class="card-body text-center">
                                <i class="ri-apps-line fs-2 text-info mb-2 d-block"></i>
                                <h6 class="fw-semibold mb-0"><?= count($applications) ?></h6>
                                <small class="text-muted">Applications</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <label class="col-2 col-form-label fw-semibold">Seller Key</label>
                    <div class="col-10">
                        <input class="form-control" value="<?= htmlspecialchars($seller_key) ?>" readonly onclick="this.select()" style="cursor:pointer;">
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <label class="col-2 col-form-label fw-semibold">Seller Link</label>
                    <div class="col-10">
                        <label class="form-control" style="height:auto;word-break:break-all;">
                            <a href="<?= htmlspecialchars($seller_link) ?>" target="_blank" class="secretlink"><?= htmlspecialchars($seller_link) ?></a>
                        </label>
                    </div>
                </div>
                <button data-bs-toggle="modal" type="button" data-bs-target="#reset-sellerkey" class="btn btn-danger waves-effect waves-light">
                    <i class="ri-refresh-line" aria-hidden="true"></i> Refresh Seller Key
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reset-sellerkey" tabindex="-1" aria-labelledby="reset-sellerkey-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="refresh_seller_key">
            <input type="hidden" name="app" value="<?= htmlspecialchars($selected_app_data['secret']) ?>">
            <input type="hidden" name="seller_csrf" value="<?= htmlspecialchars($_SESSION['seller_csrf']) ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="reset-sellerkey-title">Refresh Seller Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Generate a new seller key for <strong><?= htmlspecialchars($selected_app_data['name']) ?></strong>?</p>
                <div class="alert alert-warning mb-0">The current seller key and link will stop working. Update your shop integrations with the new link afterward.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="ri-refresh-line" aria-hidden="true"></i> Refresh Seller Key</button>
            </div>
        </form>
    </div>
</div>
