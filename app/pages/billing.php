<?php // Billing Page ?>
<h4 class="fw-bold mb-4">Billing & Subscription</h4>

<div class="row g-3">
    <!-- Current Plan -->
    <div class="col-lg-8">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Current Plan</h5>

                <div class="card bg-primary bg-opacity-10 border-primary border-opacity-50 mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-0">Free Plan</h4>
                                <p class="text-muted mt-1 mb-0">Perfect for getting started</p>
                            </div>
                            <div class="text-end">
                                <h3 class="fw-bold mb-0">$0</h3>
                                <p class="small text-muted mb-0">per month</p>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-semibold mb-3">Plan Features</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <li class="d-flex align-items-center gap-2">
                        <i class="mdi mdi-check text-success"></i>
                        <span>Up to 3 applications</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="mdi mdi-check text-success"></i>
                        <span>100 license keys per app</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="mdi mdi-check text-success"></i>
                        <span>Basic support</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Upgrade -->
    <div class="col-lg-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Upgrade</h5>
                <p class="text-muted small mb-3">Unlock more features with our Pro plan</p>
                <button class="btn btn-primary w-100">
                    <i class="mdi mdi-star me-2"></i> Upgrade to Pro
                </button>
            </div>
        </div>
    </div>
</div>
