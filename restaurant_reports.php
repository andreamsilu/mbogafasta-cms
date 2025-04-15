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
                                    <h2 class="mb-0">KSh <?php echo number_format($sales_stats['total_revenue'], 2); ?></h2>
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
                                    <h2 class="mb-0">KSh <?php echo number_format($sales_stats['avg_order_value'], 2); ?></h2>
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
                                            <td>KSh <?php echo number_format($product['total_revenue'], 2); ?></td>
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

<!-- Add html2pdf.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

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

<script>
// Initialize main charts
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($daily_revenue)); ?>,
        datasets: [{
            label: 'Daily Revenue',
            data: <?php echo json_encode(array_values($daily_revenue)); ?>,
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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
                        return 'KSh ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
const orderStatusChart = new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Processing', 'Completed', 'Cancelled', 'Delivered'],
        datasets: [{
            data: [
                <?php echo $status_distribution['Pending'] ?? 0; ?>,
                <?php echo $status_distribution['Processing'] ?? 0; ?>,
                <?php echo $status_distribution['Completed'] ?? 0; ?>,
                <?php echo $status_distribution['Cancelled'] ?? 0; ?>,
                <?php echo $status_distribution['Delivered'] ?? 0; ?>
            ],
            backgroundColor: [
                'rgba(255, 193, 7, 0.8)',
                'rgba(0, 123, 255, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(23, 162, 184, 0.8)'
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
                position: 'bottom'
            }
        },
        cutout: '60%'
    }
});

// Print Report Function
function printReport() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Restaurant Report - <?php echo htmlspecialchars($restaurant['name']); ?></title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <style>
                    @media print {
                        .no-print { display: none !important; }
                        .card { border: none !important; box-shadow: none !important; }
                        .card-header { background-color: #f8f9fa !important; }
                    }
                </style>
            </head>
            <body>
                <div class="container mt-4">
                    <h2 class="text-center mb-4">Restaurant Report - <?php echo htmlspecialchars($restaurant['name']); ?></h2>
                    <p class="text-center mb-4">Period: <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                    ${document.getElementById('report-content').innerHTML}
                </div>
                <script>
                    window.onload = function() {
                        // Initialize charts with the same data as the main page
                        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                        new Chart(revenueCtx, {
                            type: 'line',
                            data: {
                                labels: ${JSON.stringify(<?php echo json_encode(array_keys($daily_revenue)); ?>)},
                                datasets: [{
                                    label: 'Daily Revenue',
                                    data: ${JSON.stringify(<?php echo json_encode(array_values($daily_revenue)); ?>)},
                                    borderColor: 'rgba(40, 167, 69, 1)',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
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
                                                return 'KSh ' + value.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                        
                        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
                        new Chart(orderStatusCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Pending', 'Processing', 'Completed', 'Cancelled', 'Delivered'],
                                datasets: [{
                                    data: [
                                        ${<?php echo $status_distribution['Pending'] ?? 0; ?>},
                                        ${<?php echo $status_distribution['Processing'] ?? 0; ?>},
                                        ${<?php echo $status_distribution['Completed'] ?? 0; ?>},
                                        ${<?php echo $status_distribution['Cancelled'] ?? 0; ?>},
                                        ${<?php echo $status_distribution['Delivered'] ?? 0; ?>}
                                    ],
                                    backgroundColor: [
                                        'rgba(255, 193, 7, 0.8)',
                                        'rgba(0, 123, 255, 0.8)',
                                        'rgba(40, 167, 69, 0.8)',
                                        'rgba(220, 53, 69, 0.8)',
                                        'rgba(23, 162, 184, 0.8)'
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
                                        position: 'bottom'
                                    }
                                },
                                cutout: '60%'
                            }
                        });
                        
                        // Print after charts are rendered
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 1000);
                    };
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

// Download Report as PDF
function downloadReport() {
    // Create a temporary container for PDF content
    const pdfContent = document.createElement('div');
    pdfContent.className = 'pdf-content';
    
    // Add header
    const header = document.createElement('div');
    header.className = 'pdf-header';
    header.innerHTML = `
        <div class="pdf-title">Restaurant Report - <?php echo htmlspecialchars($restaurant['name']); ?></div>
        <div class="pdf-subtitle">
            Period: ${document.querySelector('select[name="period"]').value}<br>
            Generated on: ${new Date().toLocaleDateString()}
        </div>
    `;
    pdfContent.appendChild(header);
    
    // Add statistics section
    const statsSection = document.createElement('div');
    statsSection.className = 'pdf-section';
    statsSection.innerHTML = `
        <div class="pdf-section-title">Key Statistics</div>
        <div class="pdf-stats">
            <div class="pdf-stat-card">
                <div class="pdf-stat-title">Total Orders</div>
                <div class="pdf-stat-value"><?php echo number_format($sales_stats['total_orders']); ?></div>
            </div>
            <div class="pdf-stat-card">
                <div class="pdf-stat-title">Total Revenue</div>
                <div class="pdf-stat-value">KSh <?php echo number_format($sales_stats['total_revenue'], 2); ?></div>
            </div>
            <div class="pdf-stat-card">
                <div class="pdf-stat-title">Total Customers</div>
                <div class="pdf-stat-value"><?php echo number_format($sales_stats['total_customers']); ?></div>
            </div>
            <div class="pdf-stat-card">
                <div class="pdf-stat-title">Average Order Value</div>
                <div class="pdf-stat-value">KSh <?php echo number_format($sales_stats['avg_order_value'], 2); ?></div>
            </div>
        </div>
    `;
    pdfContent.appendChild(statsSection);
    
    // Add charts section
    const chartsSection = document.createElement('div');
    chartsSection.className = 'pdf-section';
    chartsSection.innerHTML = `
        <div class="pdf-section-title">Performance Charts</div>
        <div class="row">
            <div class="col-md-6">
                <div class="pdf-chart">
                    <canvas id="pdfRevenueChart" width="400" height="300"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="pdf-chart">
                    <canvas id="pdfOrderStatusChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    `;
    pdfContent.appendChild(chartsSection);
    
    // Add top products section
    const productsSection = document.createElement('div');
    productsSection.className = 'pdf-section';
    productsSection.innerHTML = `
        <div class="pdf-section-title">Top Products</div>
        <table class="pdf-table">
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
                    <td>KSh <?php echo number_format($product['total_revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    `;
    pdfContent.appendChild(productsSection);
    
    // Add footer
    const footer = document.createElement('div');
    footer.className = 'pdf-footer';
    footer.innerHTML = `
        Generated by <?php echo htmlspecialchars($restaurant['name']); ?> Restaurant Management System<br>
        Report Period: <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>
    `;
    pdfContent.appendChild(footer);
    
    // Add the content to the document
    document.body.appendChild(pdfContent);
    
    // Initialize charts in the PDF content
    const pdfRevenueCtx = document.getElementById('pdfRevenueChart').getContext('2d');
    const revenueChart = new Chart(pdfRevenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($daily_revenue)); ?>,
            datasets: [{
                label: 'Daily Revenue',
                data: <?php echo json_encode(array_values($daily_revenue)); ?>,
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
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
                            return 'KSh ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    const pdfOrderStatusCtx = document.getElementById('pdfOrderStatusChart').getContext('2d');
    const orderStatusChart = new Chart(pdfOrderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Processing', 'Completed', 'Cancelled', 'Delivered'],
            datasets: [{
                data: [
                    <?php echo $status_distribution['Pending'] ?? 0; ?>,
                    <?php echo $status_distribution['Processing'] ?? 0; ?>,
                    <?php echo $status_distribution['Completed'] ?? 0; ?>,
                    <?php echo $status_distribution['Cancelled'] ?? 0; ?>,
                    <?php echo $status_distribution['Delivered'] ?? 0; ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%'
        }
    });
    
    // Generate PDF
    const options = {
        margin: 10,
        filename: `restaurant_report_${new Date().toISOString().split('T')[0]}.pdf`,
        html2canvas: { 
            scale: 2,
            useCORS: true,
            logging: true,
            allowTaint: true
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Wait for charts to render before generating PDF
    setTimeout(() => {
        html2pdf().set(options).from(pdfContent).save().then(() => {
            // Remove the temporary content
            document.body.removeChild(pdfContent);
        });
    }, 1000);
}

// Add print and download buttons to the page
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.card-header');
    const buttons = document.createElement('div');
    buttons.className = 'float-end no-print';
    buttons.innerHTML = `
        <button onclick="printReport()" class="btn btn-sm btn-secondary me-2">
            <i class="fas fa-print me-1"></i>Print
        </button>
        <button onclick="downloadReport()" class="btn btn-sm btn-success">
            <i class="fas fa-download me-1"></i>Download
        </button>
    `;
    header.appendChild(buttons);
});
</script>

<!-- Add Font Awesome from CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Add main.js script -->
<script src="assets/js/main.js"></script>

<?php require_once 'includes/footer.php'; ?> 