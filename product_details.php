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
                    <h4 class="mb-0">Product Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     class="img-fluid rounded">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                            <p class="text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            
                            <div class="mb-3">
                                <h4>TSh <?php echo number_format($product['price'], 2); ?></h4>
                            </div>

                            <div class="mb-3">
                                <h5>Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available')); ?></p>
                            </div>

                            <div class="mb-3">
                                <h5>Stock Information</h5>
                                <p>
                                    <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                    </span>
                                    <span class="ms-2">Available Quantity: <?php echo $product['stock_quantity']; ?></span>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h5>Restaurant</h5>
                                <p><?php echo htmlspecialchars($product['restaurant_name']); ?></p>
                            </div>

                            <div class="mt-4">
                                <a href="restaurant_products.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Products
                                </a>
                                <button type="button" class="btn btn-primary" onclick="editProduct(<?php echo $product['product_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit Product
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editProduct(productId) {
    window.location.href = `edit_product.php?id=${productId}`;
}
</script>

<?php require_once 'includes/footer.php'; ?> 