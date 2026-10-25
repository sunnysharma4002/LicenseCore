<?php
require_once '../includes/enhanced.php';

$error = '';
$success = '';
$show_form = false;
$token = sanitize($_GET['token'] ?? '');
$email = sanitize($_GET['email'] ?? '');

if (!empty($token) && !empty($email)) {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ? AND reset_token = ? AND reset_expiry > ?");
    $time = time();
    $stmt->bind_param("ssi", $email, $token, $time);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $show_form = true;
    } else {
        $error = 'Invalid or expired reset token';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = sanitize($_POST['token'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ? AND reset_token = ? AND reset_expiry > ?");
    $time = time();
    $stmt->bind_param("ssi", $email, $token, $time);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        $error = 'Invalid or expired reset token';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $hash = hash_password($password);
        $stmt = $conn->prepare("UPDATE accounts SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        $stmt->bind_param("si", $hash, $user['id']);
        
        if ($stmt->execute()) {
            $success = 'Password has been reset successfully!';
            log_action($user['ownerid'], 'password_reset', $user['username'], 'Password reset completed');
        } else {
            $error = 'Failed to reset password';
        }
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>Reset Password - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="LicenseCore - Secure Authentication System" name="description" />
    <meta content="LicenseCore" name="author" />
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/images/favicon.ico">
    <script src="<?= SITE_URL ?>/assets/js/layout.js"></script>
    <link href="<?= SITE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>
<body>
    <div class="auth-page-wrapper pt-5">
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="../" class="d-inline-block auth-logo">
                                    <img src="<?= SITE_URL ?>/assets/images/logo-light.png" alt="" height="35">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">Authentication System</p>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4 card-bg-fill">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Reset Password</h5>
                                    <p class="text-muted">Enter your new password</p>
                                </div>
                                <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                                <?php endif; ?>
                                <?php if ($success): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                                <div class="text-center mt-3">
                                    <a href="../login/" class="btn btn-success">Sign In</a>
                                </div>
                                <?php endif; ?>
                                <?php if ($show_form && !$success): ?>
                                <div class="p-2">
                                    <form method="post">
                                        <input type="hidden" name="token" value="<?= $token ?>">
                                        <input type="hidden" name="email" value="<?= $email ?>">
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" class="form-control pe-5 password-input" placeholder="Enter new password" required name="password">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" placeholder="Confirm new password" required name="confirm">
                                        </div>
                                        <div class="text-center mt-4">
                                            <button class="btn btn-success w-100" name="reset_password">Reset Password</button>
                                        </div>
                                    </form>
                                </div>
                                <?php elseif (!$success): ?>
                                <div class="alert alert-warning text-center">Invalid or expired reset link.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="mb-0"><a href="../login/" class="fw-semibold text-primary text-decoration-underline"> Back to Login </a></p>
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
                            <p class="mb-0 text-muted">&copy; 2021 - <?= date('Y') ?> <?= SITE_NAME ?>. Made with <i class="mdi mdi-heart text-danger"></i> by FENIX</p>
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
    <script src="<?= SITE_URL ?>/assets/js/pages/password-addon.init.js"></script>
</body>
</html>
