<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mbogafastadb');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('SITE_NAME', 'Mbogafasta CMS');
define('SITE_URL', 'http://localhost/mbogafasta-cms');
define('UPLOAD_DIR', 'uploads/');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/error.log');

// Timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Include required files
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php'; 