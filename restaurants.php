<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', 'Attempt to access restaurant management without authentication');
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

// Check if user has access to restaurant management
if ($user_role !== 'Admin' && $user_role !== 'Manager') {
    logSecurityEvent('unauthorized_access', 'User attempted to access restaurant management without proper role');
    $_SESSION['error'] = "You don't have permission to access this page";
    header('Location: dashboard.php');
    exit();
}

// Handle restaurant deletion
if (isset($_POST['delete_restaurant'])) {
    $restaurant_id = $_POST['restaurant_id'];
    
    // For managers, verify they own the restaurant
    if ($user_role === 'Manager') {
        $stmt = $pdo->prepare("SELECT manager_id FROM restaurants WHERE restaurant_id = ?");
        $stmt->execute([$restaurant_id]);
        if ($stmt->fetchColumn() != $_SESSION['user_id']) {
            $_SESSION['error'] = "You don't have permission to delete this restaurant";
            header('Location: restaurants.php');
            exit();
        }
    }
    
    try {
        // Check if restaurant has any orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ?");
        $stmt->execute([$restaurant_id]);
        $orderCount = $stmt->fetchColumn();
        
        if ($orderCount > 0) {
            $_SESSION['error'] = "Cannot delete restaurant with existing orders";
        } else {
            // Delete restaurant
            $stmt = $pdo->prepare("DELETE FROM restaurants WHERE restaurant_id = ?");
            $stmt->execute([$restaurant_id]);
            $_SESSION['success'] = "Restaurant deleted successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting restaurant: " . $e->getMessage();
    }
    header('Location: restaurants.php');
    exit();
}

// Fetch restaurants based on user role
if ($user_role === 'Admin') {
    $stmt = $pdo->prepare("SELECT * FROM restaurants ORDER BY created_at DESC");
    $stmt->execute();
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Manager can only see their own restaurants
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE manager_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch managers for dropdown (only for admin)
$managers = [];
if ($user_role === 'Admin') {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.name 
        FROM users u 
        WHERE u.role_id = 3 
        ORDER BY u.name
    ");
    $stmt->execute();
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Restaurants Management</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRestaurantModal">
                    <i class="fas fa-plus me-2"></i>Add New Restaurant
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover" id="restaurantsTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restaurants as $restaurant): ?>
                        <tr>
                            <td>
                                <?php if (!empty($restaurant['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($restaurant['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($restaurant['name']); ?>" 
                                         class="restaurant-image">
                                <?php else: ?>
                                    <div class="restaurant-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-utensils fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($restaurant['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['address'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['email'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (isset($restaurant['is_active']) && $restaurant['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editRestaurantModal"
                                        data-restaurant='<?php echo htmlspecialchars(json_encode($restaurant)); ?>'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this restaurant?');">
                                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['restaurant_id']; ?>">
                                    <button type="submit" name="delete_restaurant" class="btn btn-sm btn-danger">
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

<!-- Add Restaurant Modal -->
<div class="modal fade" id="addRestaurantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Restaurant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_restaurant.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Basic Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="mb-3">Business Hours</h6>
                            <div class="mb-3">
                                <label class="form-label">Opening Time</label>
                                <input type="time" class="form-control" name="opening_time">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Closing Time</label>
                                <input type="time" class="form-control" name="closing_time">
                            </div>

                            <h6 class="mb-3 mt-4">Social Media</h6>
                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" placeholder="https://">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Facebook URL</label>
                                <input type="url" class="form-control" name="facebook_url" placeholder="https://facebook.com/">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instagram URL</label>
                                <input type="url" class="form-control" name="instagram_url" placeholder="https://instagram.com/">
                            </div>

                            <h6 class="mb-3 mt-4">Delivery Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Minimum Order Amount (Tsh)</label>
                                <input type="number" class="form-control" name="minimum_order_amount" min="0" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Fee (Tsh)</label>
                                <input type="number" class="form-control" name="delivery_fee" min="0" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Radius (meters)</label>
                                <input type="number" class="form-control" name="delivery_radius" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_restaurant" class="btn btn-primary">Add Restaurant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Restaurant Modal -->
<div class="modal fade" id="editRestaurantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Restaurant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_restaurant.php" enctype="multipart/form-data">
                <input type="hidden" name="restaurant_id" id="edit_restaurant_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Basic Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" id="edit_address" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="edit_phone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="mb-3">Business Hours</h6>
                            <div class="mb-3">
                                <label class="form-label">Opening Time</label>
                                <input type="time" class="form-control" name="opening_time" id="edit_opening_time">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Closing Time</label>
                                <input type="time" class="form-control" name="closing_time" id="edit_closing_time">
                            </div>

                            <h6 class="mb-3 mt-4">Social Media</h6>
                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" id="edit_website" placeholder="https://">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Facebook URL</label>
                                <input type="url" class="form-control" name="facebook_url" id="edit_facebook_url" placeholder="https://facebook.com/">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Instagram URL</label>
                                <input type="url" class="form-control" name="instagram_url" id="edit_instagram_url" placeholder="https://instagram.com/">
                            </div>

                            <h6 class="mb-3 mt-4">Delivery Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Minimum Order Amount (Tsh)</label>
                                <input type="number" class="form-control" name="minimum_order_amount" id="edit_minimum_order_amount" min="0" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Fee (Tsh)</label>
                                <input type="number" class="form-control" name="delivery_fee" id="edit_delivery_fee" min="0" step="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Delivery Radius (meters)</label>
                                <input type="number" class="form-control" name="delivery_radius" id="edit_delivery_radius" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" id="edit_is_active">
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_restaurant" class="btn btn-primary">Update Restaurant</button>
                </div>
            </form>
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
        var table = $('#restaurantsTable').DataTable({
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
                search: "Search restaurants:",
                lengthMenu: "Show _MENU_ restaurants per page",
                info: "Showing _START_ to _END_ of _TOTAL_ restaurants",
                infoEmpty: "No restaurants found",
                infoFiltered: "(filtered from _MAX_ total restaurants)"
            },
            columnDefs: [
                {
                    targets: [0], // Image column
                    orderable: false,
                    searchable: false
                },
                {
                    targets: [6], // Actions column
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // Handle edit modal data population
        $('#editRestaurantModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var restaurant = button.data('restaurant');
            
            $('#edit_restaurant_id').val(restaurant.restaurant_id);
            $('#edit_name').val(restaurant.name);
            $('#edit_description').val(restaurant.description);
            $('#edit_address').val(restaurant.address);
            $('#edit_phone').val(restaurant.phone);
            $('#edit_email').val(restaurant.email);
            $('#edit_opening_time').val(restaurant.opening_time);
            $('#edit_closing_time').val(restaurant.closing_time);
            $('#edit_website').val(restaurant.website);
            $('#edit_facebook_url').val(restaurant.facebook_url);
            $('#edit_instagram_url').val(restaurant.instagram_url);
            $('#edit_minimum_order_amount').val(restaurant.minimum_order_amount);
            $('#edit_delivery_fee').val(restaurant.delivery_fee);
            $('#edit_delivery_radius').val(restaurant.delivery_radius);
            $('#edit_is_active').prop('checked', restaurant.is_active == 1);
        });
    });
</script>

<style>
    .restaurant-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .dataTables_wrapper .dataTables_filter {
        float: right;
        text-align: right;
    }
    
    .dataTables_wrapper .dataTables_length {
        float: left;
    }
    
    .dataTables_wrapper .dataTables_paginate {
        float: right;
        text-align: right;
    }
    
    .dt-buttons {
        margin-bottom: 1rem;
    }
</style> 