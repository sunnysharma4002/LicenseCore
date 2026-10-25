<?php
require_once '../includes/enhanced.php';

if (is_logged_in()) {
    header("Location: " . SITE_URL . "/app/");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } else {
        $identifier = 'login:' . get_ip();
        if (!check_rate_limit($identifier, 5, 900)) {
            $error = 'Too many login attempts. Please try again later.';
        } else {
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (!$user) {
                $error = 'Username or password incorrect';
            } elseif ($user['banned']) {
                $error = 'Your account has been banned';
            } elseif (!verify_password($password, $user['password'])) {
                $error = 'Username or password incorrect';
            } else {
                // Check 2FA if enabled
                if ($user['twofactor_enabled']) {
                    $twofactor_code = sanitize($_POST['licensecoretwofactor'] ?? '');
                    if (empty($twofactor_code)) {
                        $error = '2FA code is required';
                    } else {
                        // Verify 2FA using the stored secret
                        $secret = $user['twofactor_secret'];
                        if (!empty($secret)) {
                            require_once '../includes/2fa.php';
                            $tfa = new PHPGangsta_GoogleAuthenticator();
                            if (!$tfa->verifyCode($secret, $twofactor_code, 2)) {
                                $error = 'Invalid 2FA code';
                            }
                        } else {
                            $error = '2FA not configured properly';
                        }
                    }
                }
                
                if (empty($error)) {
                    $ip = get_ip();
                    $stmt = $conn->prepare("UPDATE accounts SET last_login = NOW(), ip = ? WHERE id = ?");
                    $stmt->bind_param("si", $ip, $user['id']);
                    $stmt->execute();
                    
                    $_SESSION['username'] = $username;
                    $_SESSION['ownerid'] = $user['ownerid'];
                    $_SESSION['user_id'] = $user['id'];
                    
                    log_action($user['ownerid'], 'login', $username, 'User logged in', $ip);
                    
                    header("Location: " . SITE_URL . "/app/");
                    exit();
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>Sign In - <?= SITE_NAME ?></title>
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
                                    <h5 class="text-primary">Welcome Back !</h5>
                                    <p class="text-muted">Sign in to continue to <?= SITE_NAME ?>.</p>
                                </div>
                                <?php if ($error): ?>
                                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="ri-error-warning-line me-1 align-middle"></i> <?= $error ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                                <div class="p-2 mt-4">
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" required name="username" placeholder="Enter username" autocomplete="on" />
                                        </div>
                                        <div class="mb-3">
                                            <div class="float-end">
                                                <a href="../forgot/" class="text-muted">Forgot password?</a>
                                            </div>
                                            <label class="form-label" for="password-input">Password</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" class="form-control pe-5 password-input" placeholder="Enter password" required name="password" autocomplete="on" />
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon material-shadow-none" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">2FA Authenticator</label>
                                            <input type="text" class="form-control" name="licensecoretwofactor" placeholder="Enter 2FA If You Enable">
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="auth-remember-check">
                                            <label class="form-check-label" for="auth-remember-check">Remember me</label>
                                        </div>
                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" name="login">Sign In</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="mb-0">Don't have an account ? <a href="<?= SITE_URL ?>/register/" class="fw-semibold text-primary text-decoration-underline"> Signup </a></p>
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
