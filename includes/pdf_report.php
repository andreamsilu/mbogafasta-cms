<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';

class PDFReport extends FPDF {
    private $restaurant;
    private $start_date;
    private $end_date;
    private $sales_stats;
    private $top_products;
    private $daily_revenue;
    private $status_distribution;
    private $hourly_sales;
    private $payment_methods;
    private $customer_segments;
    private $product_categories;

    function __construct($restaurant, $start_date, $end_date, $sales_stats, $top_products, $daily_revenue, $status_distribution) {
        parent::__construct();
        $this->restaurant = $restaurant;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->sales_stats = $sales_stats;
        $this->top_products = $top_products;
        $this->daily_revenue = $daily_revenue;
        $this->status_distribution = $status_distribution;
        
        // Get additional data
        $this->getHourlySales();
        $this->getPaymentMethods();
        $this->getCustomerSegments();
        $this->getProductCategories();
    }

    private function getHourlySales() {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ");
        $stmt->execute([$this->restaurant['restaurant_id'], $this->start_date, $this->end_date]);
        $this->hourly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPaymentMethods() {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM orders 
            WHERE restaurant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY payment_method
        ");
        $stmt->execute([$this->restaurant['restaurant_id'], $this->start_date, $this->end_date]);
        $this->payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCustomerSegments() {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN total_orders >= 10 THEN 'Loyal'
                    WHEN total_orders >= 5 THEN 'Regular'
                    ELSE 'New'
                END as segment,
                COUNT(*) as customer_count,
                SUM(total_amount) as total_revenue
            FROM (
                SELECT 
                    user_id,
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_amount
                FROM orders 
                WHERE restaurant_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY user_id
            ) as customer_stats
            GROUP BY segment
        ");
        $stmt->execute([$this->restaurant['restaurant_id'], $this->start_date, $this->end_date]);
        $this->customer_segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getProductCategories() {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT 
                c.category_name,
                COUNT(DISTINCT oi.order_id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.total_price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE o.restaurant_id = ? 
            AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY c.category_id, c.category_name
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$this->restaurant['restaurant_id'], $this->start_date, $this->end_date]);
        $this->product_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function Header() {
        // Logo
        $this->Image('assets/images/logo.png', 10, 6, 30);
        
        // Title
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, $this->restaurant['name'] . ' - Detailed Sales Report', 0, 1, 'C');
        
        // Report Period
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Period: ' . date('M d, Y', strtotime($this->start_date)) . ' to ' . date('M d, Y', strtotime($this->end_date)), 0, 1, 'C');
        
        // Line break
        $this->Ln(10);
    }

    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function ChapterTitle($title) {
        // Arial 12
        $this->SetFont('Arial', 'B', 12);
        // Title
        $this->Cell(0, 6, $title, 0, 1, 'L');
        // Line break
        $this->Ln(4);
    }

    function PrintExecutiveSummary() {
        $this->ChapterTitle('Executive Summary');
        
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, "This report provides a comprehensive analysis of " . $this->restaurant['name'] . "'s performance during the specified period. Key highlights include total revenue, order volume, customer engagement, and product performance metrics.", 0, 'J');
        $this->Ln(5);
    }

    function PrintStats() {
        $this->ChapterTitle('Key Performance Indicators');
        
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(90, 7, 'Metric', 1);
        $this->Cell(90, 7, 'Value', 1);
        $this->Ln();
        
        // Table rows
        $this->SetFont('Arial', '', 10);
        
        // Total Orders
        $this->Cell(90, 7, 'Total Orders', 1);
        $this->Cell(90, 7, number_format($this->sales_stats['total_orders']), 1);
        $this->Ln();
        
        // Total Revenue
        $this->Cell(90, 7, 'Total Revenue', 1);
        $this->Cell(90, 7, 'TSh ' . number_format($this->sales_stats['total_revenue'], 2), 1);
        $this->Ln();
        
        // Total Customers
        $this->Cell(90, 7, 'Total Customers', 1);
        $this->Cell(90, 7, number_format($this->sales_stats['total_customers']), 1);
        $this->Ln();
        
        // Average Order Value
        $this->Cell(90, 7, 'Average Order Value', 1);
        $this->Cell(90, 7, 'TSh ' . number_format($this->sales_stats['avg_order_value'], 2), 1);
        $this->Ln();
        
        // Orders per Customer
        $orders_per_customer = $this->sales_stats['total_customers'] > 0 
            ? $this->sales_stats['total_orders'] / $this->sales_stats['total_customers'] 
            : 0;
        $this->Cell(90, 7, 'Orders per Customer', 1);
        $this->Cell(90, 7, number_format($orders_per_customer, 2), 1);
        $this->Ln();
        
        // Revenue per Customer
        $revenue_per_customer = $this->sales_stats['total_customers'] > 0 
            ? $this->sales_stats['total_revenue'] / $this->sales_stats['total_customers'] 
            : 0;
        $this->Cell(90, 7, 'Revenue per Customer', 1);
        $this->Cell(90, 7, 'TSh ' . number_format($revenue_per_customer, 2), 1);
        $this->Ln();
        
        // Add a line break
        $this->Ln(10);
    }

    function PrintHourlyAnalysis() {
        $this->ChapterTitle('Hourly Sales Analysis');
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(40, 10, 'Hour', 1, 0, 'C');
        $this->Cell(50, 10, 'Orders', 1, 0, 'C');
        $this->Cell(50, 10, 'Revenue', 1, 0, 'C');
        $this->Cell(50, 10, 'Avg Order Value', 1, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        foreach ($this->hourly_sales as $hour) {
            $this->Cell(40, 10, sprintf('%02d:00 - %02d:59', $hour['hour'], $hour['hour']), 1, 0, 'C');
            $this->Cell(50, 10, number_format($hour['order_count']), 1, 0, 'C');
            $this->Cell(50, 10, 'TSh ' . number_format($hour['revenue'], 2), 1, 0, 'R');
            $this->Cell(50, 10, 'TSh ' . number_format($hour['revenue'] / $hour['order_count'], 2), 1, 1, 'R');
        }
        
        $this->Ln(10);
    }

    function PrintPaymentMethods() {
        $this->ChapterTitle('Payment Method Analysis');
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 10, 'Payment Method', 1, 0, 'C');
        $this->Cell(50, 10, 'Orders', 1, 0, 'C');
        $this->Cell(60, 10, 'Total Amount', 1, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        foreach ($this->payment_methods as $method) {
            $this->Cell(80, 10, ucfirst($method['payment_method']), 1, 0, 'L');
            $this->Cell(50, 10, number_format($method['count']), 1, 0, 'C');
            $this->Cell(60, 10, 'TSh ' . number_format($method['total_amount'], 2), 1, 1, 'R');
        }
        
        $this->Ln(10);
    }

    function PrintCustomerSegments() {
        $this->ChapterTitle('Customer Segment Analysis');
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(60, 10, 'Segment', 1, 0, 'C');
        $this->Cell(50, 10, 'Customers', 1, 0, 'C');
        $this->Cell(80, 10, 'Total Revenue', 1, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        foreach ($this->customer_segments as $segment) {
            $this->Cell(60, 10, $segment['segment'], 1, 0, 'L');
            $this->Cell(50, 10, number_format($segment['customer_count']), 1, 0, 'C');
            $this->Cell(80, 10, 'TSh ' . number_format($segment['total_revenue'], 2), 1, 1, 'R');
        }
        
        $this->Ln(10);
    }

    function PrintProductCategories() {
        $this->ChapterTitle('Product Category Performance');
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 10, 'Category', 1, 0, 'C');
        $this->Cell(40, 10, 'Orders', 1, 0, 'C');
        $this->Cell(40, 10, 'Quantity', 1, 0, 'C');
        $this->Cell(30, 10, 'Revenue', 1, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        foreach ($this->product_categories as $category) {
            $this->Cell(80, 10, $category['category_name'], 1, 0, 'L');
            $this->Cell(40, 10, number_format($category['order_count']), 1, 0, 'C');
            $this->Cell(40, 10, number_format($category['total_quantity']), 1, 0, 'C');
            $this->Cell(30, 10, 'TSh ' . number_format($category['total_revenue'], 2), 1, 1, 'R');
        }
        
        $this->Ln(10);
    }

    private function PrintTopProducts() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Top Products', 0, 1, 'L');
        
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 7, 'Product', 1);
        $this->Cell(30, 7, 'Quantity', 1);
        $this->Cell(40, 7, 'Revenue', 1);
        $this->Cell(40, 7, '% of Total', 1);
        $this->Ln();
        
        // Table rows
        $this->SetFont('Arial', '', 10);
        $total_quantity = 0;
        $total_revenue = 0;
        
        foreach ($this->top_products as $product) {
            $this->Cell(80, 7, $product['name'], 1);
            $this->Cell(30, 7, number_format($product['quantity']), 1, 0, 'R');
            $this->Cell(40, 7, 'Ksh ' . number_format($product['revenue'], 2), 1, 0, 'R');
            $this->Cell(40, 7, number_format(($product['revenue'] / $this->sales_stats['total_revenue']) * 100, 2) . '%', 1, 0, 'R');
            $this->Ln();
            
            $total_quantity += $product['quantity'];
            $total_revenue += $product['revenue'];
        }
        
        // Total row
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 7, 'Total', 1);
        $this->Cell(30, 7, number_format($total_quantity), 1, 0, 'R');
        $this->Cell(40, 7, 'Ksh ' . number_format($total_revenue, 2), 1, 0, 'R');
        $this->Cell(40, 7, '100%', 1, 0, 'R');
        $this->Ln();
    }

    private function PrintDailyRevenue() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Daily Revenue', 0, 1, 'L');
        
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(50, 7, 'Date', 1);
        $this->Cell(30, 7, 'Orders', 1);
        $this->Cell(40, 7, 'Revenue', 1);
        $this->Cell(40, 7, 'Avg Order', 1);
        $this->Cell(30, 7, '% of Total', 1);
        $this->Ln();
        
        // Table rows
        $this->SetFont('Arial', '', 10);
        $total_orders = 0;
        $total_revenue = 0;
        
        foreach ($this->daily_revenue as $day) {
            $this->Cell(50, 7, $day['date'], 1);
            $this->Cell(30, 7, number_format($day['orders']), 1, 0, 'R');
            $this->Cell(40, 7, 'Ksh ' . number_format($day['revenue'], 2), 1, 0, 'R');
            $this->Cell(40, 7, 'Ksh ' . number_format($day['revenue'] / $day['orders'], 2), 1, 0, 'R');
            $this->Cell(30, 7, number_format(($day['revenue'] / $this->sales_stats['total_revenue']) * 100, 2) . '%', 1, 0, 'R');
            $this->Ln();
            
            $total_orders += $day['orders'];
            $total_revenue += $day['revenue'];
        }
        
        // Total row
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(50, 7, 'Total', 1);
        $this->Cell(30, 7, number_format($total_orders), 1, 0, 'R');
        $this->Cell(40, 7, 'Ksh ' . number_format($total_revenue, 2), 1, 0, 'R');
        $this->Cell(40, 7, 'Ksh ' . number_format($total_revenue / $total_orders, 2), 1, 0, 'R');
        $this->Cell(30, 7, '100%', 1, 0, 'R');
        $this->Ln();
    }

    private function PrintOrderStatus() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Order Status Distribution', 0, 1, 'L');
        
        // Table header
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 7, 'Status', 1);
        $this->Cell(40, 7, 'Count', 1);
        $this->Cell(40, 7, 'Percentage', 1);
        $this->Ln();
        
        // Table rows
        $this->SetFont('Arial', '', 10);
        $total_orders = 0;
        
        // Calculate total orders first
        foreach ($this->status_distribution as $count) {
            $total_orders += $count;
        }
        
        // Print each status
        foreach ($this->status_distribution as $status => $count) {
            $percentage = $total_orders > 0 ? ($count / $total_orders) * 100 : 0;
            
            $this->Cell(80, 7, ucfirst(str_replace('_', ' ', $status)), 1);
            $this->Cell(40, 7, number_format($count), 1, 0, 'R');
            $this->Cell(40, 7, number_format($percentage, 2) . '%', 1, 0, 'R');
            $this->Ln();
        }
        
        // Total row
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(80, 7, 'Total', 1);
        $this->Cell(40, 7, number_format($total_orders), 1, 0, 'R');
        $this->Cell(40, 7, '100%', 1, 0, 'R');
        $this->Ln();
    }
}

function generatePDFReport($restaurant, $start_date, $end_date, $sales_stats, $top_products, $daily_revenue, $status_distribution) {
    // Create new PDF document
    $pdf = new PDFReport($restaurant, $start_date, $end_date, $sales_stats, $top_products, $daily_revenue, $status_distribution);
    
    // Set document information
    $pdf->SetTitle('Restaurant Sales Report');
    $pdf->SetAuthor($restaurant['name']);
    
    // Add a page
    $pdf->AddPage();
    
    // Print all sections
    $pdf->PrintExecutiveSummary();
    $pdf->PrintStats();
    $pdf->PrintHourlyAnalysis();
    $pdf->PrintPaymentMethods();
    $pdf->PrintCustomerSegments();
    $pdf->PrintProductCategories();
    $pdf->PrintTopProducts();
    $pdf->PrintDailyRevenue();
    $pdf->PrintOrderStatus();
    
    // Output the PDF
    $filename = 'restaurant_report_' . date('Y-m-d') . '.pdf';
    $pdf->Output('D', $filename);
} 