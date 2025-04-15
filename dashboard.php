<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', 'Attempt to access dashboard without authentication');
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

// Redirect managers to their specific dashboard
if ($user_role === 'Manager') {
    header('Location: restaurant_dashboard.php');
    exit();
}

require_once 'includes/header.php';

// Get time period filter
$timeFilter = $_GET['time_filter'] ?? 'today';
$startDate = '';
$endDate = date('Y-m-d');

switch($timeFilter) {
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = $startDate;
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $startDate = date('Y-01-01'); // First day of current year
        break;
    default: // today
        $startDate = date('Y-m-d');
}

// Debug information
echo "<!-- Time Filter: " . $timeFilter . " -->";
echo "<!-- Start Date: " . $startDate . " -->";
echo "<!-- End Date: " . $endDate . " -->";

// Fetch statistics
try {
    // Total restaurants
    $stmt = $pdo->query("SELECT COUNT(*) FROM restaurants");
    $total_restaurants = $stmt->fetchColumn();

    // Total managers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Manager')");
    $total_managers = $stmt->fetchColumn();

    // Total revenue across all restaurants
    $revenueQuery = "
        SELECT 
            COALESCE(SUM(total_amount), 0) as revenue
        FROM orders
        WHERE order_status != 'Cancelled'
        AND DATE(created_at) BETWEEN ? AND ?
    ";

    $stmt = $pdo->prepare($revenueQuery);
    $stmt->execute([$startDate, $endDate]);
    $total_revenue = $stmt->fetchColumn();

    // Get revenue trend data
    $revenueTrendQuery = "
        SELECT 
            DATE(created_at) as date,
            COALESCE(SUM(total_amount), 0) as revenue
        FROM orders
        WHERE order_status != 'Cancelled'
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";

    $stmt = $pdo->prepare($revenueTrendQuery);
    $stmt->execute([$startDate, $endDate]);
    $revenue_trend = $stmt->fetchAll();

    // If no data, create sample data for testing
    if (empty($revenue_trend)) {
        $revenue_trend = [['date' => date('Y-m-d'), 'revenue' => 0]];
    }

} catch(PDOException $e) {
    die("Error fetching statistics: " . $e->getMessage());
}
?>
<!-- Main Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <div class="d-flex align-items-center">
            <label for="timeFilter" class="me-2">Time Period:</label>
            <select id="timeFilter" class="form-select" style="width: 150px;" onchange="window.location.href='?time_filter=' + this.value">
                <option value="today" <?php echo $timeFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="yesterday" <?php echo $timeFilter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                <option value="week" <?php echo $timeFilter === 'week' ? 'selected' : ''; ?>>This Week</option>
                <option value="month" <?php echo $timeFilter === 'month' ? 'selected' : ''; ?>>This Month</option>
                <option value="year" <?php echo $timeFilter === 'year' ? 'selected' : ''; ?>>This Year</option>
            </select>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-primary text-white mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Restaurants</h6>
                            <h2 class="mb-0"><?php echo $total_restaurants; ?></h2>
                        </div>
                        <i class="fas fa-store stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Managers</h6>
                            <h2 class="mb-0"><?php echo $total_managers; ?></h2>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-warning text-white mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Revenue</h6>
                            <h2 class="mb-0">Tsh<?php echo number_format($total_revenue, 2); ?></h2>
                        </div>
                        <i class="fas fa-dollar-sign stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Trend Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Revenue Trend</h5>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Debug information
    console.log('Chart.js loaded:', typeof Chart !== 'undefined');
    
    document.addEventListener('DOMContentLoaded', function() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart');
        
        if (revenueCtx) {
            const revenueData = {
                labels: <?php echo json_encode(array_column($revenue_trend, 'date')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($revenue_trend, 'revenue')); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            };

            console.log('Revenue Chart Data:', revenueData);

            try {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: revenueData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Tsh${context.raw.toFixed(2)}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Tsh' + value.toLocaleString();
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                console.log('Revenue Chart initialized successfully');
            } catch (error) {
                console.error('Error initializing revenue chart:', error);
            }
        } else {
            console.error('Revenue chart canvas element not found');
        }
    });
</script>

<style>
    .stat-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .stat-icon {
        font-size: 2rem;
        opacity: 0.8;
    }
    
    /* Chart container styles */
    .chart-container {
        position: relative;
        height: 400px;
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
        border-radius: 8px;
        padding: 15px;
    }
    
    .card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-title {
        margin-bottom: 1.5rem;
        font-weight: 600;
        color: #333;
    }
</style>

<?php require_once 'includes/footer.php'; ?> 