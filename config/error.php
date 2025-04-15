<?php
// Error reporting settings
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Log error settings
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Set error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logDir = dirname(__DIR__) . '/logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $errorLog = $logDir . 'php_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $errorMessage = sprintf(
        "[%s] [PHP Error] [User: %s] [IP: %s] [Browser: %s] [File: %s] [Line: %d] %s\n",
        $timestamp,
        $user_id,
        $ip,
        $userAgent,
        $errfile,
        $errline,
        $errstr
    );

    error_log($errorMessage, 3, $errorLog);

    // Display error in development environment
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Error:</strong> $errstr<br>";
        echo "<strong>File:</strong> $errfile<br>";
        echo "<strong>Line:</strong> $errline<br>";
        echo "</div>";
    }

    return true;
}

// Set exception handler
function customExceptionHandler($exception) {
    $logDir = dirname(__DIR__) . '/logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $exceptionLog = $logDir . 'exceptions.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $exceptionMessage = sprintf(
        "[%s] [Exception] [User: %s] [IP: %s] [Browser: %s] [File: %s] [Line: %d] %s\nStack Trace:\n%s\n",
        $timestamp,
        $user_id,
        $ip,
        $userAgent,
        $exception->getFile(),
        $exception->getLine(),
        $exception->getMessage(),
        $exception->getTraceAsString()
    );

    error_log($exceptionMessage, 3, $exceptionLog);

    // Display exception in development environment
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div class='alert alert-danger'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Stack Trace:</strong><br><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    }
}

// Register error and exception handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Define development mode
define('DEVELOPMENT_MODE', true); // Set to false in production 