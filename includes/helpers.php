<?php
/**
 * Error and success message handling
 */

// Function to set error message
function setError($message) {
    $_SESSION['error'] = $message;
    logEvent('error', $message);
}

// Function to set success message
function setSuccess($message) {
    $_SESSION['success'] = $message;
    logEvent('success', $message);
}

// Function to display error message
function displayError() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>' . 
                htmlspecialchars($_SESSION['error']) . 
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['error']);
    }
}

// Function to display success message
function displaySuccess() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>' . 
                htmlspecialchars($_SESSION['success']) . 
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['success']);
    }
}

// Function to handle database errors
function handleDatabaseError($e) {
    error_log("Database Error: " . $e->getMessage());
    logEvent('database_error', $e->getMessage());
    setError("A database error occurred. Please try again later.");
}

// Function to handle file upload errors
function handleFileUploadError($e) {
    error_log("File Upload Error: " . $e->getMessage());
    logEvent('file_upload_error', $e->getMessage());
    setError("File upload failed: " . $e->getMessage());
}

// Function to validate required fields
function validateRequiredFields($fields, $data) {
    $errors = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst($field) . " is required.";
        }
    }
    if (!empty($errors)) {
        logEvent('validation_error', implode(', ', $errors));
    }
    return $errors;
}

// Function to validate email
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logEvent('validation_error', "Invalid email format: $email");
        return "Invalid email format.";
    }
    return null;
}

// Function to validate file upload
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = "File size exceeds limit.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = "File upload was incomplete.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = "No file was uploaded.";
                break;
            default:
                $errors[] = "File upload failed.";
        }
        logEvent('file_upload_error', implode(', ', $errors));
        return $errors;
    }

    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
    }

    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds " . ($maxSize / 1024 / 1024) . "MB limit.";
    }

    if (!empty($errors)) {
        logEvent('file_validation_error', implode(', ', $errors));
    }

    return $errors;
}

// Function to display validation errors
function displayValidationErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

// Function to log events
function logEvent($type, $message, $user_id = null) {
    $logDir = 'logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logFile = $logDir . 'events.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? 'guest');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logMessage = sprintf(
        "[%s] [%s] [User: %s] [IP: %s] [Browser: %s] %s\n",
        $timestamp,
        strtoupper($type),
        $user_id,
        $ip,
        $userAgent,
        $message
    );

    error_log($logMessage, 3, $logFile);
}

// Function to log user actions
function logUserAction($action, $details = '') {
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $username = $_SESSION['username'] ?? 'guest';
    $message = "User action: $action - User: $username ($user_id)";
    if ($details) {
        $message .= " - Details: $details";
    }
    logEvent('user_action', $message, $user_id);
}

// Function to log system events
function logSystemEvent($event, $details = '') {
    $message = "System event: $event";
    if ($details) {
        $message .= " - Details: $details";
    }
    logEvent('system', $message);
}

// Function to log security events
function logSecurityEvent($event, $details = '') {
    $message = "Security event: $event";
    if ($details) {
        $message .= " - Details: $details";
    }
    logEvent('security', $message);
}

/**
 * Upload an image file with validation and security checks
 * 
 * @param array $file The $_FILES array element
 * @param string $targetDir The target directory within uploads/
 * @param string $filename Optional custom filename (without extension)
 * @return array ['success' => bool, 'message' => string, 'path' => string]
 */
function uploadImage($file, $targetDir, $filename = null) {
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File too large'];
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file uploaded'];
        default:
            return ['success' => false, 'message' => 'Unknown upload error'];
    }

    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 10MB'];
    }

    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed'];
    }

    // Generate secure filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $filename ?: uniqid('img_', true);
    $filename = preg_replace("/[^a-zA-Z0-9]/", "_", $filename);
    $new_filename = $filename . '.' . $extension;

    // Create target directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/' . $targetDir;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Move uploaded file
    $target_path = $upload_dir . '/' . $new_filename;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    // Return success with relative path
    return [
        'success' => true,
        'message' => 'File uploaded successfully',
        'path' => 'uploads/' . $targetDir . '/' . $new_filename
    ];
}

/**
 * Delete an uploaded image
 * 
 * @param string $imagePath The relative path to the image
 * @return bool True if successful, false otherwise
 */
function deleteImage($imagePath) {
    $fullPath = __DIR__ . '/../' . $imagePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Generate HTML for image preview
 * 
 * @param string $imagePath The path to the image
 * @param string $altText Alternative text for the image
 * @param string $class CSS classes to apply
 * @param int $width Width in pixels
 * @param int $height Height in pixels
 * @return string HTML for the image
 */
function getImagePreview($imagePath, $altText = '', $class = '', $width = 200, $height = 200) {
    if (empty($imagePath)) {
        return '<div class="image-placeholder ' . $class . '" style="width: ' . $width . 'px; height: ' . $height . 'px;">
                    <i class="fas fa-image"></i>
                </div>';
    }

    return '<img src="' . htmlspecialchars($imagePath) . '" 
                 alt="' . htmlspecialchars($altText) . '" 
                 class="' . $class . '" 
                 style="max-width: ' . $width . 'px; max-height: ' . $height . 'px; object-fit: cover;">';
}

/**
 * Generate HTML for image upload preview
 * 
 * @param string $inputId The ID of the file input element
 * @param string $previewId The ID of the preview container
 * @return string JavaScript code for image preview
 */
function getImageUploadPreviewScript($inputId, $previewId) {
    return "
    <script>
        document.getElementById('$inputId').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('$previewId');
                    preview.innerHTML = `<img src='\${e.target.result}' style='max-width: 200px; max-height: 200px; object-fit: cover;'>`;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>";
} 