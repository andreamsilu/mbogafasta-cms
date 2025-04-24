<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', 'Attempt to access product management without authentication');
    header('Location: login.php');
    exit();
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    try {
        // Check if product has any orders
        $stmt = $db->query("SELECT COUNT(*) FROM order_items WHERE product_id = ?", [$product_id]);
        $orderCount = $stmt->fetchColumn();
        
        if ($orderCount > 0) {
            $_SESSION['error'] = "Cannot delete product with existing orders";
        } else {
            // Delete product
            $db->delete('products', 'product_id = ?', [$product_id]);
            $_SESSION['success'] = "Product deleted successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
    }
    header('Location: products.php');
    exit();
}

// Fetch all products with restaurant names
try {
    $products = $db->fetchAll("
        SELECT p.*, r.name AS restaurant_name, c.category_name 
        FROM products p 
        LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        ORDER BY p.created_at DESC
    ");

    // Fetch categories for the add/edit form
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_name");
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching products: " . $e->getMessage();
    $products = [];
    $categories = [];
}
?>
<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Products Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; ?></div>
            <?php endif; ?>

            <!-- Filters Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Restaurant</label>
                            <select class="form-select" id="restaurantFilter">
                                <option value="">All Restaurants</option>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <option value="<?php echo $restaurant['restaurant_id']; ?>">
                                        <?php echo htmlspecialchars($restaurant['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price Range</label>
                            <select class="form-select" id="priceFilter">
                                <option value="">All Prices</option>
                                <option value="0-10">$0 - $10</option>
                                <option value="10-20">$10 - $20</option>
                                <option value="20-50">$20 - $50</option>
                                <option value="50-100">$50 - $100</option>
                                <option value="100+">$100+</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-secondary w-100" id="resetFilters">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Restaurant</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['product_name'] ?? 'Product Image'); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <img src="assets/images/no-image.jpg" 
                                                 alt="No Image" 
                                                 class="product-image">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['product_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($product['restaurant_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></td>
                                    <td>Tsh<?php echo number_format((float)($product['price'] ?? 0), 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo ($product['is_active'] ?? 0) ? 'success' : 'danger'; ?>">
                                            <?php echo ($product['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editProductModal"
                                                data-product='<?php echo json_encode($product); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="product_name" required minlength="2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
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
                        <label class="form-label">Restaurant <span class="text-danger">*</span></label>
                        <select class="form-select" name="restaurant_id" required>
                            <option value="">Select Restaurant</option>
                            <?php foreach ($restaurants as $restaurant): ?>
                                <option value="<?php echo $restaurant['restaurant_id']; ?>">
                                    <?php echo htmlspecialchars($restaurant['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="product_name" id="edit_product_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="edit_category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Restaurant</label>
                        <select class="form-select" name="restaurant_id" id="edit_restaurant_id" required>
                            <option value="">Select Restaurant</option>
                            <?php foreach ($restaurants as $restaurant): ?>
                                <option value="<?php echo $restaurant['restaurant_id']; ?>">
                                    <?php echo htmlspecialchars($restaurant['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                            <input type="checkbox" class="form-check-input" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#productsTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            order: [[1, 'asc']], // Sort by name by default
            pageLength: 10,
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

        // Restaurant filter
        $('#restaurantFilter').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        // Category filter
        $('#categoryFilter').on('change', function() {
            table.column(3).search(this.value).draw();
        });

        // Status filter
        $('#statusFilter').on('change', function() {
            table.column(5).search(this.value).draw();
        });

        // Price range filter
        $('#priceFilter').on('change', function() {
            var range = this.value;
            if (range) {
                var min = range.split('-')[0];
                var max = range.split('-')[1];
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        var price = parseFloat(data[4].replace('$', '')) || 0;
                        if (max === '+') {
                            return price >= min;
                        }
                        return price >= min && price <= max;
                    }
                );
            } else {
                $.fn.dataTable.ext.search.pop();
            }
            table.draw();
        });

        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#filterForm select').val('');
            $('#searchInput').val('');
            table.search('').columns().search('').draw();
            $.fn.dataTable.ext.search.pop();
            table.draw();
        });

        // Handle edit modal data population
        $('#editProductModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var product = button.data('product');
            
            $('#edit_product_id').val(product.product_id);
            $('#edit_product_name').val(product.product_name);
            $('#edit_description').val(product.description);
            $('#edit_price').val(product.price);
            $('#edit_category_id').val(product.category_id);
            $('#edit_restaurant_id').val(product.restaurant_id);
            $('#edit_is_active').prop('checked', product.is_active == 1);
        });

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
    });
</script>

<!-- Add client-side validation script -->
<script>
document.querySelector('form').addEventListener('submit', function(e) {
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
</script>

<style>
    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
    
    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .form-control, .form-select {
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
</style> 