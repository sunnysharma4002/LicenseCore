<?php
require_once '../includes/enhanced.php';
require_login();

$user = get_current_user_data();

// Check if user is admin (you might want to add an admin role check here)
// For now, assume all logged in users can access admin dashboard

// Get current page
$page = sanitize($_GET['page'] ?? 'dashboard');

// Handle various admin actions
$action_error = '';
$action_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Create application for a user
    if ($action === 'create_app') {
        $app_name = sanitize($_POST['app_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $ownerid = sanitize($_POST['ownerid'] ?? '');

        if (empty($app_name) || empty($ownerid)) {
            $action_error = 'Application name and owner are required';
        } else {
            $secret = generate_app_secret();

            $stmt = $conn->prepare("INSERT INTO apps (secret, name, ownerid, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $secret, $app_name, $ownerid, $description);

            if ($stmt->execute()) {
                $action_success = 'Application created successfully!';
                log_action($secret, 'app_created_admin', $user['username'], 'Application created by admin');
            } else {
                $action_error = 'Error creating application';
            }
        }
    }

    // Delete application
    if ($action === 'delete_app') {
        $app_secret = sanitize($_POST['app_secret'] ?? '');

        if (!empty($app_secret)) {
            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                $tables = [
                    'subs', 'users', '`keys`', 'sessions', 'uservars', 'vars',
                    'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages',
                    'tokens', 'integrations'
                ];

                foreach ($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM {$table} WHERE app = ?");
                    $stmt->bind_param("s", $app_secret);
                    $stmt->execute();
                }

                $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ?");
                $stmt->bind_param("s", $app_secret);
                if ($stmt->execute()) {
                    $conn->commit();
                    $action_success = 'Application deleted successfully';
                    log_action($app_secret, 'app_deleted_admin', $user['username'], 'Application deleted by admin');
                } else {
                    throw new Exception('Failed to delete application');
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $action_error = 'Failed to delete application: ' . $e->getMessage();
            }
        }
    }

    // Ban/Unban user
    if ($action === 'ban_user') {
        $target_user = sanitize($_POST['username'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');

        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 1, ban_reason = ? WHERE username = ?");
            $stmt->bind_param("ss", $reason, $target_user);
            if ($stmt->execute()) {
                $action_success = 'User banned successfully';
                log_action('', 'user_banned', $user['username'], "User $target_user banned: $reason");
            } else {
                $action_error = 'Failed to ban user';
            }
        }
    }

    if ($action === 'unban_user') {
        $target_user = sanitize($_POST['username'] ?? '');

        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 0, ban_reason = NULL WHERE username = ?");
            $stmt->bind_param("s", $target_user);
            if ($stmt->execute()) {
                $action_success = 'User unbanned successfully';
                log_action('', 'user_unbanned', $user['username'], "User $target_user unbanned");
            } else {
                $action_error = 'Failed to unban user';
            }
        }
    }

    // Delete license key
    if ($action === 'delete_key') {
        $key_id = intval($_POST['key_id'] ?? 0);

        if ($key_id > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE id = ?");
            $stmt->bind_param("i", $key_id);
            if ($stmt->execute()) {
                $action_success = 'License key deleted successfully';
                log_action('', 'key_deleted_admin', $user['username'], 'License key deleted by admin');
            } else {
                $action_error = 'Failed to delete license key';
            }
        }
    }
}

// Get system statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM accounts")->fetch_assoc()['count'],
    'total_apps' => $conn->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'],
    'total_keys' => $conn->query("SELECT COUNT(*) as count FROM `keys` WHERE status = 'active'")->fetch_assoc()['count'],
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'banned_users' => $conn->query("SELECT COUNT(*) as count FROM accounts WHERE banned = 1")->fetch_assoc()['count'],
    'recent_logs' => $conn->query("SELECT COUNT(*) as count FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count']
];

// Get recent applications
$recent_apps = $conn->query("SELECT a.*, acc.username as owner_name FROM apps a JOIN accounts acc ON a.ownerid = acc.ownerid ORDER BY a.created_at DESC LIMIT 5");

// Get recent users
$recent_users = $conn->query("SELECT * FROM accounts ORDER BY created_at DESC LIMIT 5");

// Get all applications for management
$all_apps = $conn->query("SELECT a.*, acc.username as owner_name, 
    (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'active') as active_keys,
    (SELECT COUNT(*) FROM users WHERE app = a.secret) as total_users
    FROM apps a JOIN accounts acc ON a.ownerid = acc.ownerid ORDER BY a.created_at DESC");

// Get all users for management
$all_users = $conn->query("SELECT * FROM accounts ORDER BY created_at DESC");

// Get recent license keys
$recent_keys = $conn->query("SELECT k.*, a.name as app_name FROM `keys` k JOIN apps a ON k.app = a.secret ORDER BY k.created_at DESC LIMIT 10");
?>
<!doctype html>
<html lang="en" data-bs-theme="dark" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <style>body{background:#0f111a!important;}html,body{background-color:#0f111a!important;}</style>
    <meta charset="utf-8" />
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="LicenseAuth - Secure Authentication System" name="description" />
    <meta content="LicenseAuth" name="author" />
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/images/favicon.ico">
    <script src="<?= SITE_URL ?>/assets/js/layout.js"></script>
    <link href="<?= SITE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="<?= SITE_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>
<body>

<div id="layout-wrapper">

    <!-- ========== Topbar Start ========== -->
    <header id="page-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
                <div class="navbar-brand-box d-lg-none d-md-block">
                    <a href="?page=dashboard" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="<?= SITE_URL ?>/assets/images/logo-light.png" alt="" height="22">
                        </span>
                    </a>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <div class="dropdown d-inline-block d-sm-none">
                    <button type="button" class="btn header-item" id="page-header-search-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-search-line"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-search-dropdown">
                        <form class="p-3">
                            <div class="form-group m-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search..." />
                                    <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item noti-icon position-relative" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="ri-notification-3-line"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
                    </button>
                </div>

                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class=" d-none d-xl-inline-block ms-1 fw-medium"><?= htmlspecialchars($user['username']) ?></span>
                        <i class="ri-arrow-down-s-line d-none d-xl-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Welcome <?= htmlspecialchars($user['username']) ?>!</h6>
                        <a class="dropdown-item" href="logout.php"><i class="ri-logout-circle-line align-middle me-1"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ========== Sidebar Start ========== -->
    <div class="vertical-menu">
        <div data-simplebar class="h-100">
            <div class="navbar-brand-box" style="background: transparent;">
                <a href="?page=dashboard" class="logo logo-light">
                    <span class="logo-lg">
                        <img src="<?= SITE_URL ?>/assets/images/logo-light.png" alt="" height="35">
                        <span class="logo-txt">Admin Panel</span>
                    </span>
                </a>
            </div>

            <div id="sidebar-menu">
                <ul class="metismenu list-unstyled" id="side-menu">
                    <li class="menu-title" data-key="t-menu">Menu</li>

                    <li>
                        <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                            <i class="ri-dashboard-line"></i>
                            <span data-key="t-dashboard">Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">
                            <i class="ri-group-line"></i>
                            <span data-key="t-users">User Management</span>
                        </a>
                    </li>

                    <li>
                        <a href="?page=applications" class="<?= $page === 'applications' ? 'active' : '' ?>">
                            <i class="ri-apps-line"></i>
                            <span data-key="t-apps">Applications</span>
                        </a>
                    </li>

                    <li>
                        <a href="?page=licenses" class="<?= $page === 'licenses' ? 'active' : '' ?>">
                            <i class="ri-key-line"></i>
                            <span data-key="t-licenses">License Keys</span>
                        </a>
                    </li>

                    <li>
                        <a href="?page=logs" class="<?= $page === 'logs' ? 'active' : '' ?>">
                            <i class="ri-history-line"></i>
                            <span data-key="t-logs">System Logs</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ========== Main Content ========== -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <!-- Page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">
                                <?php
                                switch($page) {
                                    case 'dashboard': echo 'Admin Dashboard'; break;
                                    case 'users': echo 'User Management'; break;
                                    case 'applications': echo 'Application Management'; break;
                                    case 'licenses': echo 'License Management'; break;
                                    case 'logs': echo 'System Logs'; break;
                                    default: echo 'Admin Dashboard';
                                }
                                ?>
                            </h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Admin</a></li>
                                    <li class="breadcrumb-item active">
                                        <?php
                                        switch($page) {
                                            case 'dashboard': echo 'Dashboard'; break;
                                            case 'users': echo 'Users'; break;
                                            case 'applications': echo 'Applications'; break;
                                            case 'licenses': echo 'Licenses'; break;
                                            case 'logs': echo 'Logs'; break;
                                            default: echo 'Dashboard';
                                        }
                                        ?>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($action_error): ?>
                <div class="alert alert-danger alert-border-left alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-line me-1 align-middle"></i> <?= $action_error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($action_success): ?>
                <div class="alert alert-success alert-border-left alert-dismissible fade show" role="alert">
                    <i class="ri-check-double-line me-1 align-middle"></i> <?= $action_success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php
                    switch ($page) {
                        case 'dashboard':
                            include 'pages/dashboard.php';
                            break;
                        case 'users':
                            include 'pages/users.php';
                            break;
                        case 'applications':
                            include 'pages/applications.php';
                            break;
                        case 'licenses':
                            include 'pages/licenses.php';
                            break;
                        case 'logs':
                            include 'pages/logs.php';
                            break;
                        default:
                            include 'pages/dashboard.php';
                    }
                ?>
            </div>
        </div>

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <p class="mb-0 text-muted">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-sm-end d-none d-sm-block">
                            <p class="mb-0 text-muted">Made with <i class="mdi mdi-heart text-danger"></i> by FENIX</p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

</div>

<script src="<?= SITE_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/libs/simplebar/simplebar.min.js"></script>
<script src="<?= SITE_URL ?>/assets/libs/node-waves/waves.min.js"></script>
<script src="<?= SITE_URL ?>/assets/libs/feather-icons/feather.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
<script src="<?= SITE_URL ?>/assets/js/plugins.js"></script>
</body>
</html>
