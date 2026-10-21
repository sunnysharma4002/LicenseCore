<?php
// Authentication handler

// Register user
function register_user($username, $email, $password, $password_confirm) {
    global $conn;
    
    // Validation
    if (strlen($password) < 12) {
        return ['success' => false, 'error' => 'Password must be at least 12 characters'];
    }
    
    if ($password !== $password_confirm) {
        return ['success' => false, 'error' => 'Passwords do not match'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address'];
    }
    
    if (strlen($username) < 3) {
        return ['success' => false, 'error' => 'Username must be at least 3 characters'];
    }
    
    // Check if user exists
    $username_check = $conn->query("SELECT id FROM users WHERE username = '".sanitize($username)."'");
    if ($username_check->num_rows > 0) {
        return ['success' => false, 'error' => 'Username already exists'];
    }
    
    $email_check = $conn->query("SELECT id FROM users WHERE email = '".sanitize($email)."'");
    if ($email_check->num_rows > 0) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (username, email, password, plan) VALUES (
        '".sanitize($username)."',
        '".sanitize($email)."',
        '".$hashed_password."',
        'free'
    )";
    
    if ($conn->query($sql) === TRUE) {
        return ['success' => true, 'message' => 'Account created successfully. Please login.'];
    } else {
        return ['success' => false, 'error' => 'Error creating account: ' . $conn->error];
    }
}

// Login user
function login_user($username, $password) {
    global $conn;
    
    // Find user
    $result = $conn->query("SELECT id, password FROM users WHERE username = '".sanitize($username)."'");
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    
    return ['success' => true, 'message' => 'Login successful'];
}

// Logout user
function logout_user() {
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}
?>
