<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if order_id is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Order ID not provided";
    header('Location: restaurant_dashboard.php');
    exit();
}

$order_id = $_GET['id'];

// Debug: Print the order ID and user ID
echo "<!-- Debug: Order ID: " . $order_id . " -->";
echo "<!-- Debug: User ID: " . $_SESSION['user_id'] . " -->";

// Get order details
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        u.name as customer_name,
        u.email as customer_email,
        u.phone_number as customer_phone,
        r.name as restaurant_name,
        r.address as restaurant_address,
        r.phone_number as restaurant_phone
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN restaurants r ON o.restaurant_id = r.restaurant_id
    WHERE o.order_id = ?
    AND o.restaurant_id = (
        SELECT restaurant_id 
        FROM restaurants 
        WHERE manager_id = ?
    )
");

// Debug: Check if the query executed successfully
try {
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    // Debug: Print the order data
    echo "<!-- Debug: Order data: " . print_r($order, true) . " -->";
    
    if (!$order) {
        $_SESSION['error'] = "Order not found or you don't have permission to view it";
        header('Location: restaurant_dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    echo "<!-- Debug: Database error: " . $e->getMessage() . " -->";
    $_SESSION['error'] = "Database error occurred";
    header('Location: restaurant_dashboard.php');
    exit();
}

// Get order items with more detailed debugging
try {
    // First, let's check the structure of the order_items table
    $table_structure = $pdo->query("DESCRIBE order_items");
    $structure = $table_structure->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: order_items table structure: " . print_r($structure, true) . " -->";

    // Check if there are any items for this order
    $check_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $check_items->execute([$order_id]);
    $raw_items = $check_items->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: Raw order items data: " . print_r($raw_items, true) . " -->";

    // Now try to get the order items with the join
    $stmt = $pdo->prepare("
        SELECT 
            oi.order_item_id,
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.price,
            oi.total_price,
            oi.special_instructions,
            p.product_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.order_item_id
    ");
    
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Print detailed order items information
    echo "<!-- Debug: Order ID: " . $order_id . " -->";
    echo "<!-- Debug: Number of items found: " . count($order_items) . " -->";
    if (count($order_items) > 0) {
        echo "<!-- Debug: First item: " . print_r($order_items[0], true) . " -->";
    } else {
        echo "<!-- Debug: No items found for this order -->";
    }
} catch (PDOException $e) {
    echo "<!-- Debug: Database error (order items): " . $e->getMessage() . " -->";
    $order_items = [];
}

// Get order status history
try {
    $stmt = $pdo->prepare("
        SELECT 
            oh.*,
            u.name as updated_by
        FROM order_history oh
        LEFT JOIN users u ON oh.updated_by = u.user_id
        WHERE oh.order_id = ?
        ORDER BY oh.created_at DESC
    ");
    $stmt->execute([$order_id]);
    $order_history = $stmt->fetchAll();
    
    // Debug: Print the order history
    echo "<!-- Debug: Order history: " . print_r($order_history, true) . " -->";
} catch (PDOException $e) {
    echo "<!-- Debug: Database error (order history): " . $e->getMessage() . " -->";
    $order_history = [];
}

// Debug: Check if we have all required data
echo "<!-- Debug: Order exists: " . (isset($order) ? 'Yes' : 'No') . " -->";
echo "<!-- Debug: Order items count: " . count($order_items) . " -->";
echo "<!-- Debug: Order history count: " . count($order_history) . " -->";
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order Details</h2>
                <div>
                    <a href="restaurant_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Order #<?php echo $order_id; ?></h4>
                </div>
                <div class="card-body">
                    <!-- Order Status and Actions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-<?php 
                                    echo $order['order_status'] == 'Completed' ? 'success' : 
                                        ($order['order_status'] == 'Pending' ? 'warning' : 
                                        ($order['order_status'] == 'Processing' ? 'info' : 
                                        ($order['order_status'] == 'Delivered' ? 'primary' : 'danger'))); 
                                ?> me-2" style="font-size: 1.1rem;">
                                    <?php echo $order['order_status']; ?>
                                </span>
                                <?php if ($order['order_status'] != 'Completed' && $order['order_status'] != 'Cancelled'): ?>
                                <div class="btn-group ms-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        Update Status
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus('Processing')">Processing</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus('Delivered')">Delivered</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus('Completed')">Completed</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus('Cancelled')">Cancelled</a></li>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="text-muted">Order Date: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    <p class="mb-1"><strong>Delivery Address:</strong> 
                                        <?php 
                                        if (isset($order['delivery_address']) && !empty($order['delivery_address'])) {
                                            echo htmlspecialchars($order['delivery_address']);
                                        } else {
                                            echo '<span class="text-muted">Not specified</span>';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Restaurant Information</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                                    <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($order['restaurant_address']); ?></p>
                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['restaurant_phone']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Order Items</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-end" style="width: 50%">Item</th>
                                            <th class="border-end text-end" style="width: 15%">Price</th>
                                            <th class="border-end text-center" style="width: 10%">Qty</th>
                                            <th class="text-end" style="width: 25%">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td class="border-end">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                        <?php if ($item['special_instructions']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['special_instructions']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="border-end text-end align-middle">TSh <?php echo number_format($item['price'], 2); ?></td>
                                            <td class="border-end text-center align-middle"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end align-middle fw-bold">TSh <?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="border-end text-end"><strong>Subtotal:</strong></td>
                                            <td class="text-end">TSh <?php echo number_format($order['subtotal'] ?? 0, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="border-end text-end"><strong>Delivery Fee:</strong></td>
                                            <td class="text-end">TSh <?php echo number_format($order['delivery_fee'] ?? 0, 2); ?></td>
                                        </tr>
                                        <tr class="table-active">
                                            <td colspan="3" class="border-end text-end"><strong>Total Amount:</strong></td>
                                            <td class="text-end fw-bold">TSh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Order History -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Order History</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($order_history as $history): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?php echo $history['status']; ?></h6>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($history['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo $history['notes']; ?></p>
                                        <?php if ($history['updated_by']): ?>
                                        <small class="text-muted">Updated by: <?php echo htmlspecialchars($history['updated_by']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -10px;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #007bff;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-left: 20px;
}

.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table > :not(caption) > * > * {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.table thead th {
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tfoot tr:last-child td {
    border-bottom: none;
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,.02);
}

.table tfoot tr:last-child {
    font-size: 1.1rem;
}

.border-end {
    border-right: 1px solid #dee2e6 !important;
}

.table-active {
    background-color: rgba(0,0,0,.05) !important;
}
</style>

<script>
function updateOrderStatus(newStatus) {
    if (confirm(`Are you sure you want to update the order status to "${newStatus}"?`)) {
        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: <?php echo $order_id; ?>,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update order status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the order status');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 