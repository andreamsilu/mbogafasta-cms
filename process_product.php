<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';
require_once 'includes/helpers.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Validate required fields
    $required_fields = ['product_name', 'category_id', 'price', 'stock_quantity', 'restaurant_id'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception('Required fields missing: ' . implode(', ', $missing_fields));
    }

    // Get product ID (optional for new products)
    $product_id = $_POST['product_id'] ?? null;
    $is_new = !$product_id;

    // Prepare the product data
    $productData = [
        'product_name' => trim($_POST['product_name']),
        'category_id' => (int)$_POST['category_id'],
        'restaurant_id' => (int)$_POST['restaurant_id'],
        'price' => (float)$_POST['price'],
        'stock_quantity' => (int)$_POST['stock_quantity'],
        'description' => trim($_POST['description'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Additional validation
    if (strlen($productData['product_name']) < 2) {
        throw new Exception('Product name must be at least 2 characters long');
    }

    if ($productData['price'] <= 0) {
        throw new Exception('Price must be greater than 0');
    }

    if ($productData['stock_quantity'] < 0) {
        throw new Exception('Stock quantity cannot be negative');
    }

    // Handle multiple image uploads
    $uploaded_images = [];
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $upload_result = uploadImage($file, 'products');
                if ($upload_result['success']) {
                    $uploaded_images[] = $upload_result['path'];
                }
            }
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        if ($is_new) {
            // Insert new product
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    product_name, category_id, restaurant_id, price, stock_quantity, 
                    description, is_active, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $productData['product_name'],
                $productData['category_id'],
                $productData['restaurant_id'],
                $productData['price'],
                $productData['stock_quantity'],
                $productData['description'],
                $productData['is_active']
            ]);
            
            $product_id = $pdo->lastInsertId();
        } else {
            // Update existing product
            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = ?, 
                    category_id = ?, 
                    price = ?, 
                    stock_quantity = ?, 
                    description = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE product_id = ?
            ");
            
            $stmt->execute([
                $productData['product_name'],
                $productData['category_id'],
                $productData['price'],
                $productData['stock_quantity'],
                $productData['description'],
                $productData['is_active'],
                $product_id
            ]);
        }

        // Handle product images
        if (!empty($uploaded_images)) {
            // Delete existing images if this is an update
            if (!$is_new) {
                $stmt = $pdo->prepare("DELETE FROM products_images WHERE product_id = ?");
                $stmt->execute([$product_id]);
            }

            // Insert new images
            $stmt = $pdo->prepare("INSERT INTO products_images (product_id, image_url) VALUES (?, ?)");
            foreach ($uploaded_images as $image_url) {
                $stmt->execute([$product_id, $image_url]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => $is_new ? 'Product created successfully' : 'Product updated successfully',
            'product_id' => $product_id
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 