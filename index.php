<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';

// Log the access attempt
logSystemEvent('system_access', 'User accessing the system');

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in, log the event and redirect to dashboard
    logUserAction('auto_redirect', 'Logged in user redirected to dashboard');
    header('Location: dashboard.php');
    exit();
} else {
    // User is not logged in, log the event and redirect to login page
    logSystemEvent('auth_required', 'Unauthorized access attempt - redirecting to login');
    header('Location: login.php');
    exit();
} 
 