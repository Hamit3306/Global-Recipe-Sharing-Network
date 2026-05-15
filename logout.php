<?php
// Initialize the session to access current session data
session_start();

// Unset all session variables (removes user_id, username, role)
$_SESSION = array();

// Destroy the session cookie (optional but recommended for complete cleanup)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session on the server
session_destroy();

// Redirect user to the homepage
header("Location: index.php");
exit;
?>