<?php // API Keys Page ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold">API Keys</h4>
    <button class="btn btn-primary">
        <i class="mdi mdi-plus me-2"></i> Generate API Key
    </button>
</div>

<div class="card bg-dark border-secondary">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-3">Your API Keys</h5>
        <p class="text-muted small mb-3">API keys allow you to integrate LicenseAuth into your applications.</p>

        <div class="d-flex flex-column gap-2">
            <div class="bg-secondary rounded p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <i class="mdi mdi-key text-primary fs-5"></i>
                    <div>
                        <p class="fw-semibold mb-0">Production API Key</p>
                        <p class="font-monospace small text-muted mt-1 mb-0">sk_live_************</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm">
                        <i class="mdi mdi-eye-outline me-1"></i> Reveal
                    </button>
                    <button class="btn btn-danger btn-sm">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- API Documentation Link -->
<div class="card bg-dark border-primary border-opacity-50">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-2">API Documentation</h5>
        <p class="text-muted small mb-3">Learn how to integrate our API into your applications</p>
        <a href="?page=documentation" class="btn btn-primary">
            <i class="mdi mdi-book-open-variant me-2"></i> View Documentation
        </a>
    </div>
</div>
