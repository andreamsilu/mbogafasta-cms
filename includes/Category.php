<?php
class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createCategory($category_name, $description = null) {
        return $this->db->insert('categories', [
            'category_name' => $category_name,
            'description' => $description
        ]);
    }

    public function updateCategory($category_id, $data) {
        return $this->db->update(
            'categories',
            $data,
            'category_id = ?',
            [$category_id]
        );
    }

    public function deleteCategory($category_id) {
        // Check if category has products
        $products = $this->db->fetchAll(
            "SELECT COUNT(*) as count FROM products WHERE category_id = ?",
            [$category_id]
        );

        if ($products[0]['count'] > 0) {
            throw new Exception("Cannot delete category with existing products");
        }

        return $this->db->delete(
            'categories',
            'category_id = ?',
            [$category_id]
        );
    }

    public function getCategoryById($category_id) {
        return $this->db->fetch(
            "SELECT * FROM categories WHERE category_id = ?",
            [$category_id]
        );
    }

    public function getAllCategories() {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count
             FROM categories c
             ORDER BY c.category_name"
        );
    }

    public function getCategoryProducts($category_id) {
        return $this->db->fetchAll(
            "SELECT p.*, r.name as restaurant_name
             FROM products p
             LEFT JOIN restaurants r ON p.restaurant_id = r.restaurant_id
             WHERE p.category_id = ?
             ORDER BY p.product_name",
            [$category_id]
        );
    }

    public function searchCategories($query) {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count
             FROM categories c
             WHERE c.category_name LIKE ? OR c.description LIKE ?
             ORDER BY c.category_name",
            ["%$query%", "%$query%"]
        );
    }

    public function getCategoryStatistics() {
        return $this->db->fetchAll(
            "SELECT c.category_name, 
                    COUNT(p.product_id) as product_count,
                    AVG(p.price) as average_price,
                    MIN(p.price) as min_price,
                    MAX(p.price) as max_price
             FROM categories c
             LEFT JOIN products p ON c.category_id = p.category_id
             GROUP BY c.category_id
             ORDER BY product_count DESC"
        );
    }

    public function getPopularCategories($limit = 5) {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    COUNT(p.product_id) as product_count
             FROM categories c
             LEFT JOIN products p ON c.category_id = p.category_id
             GROUP BY c.category_id
             ORDER BY product_count DESC
             LIMIT ?",
            [$limit]
        );
    }
} 