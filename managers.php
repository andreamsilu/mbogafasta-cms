<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // Get all managers with their restaurant information
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.name,
            u.email,
            u.phone_number,
            CASE 
                WHEN u.status = 1 THEN 1
                ELSE 0
            END as is_active,
            r.name AS restaurant_name,
            r.restaurant_id
        FROM users u
        LEFT JOIN restaurants r ON u.user_id = r.manager_id
        WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Manager')
        ORDER BY u.name
    ");
    $stmt->execute();
    $managers = $stmt->fetchAll();

    // Get all restaurants for the dropdown
    $stmt = $pdo->prepare("
        SELECT restaurant_id, name 
        FROM restaurants 
        WHERE manager_id IS NULL 
        ORDER BY name
    ");
    $stmt->execute();
    $available_restaurants = $stmt->fetchAll();

    // Include header
    include 'includes/header.php';
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Restaurant Managers</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addManagerModal">
                            <i class="fas fa-plus"></i> Add Manager
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped" id="managersTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Restaurant</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($managers as $manager): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($manager['name']); ?></td>
                                            <td><?php echo htmlspecialchars($manager['email']); ?></td>
                                            <td><?php echo htmlspecialchars($manager['phone_number']); ?></td>
                                            <td>
                                                <?php if ($manager['restaurant_name']): ?>
                                                    <?php echo htmlspecialchars($manager['restaurant_name']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $manager['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $manager['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary edit-manager" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editManagerModal"
                                                        data-user-id="<?php echo $manager['user_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($manager['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($manager['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($manager['phone_number']); ?>"
                                                        data-restaurant-id="<?php echo $manager['restaurant_id'] ?? ''; ?>"
                                                        data-status="<?php echo $manager['is_active']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteManagerModal"
                                                        data-manager-id="<?php echo $manager['user_id']; ?>"
                                                        data-manager-name="<?php echo htmlspecialchars($manager['name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Add Manager Modal -->
    <div class="modal fade" id="addManagerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="process_manager.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Restaurant</label>
                            <select class="form-select" name="restaurant_id">
                                <option value="">Select Restaurant</option>
                                <?php foreach ($available_restaurants as $restaurant): ?>
                                    <option value="<?php echo $restaurant['restaurant_id']; ?>">
                                        <?php echo htmlspecialchars($restaurant['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <button type="submit" name="add_manager" class="btn btn-primary">Add Manager</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Manager Modal -->
    <div class="modal fade" id="editManagerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="process_manager.php">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone_number" id="edit_phone_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Restaurant</label>
                            <select class="form-select" name="restaurant_id" id="edit_restaurant_id">
                                <option value="">Select Restaurant</option>
                                <?php foreach ($available_restaurants as $restaurant): ?>
                                    <option value="<?php echo $restaurant['restaurant_id']; ?>">
                                        <?php echo htmlspecialchars($restaurant['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <button type="submit" name="edit_manager" class="btn btn-primary">Update Manager</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Manager Modal -->
    <div class="modal fade" id="deleteManagerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="process_manager.php">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete <span id="delete_manager_name"></span>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_manager" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#managersTable').DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Edit Manager Modal
        $('.edit-manager').on('click', function() {
            var button = $(this);
            var modal = $('#editManagerModal');
            
            // Set form values
            modal.find('#edit_user_id').val(button.data('user-id'));
            modal.find('#edit_name').val(button.data('name'));
            modal.find('#edit_email').val(button.data('email'));
            modal.find('#edit_phone_number').val(button.data('phone'));
            modal.find('#edit_restaurant_id').val(button.data('restaurant-id'));
            modal.find('#edit_is_active').prop('checked', button.data('status') == 1);
        });

        // Delete Manager Modal
        $('#deleteManagerModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var managerId = button.data('manager-id');
            var managerName = button.data('manager-name');
            var modal = $(this);
            
            modal.find('#delete_user_id').val(managerId);
            modal.find('#delete_manager_name').text(managerName);
        });
    });
    </script>

    <?php include 'includes/footer.php'; ?>

<?php
} catch (Exception $e) {
    // Log the error
    error_log("Error in managers.php: " . $e->getMessage());
    
    // Display the actual error message for debugging
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    
    // Also show the stack trace for more details
    echo "<div class='alert alert-danger'>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</div>";
}
?> 