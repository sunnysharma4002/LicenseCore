<?php
require_once '../includes/enhanced.php';
session_destroy();
header("Location: " . SITE_URL . "/login/");
exit();
?>
