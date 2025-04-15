<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth_check.php';
require_once 'includes/pdf_report.php';

// Get parameters
$restaurant_id = $_GET['restaurant_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Log the received parameters
error_log("PDF Generation Request - Restaurant ID: $restaurant_id, Start Date: $start_date, End Date: $end_date");

if (!$restaurant_id) {
    die('Restaurant ID is required');
}

try {
    // Get restaurant data
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE restaurant_id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch();

    if (!$restaurant) {
        die('Restaurant not found');
    }

    // Get sales statistics
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
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $sales_stats = $stmt->fetch();

    // Get top products
    $stmt = $pdo->prepare("
        SELECT 
            p.product_name,
            COUNT(DISTINCT oi.order_id) as order_count,
            SUM(oi.quantity) as total_quantity,
            COALESCE(SUM(oi.total_price), 0) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE o.restaurant_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.product_id, p.product_name
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $top_products = $stmt->fetchAll();

    // Get daily revenue
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COALESCE(SUM(total_amount), 0) as revenue
        FROM orders
        WHERE restaurant_id = ?
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $daily_revenue = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get order status distribution
    $stmt = $pdo->prepare("
        SELECT 
            order_status,
            COUNT(*) as count
        FROM orders
        WHERE restaurant_id = ?
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY order_status
    ");
    $stmt->execute([$restaurant_id, $start_date, $end_date]);
    $status_distribution = [];
    while ($row = $stmt->fetch()) {
        $status_distribution[$row['order_status']] = $row['count'];
    }

    // Log the data for debugging
    error_log("Status Distribution Data: " . print_r($status_distribution, true));

    // Log the data being passed to the PDF generator
    error_log("Data for PDF Generation - Restaurant: " . print_r($restaurant, true));
    error_log("Sales Stats: " . print_r($sales_stats, true));
    error_log("Top Products: " . print_r($top_products, true));
    error_log("Daily Revenue: " . print_r($daily_revenue, true));

    // Generate and download the PDF report
    generatePDFReport($restaurant, $start_date, $end_date, $sales_stats, $top_products, $daily_revenue, $status_distribution);
} catch (Exception $e) {
    error_log("Error generating PDF: " . $e->getMessage());
    die("Error generating PDF: " . $e->getMessage());
} 