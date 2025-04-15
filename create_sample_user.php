<?php
require_once 'config/database.php';
require_once 'includes/helpers.php';

try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();

    if ($adminCount > 0) {
        echo "Admin user already exists.\n";
        exit();
    }

    // Create admin user
    $userData = [
        'role_id' => 1, // Admin role
        'name' => 'Admin User',
        'email' => 'admin@mbogafasta.com',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'phone_number' => '255712345678',
        'is_verified' => 1
    ];

    $stmt = $pdo->prepare("
        INSERT INTO users (role_id, name, email, password_hash, phone_number, is_verified)
        VALUES (:role_id, :name, :email, :password_hash, :phone_number, :is_verified)
    ");

    $stmt->execute($userData);
    $userId = $pdo->lastInsertId();

    // Log the creation
    logSystemEvent('user_created', "Sample admin user created with ID: $userId");

    echo "Sample admin user created successfully!\n";
    echo "Username: admin@mbogafasta.com\n";
    echo "Password: admin123\n";
    echo "Please change these credentials after first login.\n";

} catch (PDOException $e) {
    echo "Error creating sample user: " . $e->getMessage() . "\n";
    logSystemEvent('error', "Failed to create sample user: " . $e->getMessage());
}
?> 