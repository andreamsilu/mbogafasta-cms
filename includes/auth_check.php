<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Log security event
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('unauthorized_access', 'Attempt to access protected page without authentication');
    }
    
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Optional: Check for specific role requirements
function requireRole($required_role) {
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != $required_role) {
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('unauthorized_role', 'User attempted to access role-restricted page');
        }
        header('Location: dashboard.php');
        exit();
    }
}
?> 