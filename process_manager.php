<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

try {
    // Get database instance
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check if user is logged in and has appropriate role
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    // Get user's role
    $stmt = $pdo->prepare("
        SELECT r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_role = $stmt->fetchColumn();

    // Check if user has access to manager management
    if ($user_role !== 'Admin') {
        $_SESSION['error'] = "You don't have permission to manage managers";
        header('Location: dashboard.php');
        exit();
    }

    // Handle Add Manager
    if (isset($_POST['add_manager'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $password = $_POST['password'];
        $restaurant_id = !empty($_POST['restaurant_id']) ? $_POST['restaurant_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate input
        if (empty($name) || empty($email) || empty($phone_number) || empty($password)) {
            $_SESSION['error'] = "All fields are required";
            header('Location: managers.php');
            exit();
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Email already exists";
            header('Location: managers.php');
            exit();
        }

        // Get Manager role_id
        $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'Manager'");
        $stmt->execute();
        $role_id = $stmt->fetchColumn();

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone_number, password_hash, role_id, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $email,
                $phone_number,
                password_hash($password, PASSWORD_DEFAULT),
                $role_id,
                $is_active
            ]);
            $user_id = $pdo->lastInsertId();

            // Update restaurant if assigned
            if ($restaurant_id) {
                $stmt = $pdo->prepare("UPDATE restaurants SET manager_id = ? WHERE restaurant_id = ?");
                $stmt->execute([$user_id, $restaurant_id]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Manager added successfully";
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Handle Edit Manager
    if (isset($_POST['edit_manager'])) {
        $user_id = $_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        $restaurant_id = !empty($_POST['restaurant_id']) ? $_POST['restaurant_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate input
        if (empty($name) || empty($email) || empty($phone_number)) {
            $_SESSION['error'] = "All fields are required";
            header('Location: managers.php');
            exit();
        }

        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Email already exists";
            header('Location: managers.php');
            exit();
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Update user
            if ($password) {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone_number = ?, password_hash = ?, status = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $name,
                    $email,
                    $phone_number,
                    password_hash($password, PASSWORD_DEFAULT),
                    $is_active,
                    $user_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone_number = ?, status = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $name,
                    $email,
                    $phone_number,
                    $is_active,
                    $user_id
                ]);
            }

            // Update restaurant assignment
            if ($restaurant_id) {
                // Remove from old restaurant
                $stmt = $pdo->prepare("UPDATE restaurants SET manager_id = NULL WHERE manager_id = ?");
                $stmt->execute([$user_id]);

                // Assign to new restaurant
                $stmt = $pdo->prepare("UPDATE restaurants SET manager_id = ? WHERE restaurant_id = ?");
                $stmt->execute([$user_id, $restaurant_id]);
            } else {
                // Remove from current restaurant
                $stmt = $pdo->prepare("UPDATE restaurants SET manager_id = NULL WHERE manager_id = ?");
                $stmt->execute([$user_id]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Manager updated successfully";
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Handle Delete Manager
    if (isset($_POST['delete_manager'])) {
        $user_id = $_POST['user_id'];

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Remove from restaurants
            $stmt = $pdo->prepare("UPDATE restaurants SET manager_id = NULL WHERE manager_id = ?");
            $stmt->execute([$user_id]);

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();
            $_SESSION['success'] = "Manager deleted successfully";
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Redirect back to managers page
    header('Location: managers.php');
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Error in process_manager.php: " . $e->getMessage());
    
    // Set error message
    $_SESSION['error'] = "An error occurred. Please try again.";
    
    // Redirect back to managers page
    header('Location: managers.php');
    exit();
}
?> 