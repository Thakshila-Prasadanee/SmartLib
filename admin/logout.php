<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store user info for logout message (optional)
$user_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'User';

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any authentication cookies if they exist
if (isset($_COOKIE['remember_admin'])) {
    setcookie('remember_admin', '', time() - 3600, '/');
}

if (isset($_COOKIE['admin_token'])) {
    setcookie('admin_token', '', time() - 3600, '/');
}

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out. Thank you for using SmartLib!";

// Redirect to login page
header("Location: ../auth/login.php");
exit();
?>
