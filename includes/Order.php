<?php
class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createOrder($user_id, $restaurant_id, $cart_id, $total_amount, $shipping_address, $pay_method) {
        $order_id = $this->db->insert('orders', [
            'user_id' => $user_id,
            'restaurant_id' => $restaurant_id,
            'cart_id' => $cart_id,
            'total_amount' => $total_amount,
            'shipping_address' => $shipping_address,
            'order_status' => 'Pending',
            'pay_method' => $pay_method
        ]);

        // Move cart items to order items
        $cart_items = $this->db->fetchAll(
            "SELECT * FROM cart_items WHERE cart_id = ?",
            [$cart_id]
        );

        foreach ($cart_items as $item) {
            $this->db->insert('order_items', [
                'order_id' => $order_id,
                'cart_item_id' => $item['cart_item_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $this->getProductPrice($item['product_id'])
            ]);
        }

        // Update cart status
        $this->db->update('carts', ['status' => 'completed'], 'cart_id = ?', [$cart_id]);

        return $order_id;
    }

    public function getOrderById($order_id) {
        $order = $this->db->fetch(
            "SELECT o.*, r.name as restaurant_name, u.name as user_name
             FROM orders o
             LEFT JOIN restaurants r ON o.restaurant_id = r.restaurant_id
             LEFT JOIN users u ON o.user_id = u.user_id
             WHERE o.order_id = ?",
            [$order_id]
        );

        if ($order) {
            $order['items'] = $this->getOrderItems($order_id);
        }

        return $order;
    }

    public function getOrderItems($order_id) {
        return $this->db->fetchAll(
            "SELECT oi.*, p.product_name, p.description
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.product_id
             WHERE oi.order_id = ?",
            [$order_id]
        );
    }

    public function getUserOrders($user_id) {
        $orders = $this->db->fetchAll(
            "SELECT o.*, r.name as restaurant_name
             FROM orders o
             LEFT JOIN restaurants r ON o.restaurant_id = r.restaurant_id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC",
            [$user_id]
        );

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['order_id']);
        }

        return $orders;
    }

    public function getRestaurantOrders($restaurant_id) {
        $orders = $this->db->fetchAll(
            "SELECT o.*, u.name as user_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.user_id
             WHERE o.restaurant_id = ?
             ORDER BY o.created_at DESC",
            [$restaurant_id]
        );

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['order_id']);
        }

        return $orders;
    }

    public function updateOrderStatus($order_id, $status) {
        return $this->db->update('orders', 
            ['order_status' => $status], 
            'order_id = ?', 
            [$order_id]
        );
    }

    private function getProductPrice($product_id) {
        $product = $this->db->fetch(
            "SELECT price FROM products WHERE product_id = ?",
            [$product_id]
        );
        return $product ? $product['price'] : 0;
    }

    public function getOrderStatistics($restaurant_id = null) {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value,
                    COUNT(CASE WHEN order_status = 'Completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN order_status = 'Pending' THEN 1 END) as pending_orders,
                    COUNT(CASE WHEN order_status = 'Processing' THEN 1 END) as processing_orders,
                    COUNT(CASE WHEN order_status = 'Cancelled' THEN 1 END) as cancelled_orders
                FROM orders";
        
        $params = [];
        if ($restaurant_id) {
            $sql .= " WHERE restaurant_id = ?";
            $params[] = $restaurant_id;
        }

        return $this->db->fetch($sql, $params);
    }
} 