<?php
@include_once 'includes/enhanced.php';
if (is_logged_in()) {
    header("Location: " . SITE_URL . "/app/");
    exit();
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>LicenseCore | Secure Authentication System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="LicenseCore - Secure Authentication System, C++, C#, Python, PHP, Ruby, JavaScript, VB.Net, Java, Rush, Go, Lua, Perl" name="description" />
    <meta content="LicenseCore" name="author" />
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/images/favicon.ico">
    <script src="<?= SITE_URL ?>/assets/js/layout.js"></script>
    <link href="<?= SITE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div class="auth-page-wrapper pt-5">
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="/" class="d-inline-block auth-logo">
                                    <img src="<?= SITE_URL ?>/assets/images/logo-light.png" alt="" height="35">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">Authentication System</p>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="text-center mb-5">
                            <h1 class="display-4 fw-bold text-white mb-3">The better way to manage your application with LicenseAuth</h1>
                            <p class="lead text-muted mb-4">Secure, scalable license management for your software applications</p>
                            <div class="mt-4">
                                <a href="./register/" class="btn btn-success btn-lg me-3">Get Started</a>
                                <a href="./login/" class="btn btn-outline-light btn-lg">Sign in</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center mt-5">
                    <div class="col-lg-10">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="card card-bg-fill h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="avatar-sm mx-auto mb-3">
                                            <span class="avatar-title rounded-circle bg-primary bg-soft text-primary fs-24">
                                                <i class="ri-key-2-line"></i>
                                            </span>
                                        </div>
                                        <h5 class="card-title">License Management</h5>
                                        <p class="card-text text-muted">Generate, validate, and manage license keys with ease</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-bg-fill h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="avatar-sm mx-auto mb-3">
                                            <span class="avatar-title rounded-circle bg-success bg-soft text-success fs-24">
                                                <i class="ri-shield-check-line"></i>
                                            </span>
                                        </div>
                                        <h5 class="card-title">Secure API</h5>
                                        <p class="card-text text-muted">HMAC-SHA256 signed requests with session management</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card card-bg-fill h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="avatar-sm mx-auto mb-3">
                                            <span class="avatar-title rounded-circle bg-warning bg-soft text-warning fs-24">
                                                <i class="ri-dashboard-3-line"></i>
                                            </span>
                                        </div>
                                        <h5 class="card-title">Admin Dashboard</h5>
                                        <p class="card-text text-muted">Complete oversight with analytics and user management</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy; 2021 - <?= date('Y') ?> LicenseAuth. Made with <i class="mdi mdi-heart text-danger"></i> by FENIX</p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <script src="<?= SITE_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/node-waves/waves.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/feather-icons/feather.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/plugins.js"></script>
    <script src="<?= SITE_URL ?>/assets/libs/particles.js/particles.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/pages/particles.app.js"></script>
</body>
</html>
