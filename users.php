<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', 'Attempt to access customer management without authentication');
    header('Location: login.php');
    exit();
}

// Handle customer deletion
if (isset($_POST['delete_customer'])) {
    $user_id = $_POST['user_id'];
    try {
        // Check if customer has any orders
        $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE user_id = ?", [$user_id]);
        $orderCount = $stmt->fetchColumn();
        
        if ($orderCount > 0) {
            $_SESSION['error'] = "Cannot delete customer with existing orders";
        } else {
            // Delete customer
            $db->delete('users', 'user_id = ?', [$user_id]);
            $_SESSION['success'] = "Customer deleted successfully";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
    }
    header('Location: users.php');
    exit();
}

// Get time filter
$timeFilter = $_GET['time'] ?? 'monthly';

// Base query for customers with order statistics
$sql = "SELECT u.*, 
        COUNT(o.order_id) as order_count,
        SUM(o.total_amount) as total_spent,
        MAX(o.created_at) as last_order_date
        FROM users u 
        LEFT JOIN orders o ON u.user_id = o.user_id 
        WHERE u.role_id = 3"; // Role ID 3 for customers

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
}

$sql .= " GROUP BY u.user_id";

// Get customers
try {
    $customers = $db->fetchAll($sql, $params ?? []);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching customers: " . $e->getMessage();
    $customers = [];
}

// Get monthly registration data for customers
$monthlyData = [];
$currentYear = date('Y');
$currentMonth = date('n');

// Create array with all months
for ($i = 1; $i <= 12; $i++) {
    $monthlyData[] = [
        'month' => $i,
        'count' => 0
    ];
}

// Get actual counts for each month
try {
    $counts = $db->fetchAll("
        SELECT MONTH(created_at) as month, COUNT(*) as count 
        FROM users 
        WHERE role_id = 3 
        AND YEAR(created_at) = ? 
        GROUP BY MONTH(created_at)", 
        [$currentYear]
    );
    
    // Update counts for months that have data
    foreach ($counts as $count) {
        foreach ($monthlyData as &$month) {
            if ($month['month'] == $count['month']) {
                $month['count'] = $count['count'];
                break;
            }
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching monthly data: " . $e->getMessage();
}

// Get customer distribution data
try {
    $customer_distribution = $db->fetchAll("
        SELECT 
            CASE 
                WHEN is_verified = 1 THEN 'Active'
                ELSE 'Inactive'
            END as customer_status,
            COUNT(*) as count
        FROM users
        WHERE role_id = 2
        GROUP BY is_verified
        ORDER BY count DESC
    ");

    // If no data, create sample data for testing
    if (empty($customer_distribution)) {
        $customer_distribution = [
            ['customer_status' => 'Active', 'count' => 5],
            ['customer_status' => 'Inactive', 'count' => 2]
        ];
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching customer distribution: " . $e->getMessage();
    $customer_distribution = [];
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

    <!-- Charts -->
    <div class="row">
        <!-- Customer Distribution Chart -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Customer Distribution</h5>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="customerDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Registration Chart -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Customer Registration Trend</h5>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="registrationChart"></canvas>
                    </div>
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
            <form method="POST">
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
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
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
            <form method="POST">
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
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" id="edit_phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password">
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
                    <button type="submit" name="update_customer" class="btn btn-primary">Update Customer</button>
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
                    <table class="table" id="ordersTable">
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

<script>
    $(document).ready(function() {
        var table = $('#customersTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            order: [[0, 'asc']], // Sort by name by default
            pageLength: 10,
            language: {
                search: "Search customers:",
                lengthMenu: "Show _MENU_ customers per page",
                info: "Showing _START_ to _END_ of _TOTAL_ customers",
                infoEmpty: "No customers found",
                infoFiltered: "(filtered from _MAX_ total customers)"
            }
        });

        // Handle edit modal data population
        $('#editCustomerModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var customer = button.data('customer');
            
            $('#edit_user_id').val(customer.user_id);
            $('#edit_name').val(customer.name);
            $('#edit_email').val(customer.email);
            $('#edit_phone').val(customer.phone);
            $('#edit_is_active').prop('checked', customer.is_active == 1);
        });

        // Handle view orders modal
        $('#viewOrdersModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var customerId = button.data('customer-id');
            
            // Load customer orders
            $.ajax({
                url: 'ajax/get_customer_orders.php',
                method: 'POST',
                data: { customer_id: customerId },
                success: function(response) {
                    var orders = JSON.parse(response);
                    var tbody = $('#ordersTable tbody');
                    tbody.empty();
                    
                    orders.forEach(function(order) {
                        tbody.append(`
                            <tr>
                                <td>${order.order_id}</td>
                                <td>${order.order_date}</td>
                                <td>${order.restaurant_name}</td>
                                <td>$${order.total_amount}</td>
                                <td>${order.status}</td>
                            </tr>
                        `);
                    });
                }
            });
        });

        // Registration Chart
        const registrationCtx = document.getElementById('registrationChart').getContext('2d');
        const registrationChart = new Chart(registrationCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($data) {
                    return date('F', mktime(0, 0, 0, $data['month'], 1));
                }, $monthlyData)); ?>,
                datasets: [{
                    label: 'Customer Registrations',
                    data: <?php echo json_encode(array_column($monthlyData, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Customer Registrations - <?php echo date('Y'); ?>'
                    }
                }
            }
        });

        // Customer Distribution Chart
        const customerDistributionCtx = document.getElementById('customerDistributionChart');
        if (customerDistributionCtx) {
            const customerDistributionData = {
                labels: <?php echo json_encode(array_column($customer_distribution, 'customer_status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($customer_distribution, 'count')); ?>,
                    backgroundColor: [
                        '#28a745', // Active
                        '#dc3545'  // Inactive
                    ],
                    borderWidth: 1
                }]
            };

            new Chart(customerDistributionCtx, {
                type: 'doughnut',
                data: customerDistributionData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    });
</script>

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
    
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
</style>
