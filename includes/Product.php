<?php
class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllProducts($restaurant_id = null) {
        $sql = "SELECT p.*, c.category_name, r.name as restaurant_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id";
        
        $params = [];
        if ($restaurant_id) {
            $sql .= " WHERE p.restaurant_id = ?";
            $params[] = $restaurant_id;
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getProductById($product_id) {
        $sql = "SELECT p.*, c.category_name, r.name as restaurant_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id 
                WHERE p.product_id = ?";
        
        return $this->db->fetch($sql, [$product_id]);
    }

    public function createProduct($data) {
        return $this->db->insert('products', $data);
    }

    public function updateProduct($product_id, $data) {
        return $this->db->update('products', $data, 'product_id = ?', [$product_id]);
    }

    public function deleteProduct($product_id) {
        return $this->db->delete('products', 'product_id = ?', [$product_id]);
    }

    public function getProductImages($product_id) {
        return $this->db->fetchAll(
            "SELECT * FROM products_images WHERE product_id = ?",
            [$product_id]
        );
    }

    public function addProductImage($product_id, $image_url) {
        return $this->db->insert('products_images', [
            'product_id' => $product_id,
            'image_url' => $image_url
        ]);
    }

    public function deleteProductImage($image_id) {
        return $this->db->delete('products_images', 'id = ?', [$image_id]);
    }

    public function getProductsByCategory($category_id) {
        return $this->db->fetchAll(
            "SELECT p.*, c.category_name, r.name as restaurant_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.category_id 
             LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id 
             WHERE p.category_id = ?",
            [$category_id]
        );
    }

    public function searchProducts($query) {
        $sql = "SELECT p.*, c.category_name, r.name as restaurant_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id 
                WHERE p.product_name LIKE ? OR p.description LIKE ?";
        
        $searchTerm = "%$query%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm]);
    }
} 