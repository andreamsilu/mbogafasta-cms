<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';
require_once 'includes/helpers.php';

header('Content-Type: application/json');

// Check if user is a restaurant manager
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get form data
    $user_id = $_POST['user_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone_number)) {
        throw new Exception('All required fields must be filled');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Email is already taken by another user');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // If password change is requested
        if (!empty($current_password) && !empty($new_password)) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();

            if (!password_verify($current_password, $current_hash)) {
                throw new Exception('Current password is incorrect');
            }

            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
        }

        // Update user information
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, phone_number = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$name, $email, $phone_number, $user_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 