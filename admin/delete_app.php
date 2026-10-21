<?php
include '../includes/config.php';
require_login();

$user = get_current_user_data();
$app_id = $_GET['id'] ?? 0;

// Verify app belongs to user
$app = $conn->query("SELECT id FROM applications WHERE id = $app_id AND user_id = {$user['id']}")->fetch_assoc();

if (!$app) {
    header("Location: dashboard.php");
    exit();
}

// Delete app and its licenses
$conn->query("DELETE FROM activations WHERE license_id IN (SELECT id FROM licenses WHERE app_id = $app_id)");
$conn->query("DELETE FROM licenses WHERE app_id = $app_id");
$conn->query("DELETE FROM applications WHERE id = $app_id");

header("Location: dashboard.php");
exit();
?>
