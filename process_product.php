<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get product ID
    $product_id = $_POST['product_id'] ?? null;
    if (!$product_id) {
        throw new Exception('Product ID is required');
    }

    // Prepare the update data
    $updateData = [
        'product_name' => $_POST['product_name'],
        'category_id' => $_POST['category_id'],
        'price' => $_POST['price'],
        'stock_quantity' => $_POST['stock_quantity'],
        'description' => $_POST['description'] ?? null
    ];

    // Handle image upload if a new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');
        }

        $fileName = uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // Update product image in database
            $stmt = $pdo->prepare("
                INSERT INTO products_images (product_id, image_url) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE image_url = ?
            ");
            $stmt->execute([$product_id, $targetPath, $targetPath]);
        } else {
            throw new Exception('Failed to upload image');
        }
    }

    // Update product details
    $stmt = $pdo->prepare("
        UPDATE products 
        SET product_name = ?, 
            category_id = ?, 
            price = ?, 
            stock_quantity = ?, 
            description = ?
        WHERE product_id = ?
    ");
    
    $stmt->execute([
        $updateData['product_name'],
        $updateData['category_id'],
        $updateData['price'],
        $updateData['stock_quantity'],
        $updateData['description'],
        $product_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'product_id' => $product_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 