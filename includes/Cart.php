<?php
class Cart {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getOrCreateCart($user_id) {
        $cart = $this->db->fetch(
            "SELECT * FROM carts WHERE user_id = ? AND status = 'active'",
            [$user_id]
        );

        if (!$cart) {
            $cart_id = $this->db->insert('carts', [
                'user_id' => $user_id,
                'status' => 'active',
                'total_price' => 0
            ]);
            $cart = $this->db->fetch(
                "SELECT * FROM carts WHERE cart_id = ?",
                [$cart_id]
            );
        }

        return $cart;
    }

    public function addToCart($cart_id, $product_id, $quantity = 1) {
        // Check if product already exists in cart
        $existing_item = $this->db->fetch(
            "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?",
            [$cart_id, $product_id]
        );

        if ($existing_item) {
            // Update quantity
            return $this->db->update(
                'cart_items',
                ['quantity' => $existing_item['quantity'] + $quantity],
                'cart_item_id = ?',
                [$existing_item['cart_item_id']]
            );
        } else {
            // Add new item
            return $this->db->insert('cart_items', [
                'cart_id' => $cart_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'status' => '0'
            ]);
        }
    }

    public function updateCartItem($cart_item_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($cart_item_id);
        }

        return $this->db->update(
            'cart_items',
            ['quantity' => $quantity],
            'cart_item_id = ?',
            [$cart_item_id]
        );
    }

    public function removeFromCart($cart_item_id) {
        return $this->db->delete(
            'cart_items',
            'cart_item_id = ?',
            [$cart_item_id]
        );
    }

    public function getCartItems($cart_id) {
        return $this->db->fetchAll(
            "SELECT ci.*, p.product_name, p.price, p.description, p.image_url
             FROM cart_items ci
             LEFT JOIN products p ON ci.product_id = p.product_id
             WHERE ci.cart_id = ?",
            [$cart_id]
        );
    }

    public function getCartTotal($cart_id) {
        $items = $this->getCartItems($cart_id);
        $total = 0;

        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Update cart total
        $this->db->update(
            'carts',
            ['total_price' => $total],
            'cart_id = ?',
            [$cart_id]
        );

        return $total;
    }

    public function clearCart($cart_id) {
        // Remove all items
        $this->db->delete('cart_items', 'cart_id = ?', [$cart_id]);
        
        // Reset cart total
        return $this->db->update(
            'carts',
            ['total_price' => 0],
            'cart_id = ?',
            [$cart_id]
        );
    }

    public function getCartCount($cart_id) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM cart_items WHERE cart_id = ?",
            [$cart_id]
        );
        return $result ? $result['count'] : 0;
    }

    public function checkProductAvailability($product_id, $quantity) {
        $product = $this->db->fetch(
            "SELECT stock_quantity FROM products WHERE product_id = ?",
            [$product_id]
        );
        return $product && $product['stock_quantity'] >= $quantity;
    }
} 