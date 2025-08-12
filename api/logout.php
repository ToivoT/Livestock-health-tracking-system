<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}

// Log the logout action (optional - for audit purposes)
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Unknown';

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with logout confirmation
header('Location: /pages/login.php?logged_out=1');
exit;
?>