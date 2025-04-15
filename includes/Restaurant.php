<?php
class Restaurant {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllRestaurants() {
        return $this->db->fetchAll(
            "SELECT r.*, 
                    (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as average_rating,
                    (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count
             FROM restaurants r
             ORDER BY r.name"
        );
    }

    public function getRestaurantById($restaurant_id) {
        $sql = "SELECT r.*, 
                       (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as average_rating,
                       (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count
                FROM restaurants r
                WHERE r.restaurant_id = ?";
        
        return $this->db->fetch($sql, [$restaurant_id]);
    }

    public function createRestaurant($data) {
        return $this->db->insert('restaurants', $data);
    }

    public function updateRestaurant($restaurant_id, $data) {
        return $this->db->update('restaurants', $data, 'restaurant_id = ?', [$restaurant_id]);
    }

    public function deleteRestaurant($restaurant_id) {
        return $this->db->delete('restaurants', 'restaurant_id = ?', [$restaurant_id]);
    }

    public function getRestaurantImages($restaurant_id) {
        return $this->db->fetchAll(
            "SELECT * FROM restaurant_images WHERE restaurant_id = ?",
            [$restaurant_id]
        );
    }

    public function addRestaurantImage($restaurant_id, $image_url) {
        return $this->db->insert('restaurant_images', [
            'restaurant_id' => $restaurant_id,
            'image_url' => $image_url
        ]);
    }

    public function deleteRestaurantImage($image_id) {
        return $this->db->delete('restaurant_images', 'id = ?', [$image_id]);
    }

    public function getRestaurantMenu($restaurant_id) {
        return $this->db->fetchAll(
            "SELECT m.*, c.category_name
             FROM menu_items m
             LEFT JOIN categories c ON m.category_id = c.category_id
             WHERE m.restaurant_id = ?
             ORDER BY c.category_name, m.name",
            [$restaurant_id]
        );
    }

    public function getNearbyRestaurants($latitude, $longitude, $radius = 10) {
        $sql = "SELECT r.*, 
                       (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as average_rating,
                       (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM restaurants r
                HAVING distance < ?
                ORDER BY distance";
        
        return $this->db->fetchAll($sql, [$latitude, $longitude, $latitude, $radius]);
    }

    public function searchRestaurants($query) {
        $sql = "SELECT r.*, 
                       (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.restaurant_id) as average_rating,
                       (SELECT COUNT(*) FROM reviews WHERE restaurant_id = r.restaurant_id) as review_count
                FROM restaurants r
                WHERE r.name LIKE ? OR r.description LIKE ? OR r.address LIKE ?
                ORDER BY r.name";
        
        $searchTerm = "%$query%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }
} 