<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/pdf_report.php';

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
$time_period = $_GET['period'] ?? 'month';
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

// Debug: Print the date range being used
echo "<!-- Debug: Date range: " . $start_date . " to " . $end_date . " -->";

// Get sales statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.order_id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_revenue,
            COUNT(DISTINCT o.user_id) as total_customers,
            COALESCE(AVG(o.total_amount), 0) as avg_order_value
        FROM orders o
        WHERE o.restaurant_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
    $sales_stats = $stmt->fetch();
    
    // Debug output
    echo "<!-- Debug: Sales stats query result: " . print_r($sales_stats, true) . " -->";
} catch (PDOException $e) {
    echo "<!-- Debug: Error in sales stats query: " . $e->getMessage() . " -->";
    $sales_stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'total_customers' => 0,
        'avg_order_value' => 0
    ];
}

// Get top products
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.product_name,
            COUNT(oi.order_item_id) as order_count,
            SUM(oi.quantity) as total_quantity,
            COALESCE(SUM(oi.total_price), 0) as total_revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.restaurant_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.product_id, p.product_name
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
    $top_products = $stmt->fetchAll();
    
    // Debug output
    echo "<!-- Debug: Top products query result: " . print_r($top_products, true) . " -->";
} catch (PDOException $e) {
    echo "<!-- Debug: Error in top products query: " . $e->getMessage() . " -->";
    $top_products = [];
}

// Get order status distribution
try {
    $stmt = $pdo->prepare("
        SELECT 
            order_status,
            COUNT(*) as count
        FROM orders
        WHERE restaurant_id = ?
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY order_status
    ");
    $stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
    $status_distribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Debug output
    echo "<!-- Debug: Status distribution query result: " . print_r($status_distribution, true) . " -->";
} catch (PDOException $e) {
    echo "<!-- Debug: Error in status distribution query: " . $e->getMessage() . " -->";
    $status_distribution = [];
}

// Get daily revenue data for chart
try {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COALESCE(SUM(total_amount), 0) as daily_revenue
        FROM orders
        WHERE restaurant_id = ?
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$restaurant['restaurant_id'], $start_date, $end_date]);
    $daily_revenue = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Debug output
    echo "<!-- Debug: Daily revenue query result: " . print_r($daily_revenue, true) . " -->";
} catch (PDOException $e) {
    echo "<!-- Debug: Error in daily revenue query: " . $e->getMessage() . " -->";
    $daily_revenue = [];
}
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Restaurant Reports</h2>
                <div>
                    <button onclick="printReport()" class="btn btn-secondary me-2">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                    <button onclick="downloadReport()" class="btn btn-success me-2">
                        <i class="fas fa-download me-2"></i>Download Report
                    </button>
                    <a href="restaurant_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="card" id="report-content">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($restaurant['name']); ?> Reports</h4>
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
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="mb-0"><?php echo number_format($sales_stats['total_orders']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Revenue</h5>
                                    <h2 class="mb-0">TSh <?php echo number_format($sales_stats['total_revenue'], 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Customers</h5>
                                    <h2 class="mb-0"><?php echo number_format($sales_stats['total_customers']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Average Order Value</h5>
                                    <h2 class="mb-0">TSh <?php echo number_format($sales_stats['avg_order_value'], 2); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row mb-4">
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
                    </div>

                    <!-- Top Products -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Orders</th>
                                            <th>Quantity Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo number_format($product['order_count']); ?></td>
                                            <td><?php echo number_format($product['total_quantity']); ?></td>
                                            <td>TSh <?php echo number_format($product['total_revenue'], 2); ?></td>
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

<!-- Add pdfmake library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

@media print {
    .no-print {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
    
    .table {
        border: 1px solid #dee2e6 !important;
    }
    
    .table th, .table td {
        border: 1px solid #dee2e6 !important;
    }
}

/* PDF specific styles */
.pdf-content {
    padding: 20px;
    background: white;
}

.pdf-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #dee2e6;
}

.pdf-title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
}

.pdf-subtitle {
    font-size: 16px;
    color: #666;
}

.pdf-section {
    margin-bottom: 30px;
}

.pdf-section-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 15px;
    color: #333;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 5px;
}

.pdf-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.pdf-stat-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    width: 23%;
    text-align: center;
}

.pdf-stat-title {
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.pdf-stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #333;
}

.pdf-chart {
    margin: 20px 0;
    page-break-inside: avoid;
}

.pdf-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.pdf-table th, .pdf-table td {
    border: 1px solid #dee2e6;
    padding: 8px;
    text-align: left;
}

.pdf-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.pdf-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
    font-size: 12px;
    color: #666;
}
</style>

<!-- Add Font Awesome from CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Add main.js script -->
<script src="assets/js/main.js"></script>

<script>
// Pass data to the charts
window.dailyRevenueLabels = <?php echo json_encode(array_keys($daily_revenue)); ?>;
window.dailyRevenueData = <?php echo json_encode(array_values($daily_revenue)); ?>;
window.dailyOrderCount = <?php echo json_encode(array_values($daily_orders)); ?>;
window.statusDistributionData = [
    <?php echo $status_distribution['Pending'] ?? 0; ?>,
    <?php echo $status_distribution['Processing'] ?? 0; ?>,
    <?php echo $status_distribution['Completed'] ?? 0; ?>,
    <?php echo $status_distribution['Cancelled'] ?? 0; ?>,
    <?php echo $status_distribution['Delivered'] ?? 0; ?>
];

// Download Report as PDF
function downloadReport() {
    try {
        const restaurantId = <?php echo $restaurant['restaurant_id']; ?>;
        const startDate = '<?php echo $start_date; ?>';
        const endDate = '<?php echo $end_date; ?>';
        
        console.log('Downloading report with params:', {
            restaurantId,
            startDate,
            endDate
        });
        
        const url = `generate_pdf.php?restaurant_id=${restaurantId}&start_date=${startDate}&end_date=${endDate}`;
        console.log('Generated URL:', url);
        
        window.location.href = url;
    } catch (error) {
        console.error('Error in downloadReport:', error);
        alert('Error generating report. Please try again.');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 