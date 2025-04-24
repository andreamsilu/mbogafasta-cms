<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/auth_check.php';

try {
    // Get database instance
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get manager's restaurant
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM restaurants r 
        WHERE r.manager_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $restaurant = $stmt->fetch();

    if (!$restaurant) {
        echo "<div class='alert alert-danger'>No restaurant found for this manager. Please contact support.</div>";
        exit;
    }

    // Get products
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name as category_name, pi.image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN products_images pi ON p.product_id = pi.product_id
        WHERE p.restaurant_id = ?
        ORDER BY p.product_name ASC
    ");
    $stmt->execute([$restaurant['restaurant_id']]);
    $products = $stmt->fetchAll();

    // Get categories for the dropdown
    $stmt = $pdo->prepare("
        SELECT * FROM categories
        ORDER BY category_name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    // Debug information
    if (empty($products)) {
        echo "<div class='alert alert-info'>No products found for this restaurant. You can add products using the 'Add Product' button.</div>";
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
                    <h4 class="mb-0"><?php echo htmlspecialchars($restaurant['name']); ?> Products</h4>
                </div>
                <div class="card-body">
                    <!-- Add Product Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="1">In Stock</option>
                                <option value="0">Out of Stock</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                 class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-thumbnail bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td>TSh <?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editProduct(<?php echo $product['product_id']; ?>)"
                                                    title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="viewProduct(<?php echo $product['product_id']; ?>)"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteProduct(<?php echo $product['product_id']; ?>)"
                                                    title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" enctype="multipart/form-data">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['restaurant_id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_name" required minlength="2">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="price" step="0.01" required min="0.01">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="stock_quantity" required min="0" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Images</label>
                        <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
                        <small class="text-muted">You can select multiple images</small>
                        <div id="imagePreview" class="mt-2 d-flex flex-wrap gap-2">
                            <!-- Image previews will be shown here -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<!-- Add DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#productsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    'copy',
                    'excel',
                    'csv',
                    'pdf',
                    'print'
                ]
            }
        ],
        order: [[1, 'asc']], // Sort by name by default
        pageLength: 10,
        responsive: true,
        language: {
            search: "Search products:",
            lengthMenu: "Show _MENU_ products per page",
            info: "Showing _START_ to _END_ of _TOTAL_ products",
            infoEmpty: "No products found",
            infoFiltered: "(filtered from _MAX_ total products)"
        }
    });

    // Custom search input
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Category filter
    $('#categoryFilter').on('change', function() {
        table.column(2).search(this.value).draw();
    });

    // Status filter
    $('#statusFilter').on('change', function() {
        table.column(5).search(this.value).draw();
    });
});

function editProduct(productId) {
    window.location.href = `edit_product.php?id=${productId}`;
}

function viewProduct(productId) {
    window.location.href = `product_details.php?id=${productId}`;
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch('process_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the product');
        });
    }
}

// Image preview functionality
document.querySelector('input[name="images[]"]').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = ''; // Clear existing previews
    
    Array.from(e.target.files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-thumbnail';
                img.style.maxWidth = '150px';
                img.style.maxHeight = '150px';
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    });
});

// Form validation
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields');
    }
});

// Save product function
function saveProduct() {
    const form = document.getElementById('addProductForm');
    const formData = new FormData(form);
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Submit form
    fetch('process_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
            modal.hide();
            
            // Show success message
            alert(data.message);
            
            // Reload the page to show the new product
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the product');
    });
}
</script>
<?php require_once 'includes/footer.php'; ?>