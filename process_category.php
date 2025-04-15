<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle add category
if (isset($_POST['add_category'])) {
    try {
        $category_name = $_POST['category_name'];
        $description = $_POST['description'];

        // Check if category name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
        $stmt->execute([$category_name]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Category name already exists");
        }

        $stmt = $pdo->prepare("
            INSERT INTO categories (category_name, description) 
            VALUES (?, ?)
        ");
        $stmt->execute([$category_name, $description]);

        $_SESSION['success'] = "Category added successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding category: " . $e->getMessage();
    }
}

// Handle edit category
if (isset($_POST['edit_category'])) {
    try {
        $category_id = $_POST['category_id'];
        $category_name = $_POST['category_name'];
        $description = $_POST['description'];

        // Check if category name already exists for other categories
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ? AND category_id != ?");
        $stmt->execute([$category_name, $category_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Category name already exists");
        }

        $stmt = $pdo->prepare("
            UPDATE categories 
            SET category_name = ?, description = ? 
            WHERE category_id = ?
        ");
        $stmt->execute([$category_name, $description, $category_id]);

        $_SESSION['success'] = "Category updated successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating category: " . $e->getMessage();
    }
}

// Redirect back to categories page
header('Location: categories.php');
exit();
?> 