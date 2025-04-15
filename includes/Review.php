<?php
class Review {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createReview($user_id, $restaurant_id, $rating, $comment = null) {
        // Check if user has already reviewed this restaurant
        $existing_review = $this->db->fetch(
            "SELECT * FROM reviews WHERE user_id = ? AND restaurant_id = ?",
            [$user_id, $restaurant_id]
        );

        if ($existing_review) {
            throw new Exception("You have already reviewed this restaurant");
        }

        // Create new review
        $review_id = $this->db->insert('reviews', [
            'user_id' => $user_id,
            'restaurant_id' => $restaurant_id,
            'rating' => $rating,
            'comment' => $comment
        ]);

        // Update restaurant average rating
        $this->updateRestaurantRating($restaurant_id);

        return $review_id;
    }

    public function updateReview($review_id, $rating, $comment = null) {
        $review = $this->getReviewById($review_id);
        if (!$review) {
            throw new Exception("Review not found");
        }

        $this->db->update('reviews', 
            ['rating' => $rating, 'comment' => $comment],
            'review_id = ?',
            [$review_id]
        );

        // Update restaurant average rating
        $this->updateRestaurantRating($review['restaurant_id']);

        return true;
    }

    public function deleteReview($review_id) {
        $review = $this->getReviewById($review_id);
        if (!$review) {
            throw new Exception("Review not found");
        }

        $this->db->delete('reviews', 'review_id = ?', [$review_id]);

        // Update restaurant average rating
        $this->updateRestaurantRating($review['restaurant_id']);

        return true;
    }

    public function getReviewById($review_id) {
        return $this->db->fetch(
            "SELECT r.*, u.name as user_name
             FROM reviews r
             LEFT JOIN users u ON r.user_id = u.user_id
             WHERE r.review_id = ?",
            [$review_id]
        );
    }

    public function getRestaurantReviews($restaurant_id) {
        return $this->db->fetchAll(
            "SELECT r.*, u.name as user_name, u.profile_pic
             FROM reviews r
             LEFT JOIN users u ON r.user_id = u.user_id
             WHERE r.restaurant_id = ?
             ORDER BY r.created_at DESC",
            [$restaurant_id]
        );
    }

    public function getUserReviews($user_id) {
        return $this->db->fetchAll(
            "SELECT r.*, res.name as restaurant_name
             FROM reviews r
             LEFT JOIN restaurants res ON r.restaurant_id = res.restaurant_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC",
            [$user_id]
        );
    }

    public function getRestaurantRating($restaurant_id) {
        return $this->db->fetch(
            "SELECT 
                AVG(rating) as average_rating,
                COUNT(*) as total_reviews,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
             FROM reviews
             WHERE restaurant_id = ?",
            [$restaurant_id]
        );
    }

    private function updateRestaurantRating($restaurant_id) {
        $rating = $this->getRestaurantRating($restaurant_id);
        $average_rating = $rating['average_rating'] ?? 0;

        return $this->db->update(
            'restaurants',
            ['rating' => $average_rating],
            'restaurant_id = ?',
            [$restaurant_id]
        );
    }

    public function getRecentReviews($limit = 10) {
        return $this->db->fetchAll(
            "SELECT r.*, u.name as user_name, res.name as restaurant_name
             FROM reviews r
             LEFT JOIN users u ON r.user_id = u.user_id
             LEFT JOIN restaurants res ON r.restaurant_id = res.restaurant_id
             ORDER BY r.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }
} 