<?php
require_once '../includes/enhanced.php';

if (is_logged_in()) {
    header("Location: " . SITE_URL . "/app/");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';
    $agree = isset($_POST['agree']);
    
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (empty($email)) {
        $error = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (!$agree) {
        $error = 'You must agree to the Terms of Service';
    }
    
    if (empty($error)) {
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists';
        }
        
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        }
    }
    
    if (empty($error)) {
        $ownerid = generate_owner_id();
        $password_hash = hash_password($password);
        $role = 'tester';
        
        $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, ownerid, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $ownerid, $role);
        
        if ($stmt->execute()) {
            $success = 'Account created successfully!';
            $_SESSION['username'] = $username;
            $_SESSION['ownerid'] = $ownerid;
            $_SESSION['user_id'] = $stmt->insert_id;
            header("Location: " . SITE_URL . "/app/");
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>Sign Up - <?= SITE_NAME ?></title>
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
                                    <h5 class="text-primary">Create New Account</h5>
                                    <p class="text-muted">Get your free <?= SITE_NAME ?> Premium account now.</p>
                                </div>
                                <?php if ($error): ?>
                                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="ri-error-warning-line me-1 align-middle"></i> <?= $error ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                                <?php if ($success): ?>
                                <div class="alert alert-success alert-border-left alert-dismissible fade show" role="alert">
                                    <i class="ri-check-line me-1 align-middle"></i> <?= $success ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php endif; ?>
                                <div class="p-2 mt-4">
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" required name="email" placeholder="Please enter email" autocomplete="on">
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" required name="username" placeholder="Enter username" autocomplete="on">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="password-input">Password<span class="text-danger">*</span></label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" class="form-control pe-5 password-input" placeholder="Enter password" required name="password" autocomplete="on">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon material-shadow-none" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                            <p class="text-muted">Use 12 or more characters with a mix of letters, numbers &amp; symbols.</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="password-confirm">Confirm Password</label>
                                            <input type="password" class="form-control" placeholder="Confirm password" required name="password_confirmation">
                                        </div>
                                        <div class="alert alert-warning border-0" role="alert">
                                            <strong>Do NOT share your account with anyone.</strong> This is against ToS.<br>
                                            With developer plan or higher, you can create accounts for other people to use.
                                        </div>
                                        <div class="alert alert-danger border-0" role="alert">
                                            <strong>Do NOT use a fake email.</strong> You need a real email for account recovery/password reset.
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" value="1" id="agree" name="agree" required>
                                            <label class="form-check-label" for="agree">
                                                I agree to the <a href="https://licenseauth.cloud/terms" class="text-primary">Terms of Service</a>
                                            </label>
                                        </div>
                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" type="submit">Sign Up</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <p class="mb-0">Already have an account ? <a href="<?= SITE_URL ?>/login/" class="fw-semibold text-primary text-decoration-underline"> Signin </a></p>
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
