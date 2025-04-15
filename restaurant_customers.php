<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

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

// Get customers who have ordered from this restaurant
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(o.total_amount) as total_spent,
        MAX(o.created_at) as last_order_date
    FROM users u
    JOIN orders o ON u.user_id = o.user_id
    WHERE o.restaurant_id = ?
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY u.user_id
    ORDER BY total_orders DESC
");
$stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
$customers = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($restaurant['name']); ?> Customers</h4>
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

                    <!-- Customers Table -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="customersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Orders</th>
                                    <th>Total Spent</th>
                                    <th>Last Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo number_format($customer['total_orders']); ?></td>
                                    <td>KSh <?php echo number_format($customer['total_spent'], 2); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($customer['last_order_date'])); ?></td>
                                    <td>
                                        <a href="customer_orders.php?id=<?php echo $customer['user_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
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

<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<!-- Add DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#customersTable').DataTable({
        order: [[3, 'desc']], // Sort by total orders by default
        pageLength: 10,
        responsive: true,
        language: {
            search: "Search customers:",
            lengthMenu: "Show _MENU_ customers per page",
            info: "Showing _START_ to _END_ of _TOTAL_ customers",
            infoEmpty: "No customers found",
            infoFiltered: "(filtered from _MAX_ total customers)"
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 