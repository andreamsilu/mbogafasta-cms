<?php
// Application Constants
define('APP_NAME', 'Mbogafasta CMS');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/mbogafasta-cms');
define('APP_ROOT', dirname(__DIR__));

// File Upload Constants
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Session Constants
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'mbogafasta_session');

// Pagination Constants
define('ITEMS_PER_PAGE', 10);

// Date/Time Constants
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Security Constants
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL_CHARS', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_UPPERCASE', true);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?> 