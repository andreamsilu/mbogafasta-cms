<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: restaurant_products.php');
    exit;
}

try {
    // Get database instance
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get product details
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name, r.name as restaurant_name, pi.image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id
        LEFT JOIN products_images pi ON p.product_id = pi.product_id
        WHERE p.product_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();

    if (!$product) {
        echo "<div class='alert alert-danger'>Product not found.</div>";
        exit;
    }

    // Get all categories for the dropdown
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY category_name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    require_once 'includes/header.php';
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Product</h4>
                </div>
                <div class="card-body">
                    <form id="editProductForm" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="product_name" 
                                           value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>"
                                                    <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Price (TSh)</label>
                                    <input type="number" class="form-control" name="price" step="0.01" 
                                           value="<?php echo $product['price']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="stock_quantity" 
                                           value="<?php echo $product['stock_quantity']; ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="4"><?php 
                                        echo htmlspecialchars($product['description'] ?? ''); 
                                    ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Product Image</label>
                                    <?php if (!empty($product['image_url'])): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="Current Product Image" class="img-thumbnail" style="max-height: 200px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('process_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = `product_details.php?id=${data.product_id}`;
        } else {
            alert(data.message || 'Error updating product');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the product');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 