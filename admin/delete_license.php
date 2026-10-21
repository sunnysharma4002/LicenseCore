<?php
include '../includes/config.php';
require_login();

$user = get_current_user_data();
$license_id = $_GET['id'] ?? 0;

// Get license and verify it belongs to user's app
$license = $conn->query("SELECT l.*, a.user_id FROM licenses l 
    JOIN applications a ON l.app_id = a.id 
    WHERE l.id = $license_id AND a.user_id = {$user['id']}")->fetch_assoc();

if (!$license) {
    header("Location: dashboard.php");
    exit();
}

$app_id = $license['app_id'];

// Delete activations first
$conn->query("DELETE FROM activations WHERE license_id = $license_id");

// Delete license
$conn->query("DELETE FROM licenses WHERE id = $license_id");

header("Location: app.php?id=$app_id");
exit();
?>
