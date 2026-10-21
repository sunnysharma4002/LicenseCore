<?php
$seller_key = md5($ownerid);
$seller_link = SITE_URL . '/api/seller/?sellerkey=' . $seller_key . '&type=add&expiry=1&mask=******-******-******-******-******-******&level=1&amount=1&character=&note=&format=json';
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Seller Settings</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    Manage your seller settings here.
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
            </div>
        </div>
    </div>
</div>