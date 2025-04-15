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

// Get orders
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.restaurant_id = ?
    AND DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
$orders = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($restaurant['name']); ?> Orders</h4>
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

                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>TSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] == 'completed' ? 'success' : 
                                                ($order['status'] == 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
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
    $('#ordersTable').DataTable({
        order: [[4, 'desc']], // Sort by date by default
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

<?php require_once 'includes/footer.php'; ?> 