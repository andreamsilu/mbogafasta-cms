<?php
require_once 'includes/header.php';

// Handle customer deletion
if (isset($_POST['delete_customer'])) {
    $user_id = $_POST['user_id'];
    try {
        // Check if customer has any orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $orderCount = $stmt->fetchColumn();
        
        if ($orderCount > 0) {
            $_SESSION['error'] = "Cannot delete customer with existing orders";
        } else {
            // Delete customer
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Customer deleted successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
    }
    header('Location: customers.php');
    exit();
}

// Get time filter
$timeFilter = $_GET['time'] ?? 'all';

// Base query for customers with order statistics
$sql = "SELECT u.*, 
        COUNT(o.order_id) as order_count,
        SUM(o.total_amount) as total_spent,
        MAX(o.created_at) as last_order_date
        FROM users u 
        LEFT JOIN orders o ON u.user_id = o.user_id 
        WHERE u.role_id = 2"; // Role ID 2 for customers

// Apply time filter
switch ($timeFilter) {
    case 'today':
        $sql .= " AND DATE(u.created_at) = CURDATE()";
        break;
    case 'yesterday':
        $sql .= " AND DATE(u.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $sql .= " AND u.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'monthly':
        $sql .= " AND MONTH(u.created_at) = MONTH(CURDATE()) AND YEAR(u.created_at) = YEAR(CURDATE())";
        break;
    case 'custom':
        if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
            $sql .= " AND DATE(u.created_at) BETWEEN ? AND ?";
            $params[] = $_GET['start_date'];
            $params[] = $_GET['end_date'];
        }
        break;
    case 'all':
    default:
        // No additional conditions needed - show all customers
        break;
}

$sql .= " GROUP BY u.user_id";

// Get customers
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params ?? []);
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching customers: " . $e->getMessage();
    $customers = [];
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title">Customer Management</h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                <i class="fas fa-plus"></i> Add New Customer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="time" class="form-label">Time Period</label>
                            <select name="time" id="time" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $timeFilter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $timeFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $timeFilter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="week" <?php echo $timeFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="monthly" <?php echo $timeFilter === 'monthly' ? 'selected' : ''; ?>>This Month</option>
                                <option value="custom" <?php echo $timeFilter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>
                        <?php if ($timeFilter === 'custom'): ?>
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" 
                                       value="<?php echo $_GET['start_date'] ?? ''; ?>" onchange="this.form.submit()">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" 
                                       value="<?php echo $_GET['end_date'] ?? ''; ?>" onchange="this.form.submit()">
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="customersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Last Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                                    <td><?php echo $customer['order_count'] ?? 0; ?></td>
                                    <td>Tsh<?php echo number_format((float)($customer['total_spent'] ?? 0), 2); ?></td>
                                    <td><?php echo $customer['last_order_date'] ? date('M d, Y', strtotime($customer['last_order_date'])) : 'Never'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo ($customer['is_active'] ?? 0) ? 'success' : 'danger'; ?>">
                                            <?php echo ($customer['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editCustomerModal"
                                                data-customer='<?php echo json_encode($customer); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewOrdersModal"
                                                data-customer-id="<?php echo $customer['user_id']; ?>">
                                            <i class="fas fa-shopping-cart"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            <input type="hidden" name="user_id" value="<?php echo $customer['user_id']; ?>">
                                            <button type="submit" name="delete_customer" class="btn btn-sm btn-danger">
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

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_customer.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_customer.php">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_is_active" class="form-label">Status</label>
                        <select class="form-select" id="edit_is_active" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_customer" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Orders Modal -->
<div class="modal fade" id="viewOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="customerOrdersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Restaurant</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Orders will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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

    /* DataTables custom styling */
    .dataTables_wrapper .dataTables_filter {
        float: right;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_info {
        padding-top: 1rem;
    }
    .dataTables_wrapper .dataTables_paginate {
        padding-top: 1rem;
    }
    .dataTables_wrapper .dataTables_filter input {
        margin-left: 0.5rem;
    }
    .dataTables_wrapper .dataTables_length select {
        margin-left: 0.5rem;
        margin-right: 0.5rem;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.75rem;
        margin-left: 0.25rem;
        border-radius: 0.25rem;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0d6efd;
        color: white !important;
        border: 1px solid #0d6efd;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize DataTable with enhanced features
        var table = $('#customersTable').DataTable({
            responsive: true,
            order: [[5, 'desc']], // Sort by last order date by default
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                search: "Search customers:",
                lengthMenu: "Show _MENU_ customers per page",
                info: "Showing _START_ to _END_ of _TOTAL_ customers",
                infoEmpty: "No customers found",
                infoFiltered: "(filtered from _MAX_ total customers)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            columnDefs: [
                { targets: [3, 4, 5], orderable: true },
                { targets: [6, 7], orderable: false }
            ],
            initComplete: function() {
                // Add custom search inputs for specific columns
                this.api().columns([0, 1, 2]).every(function() {
                    var column = this;
                    var header = $(column.header());
                    var title = header.text().trim();
                    
                    if (title !== 'Actions' && title !== 'Status') {
                        var input = $('<input type="text" class="form-control form-control-sm" placeholder="Search ' + title + '">')
                            .appendTo(header)
                            .on('keyup change', function() {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    }
                });
            }
        });

        // Handle edit modal data
        $('#editCustomerModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var customer = button.data('customer');
            var modal = $(this);
            
            modal.find('#edit_user_id').val(customer.user_id);
            modal.find('#edit_name').val(customer.name);
            modal.find('#edit_email').val(customer.email);
            modal.find('#edit_phone').val(customer.phone);
            modal.find('#edit_is_active').val(customer.is_active);
        });

        // Handle view orders modal
        $('#viewOrdersModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var customerId = button.data('customer-id');
            var modal = $(this);
            
            // Clear previous data
            modal.find('#customerOrdersTable tbody').empty();
            
            // Load customer orders
            $.get('get_customer_orders.php', { customer_id: customerId }, function(data) {
                var orders = JSON.parse(data);
                var tbody = modal.find('#customerOrdersTable tbody');
                
                orders.forEach(function(order) {
                    tbody.append(`
                        <tr>
                            <td>${order.order_id}</td>
                            <td>${new Date(order.created_at).toLocaleDateString()}</td>
                            <td>${order.restaurant_name}</td>
                            <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                            <td><span class="status-badge status-${order.order_status.toLowerCase()}">${order.order_status}</span></td>
                        </tr>
                    `);
                });
            });
        });
    });
</script> 