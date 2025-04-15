<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/header.php';
require_once 'includes/auth_check.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', 'Attempt to access manager dashboard without authentication');
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

if ($user_role !== 'Manager') {
    logSecurityEvent('unauthorized_access', 'User attempted to access manager dashboard without proper role');
    $_SESSION['error'] = "You don't have permission to access this page";
    header('Location: dashboard.php');
    exit();
}

// Get manager's restaurant
$stmt = $pdo->prepare("
    SELECT r.* 
    FROM restaurants r 
    WHERE r.manager_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header('Location: logout.php');
    exit;
}

// Get time period filter
$time_period = $_GET['period'] ?? 'today';
$start_date = $end_date = date('Y-m-d');

switch ($time_period) {
    case 'today':
        break;
    case 'yesterday':
        $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        break;
    case 'custom':
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        break;
}

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT o.order_id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        COUNT(DISTINCT o.user_id) as total_customers
    FROM orders o
    WHERE o.restaurant_id = ?
    AND DATE(o.created_at) BETWEEN ? AND ?
");
$stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
$stats = $stmt->fetch();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.restaurant_id = ?
    AND DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
$recent_orders = $stmt->fetchAll();

// Get order status distribution
$stmt = $pdo->prepare("
    SELECT 
        o.order_status,
        COUNT(*) as count
    FROM orders o
    WHERE o.restaurant_id = ?
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY o.order_status
");
$stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
$order_statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manager Dashboard</h2>
                <div>
                    <a href="restaurants.php" class="btn btn-primary me-2">
                        <i class="fas fa-utensils me-2"></i>Manage Restaurants
                    </a>
                    <a href="orders.php" class="btn btn-success">
                        <i class="fas fa-shopping-cart me-2"></i>View All Orders
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($restaurant['name']); ?> Dashboard</h4>
                </div>
                <div class="card-body">
                    <!-- Time Period Filter -->
                    <div class="mb-4">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <select name="period" class="form-select" onchange="this.form.submit()">
                                    <option value="today" <?php echo $time_period == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="yesterday" <?php echo $time_period == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                    <option value="week" <?php echo $time_period == 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $time_period == 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="year" <?php echo $time_period == 'year' ? 'selected' : ''; ?>>This Year</option>
                                    <option value="custom" <?php echo $time_period == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>
                            <?php if ($time_period == 'custom'): ?>
                            <div class="col-md-3">
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Revenue</h5>
                                    <h2 class="mb-0">TSh <?php echo number_format($stats['total_revenue'], 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Customers</h5>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_customers']); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Order Status Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="orderStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Revenue Trend</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Orders</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td>TSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['order_status'] == 'Completed' ? 'success' : 
                                                        ($order['order_status'] == 'Pending' ? 'warning' : 
                                                        ($order['order_status'] == 'Processing' ? 'info' : 
                                                        ($order['order_status'] == 'Delivered' ? 'primary' : 'danger'))); 
                                                ?>">
                                                    <?php echo $order['order_status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
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
    </div>
</div>

<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<!-- Add DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#recentOrdersTable').DataTable({
            order: [[5, 'desc']], // Sort by date by default
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search orders:",
                lengthMenu: "Show _MENU_ orders per page",
                info: "Showing _START_ to _END_ of _TOTAL_ orders",
                infoEmpty: "No orders found",
                infoFiltered: "(filtered from _MAX_ total orders)"
            }
        });
    });
</script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>

<script>
// Order Status Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Processing', 'Completed', 'Cancelled', 'Delivered'],
        datasets: [{
            data: [
                <?php echo $order_statuses['Pending'] ?? 0; ?>,
                <?php echo $order_statuses['Processing'] ?? 0; ?>,
                <?php echo $order_statuses['Completed'] ?? 0; ?>,
                <?php echo $order_statuses['Cancelled'] ?? 0; ?>,
                <?php echo $order_statuses['Delivered'] ?? 0; ?>
            ],
            backgroundColor: [
                'rgba(255, 193, 7, 0.8)',    // Pending - Yellow
                'rgba(0, 123, 255, 0.8)',    // Processing - Blue
                'rgba(40, 167, 69, 0.8)',    // Completed - Green
                'rgba(220, 53, 69, 0.8)',    // Cancelled - Red
                'rgba(23, 162, 184, 0.8)'    // Delivered - Teal
            ],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            }
        },
        cutout: '60%'
    }
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php 
            $labels = [];
            $current = strtotime($start_date);
            $end = strtotime($end_date);
            while($current <= $end) {
                $labels[] = date('M d', $current);
                $current = strtotime('+1 day', $current);
            }
            echo json_encode($labels);
        ?>,
        datasets: [{
            label: 'Daily Revenue',
            data: <?php
                $revenue_data = [];
                $current = strtotime($start_date);
                $end = strtotime($end_date);
                while($current <= $end) {
                    $date = date('Y-m-d', $current);
                    $stmt = $pdo->prepare("
                        SELECT COALESCE(SUM(total_amount), 0) as daily_revenue
                        FROM orders
                        WHERE restaurant_id = ?
                        AND DATE(created_at) = ?
                    ");
                    $stmt->execute([$restaurant['restaurant_id'], $date]);
                    $revenue_data[] = $stmt->fetchColumn();
                    $current = strtotime('+1 day', $current);
                }
                echo json_encode($revenue_data);
            ?>,
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'TSh ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 