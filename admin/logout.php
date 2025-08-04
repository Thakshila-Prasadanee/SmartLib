<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect user type and store user info for logout message
$user_name = 'User';
$user_role = 'user'; // default
$redirect_path = '../auth/login.php'; // default redirect

// Check what type of user is logging out
if (isset($_SESSION['role'])) {
    $user_role = $_SESSION['role'];
}

if (isset($_SESSION['admin_name'])) {
    $user_name = $_SESSION['admin_name'];
    $user_role = 'admin';
} elseif (isset($_SESSION['name'])) {
    $user_name = $_SESSION['name'];
}

// Determine appropriate redirect based on role or current location
if ($user_role === 'admin') {
    $redirect_path = '../index.php';
    $logout_message = "Admin logout successful. Thank you for managing SmartLib!";
} else {
    // For regular users, redirect to index page with logout success
    $redirect_path = '../index.php';
    $logout_message = "You have been successfully logged out. Thank you for using SmartLib!";
}

// Clear any authentication cookies if they exist (both admin and user)
$cookies_to_clear = [
    'remember_admin', 
    'admin_token', 
    'remember_user', 
    'user_token',
    'smartlib_session',
    'remember_me'
];

foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// Store logout flags before destroying session (for users only)
$is_user_logout = ($user_role !== 'admin');

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

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = $logout_message;

// Set a flag for user logout to help navbar detect logout state
if ($is_user_logout) {
    $_SESSION['user_logged_out'] = true;
    $_SESSION['show_logout_message'] = true;
}

// Redirect based on user type
header("Location: " . $redirect_path);
exit();
?>
