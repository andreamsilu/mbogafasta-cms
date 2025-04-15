<?php
class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createNotification($user_id, $message) {
        return $this->db->insert('notifications', [
            'user_id' => $user_id,
            'message' => $message,
            'is_read' => 0
        ]);
    }

    public function markAsRead($notification_id) {
        return $this->db->update(
            'notifications',
            ['is_read' => 1],
            'notification_id = ?',
            [$notification_id]
        );
    }

    public function markAllAsRead($user_id) {
        return $this->db->update(
            'notifications',
            ['is_read' => 1],
            'user_id = ? AND is_read = 0',
            [$user_id]
        );
    }

    public function getUserNotifications($user_id, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$user_id, $limit]
        );
    }

    public function getUnreadCount($user_id) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM notifications 
             WHERE user_id = ? AND is_read = 0",
            [$user_id]
        );
        return $result ? $result['count'] : 0;
    }

    public function deleteNotification($notification_id) {
        return $this->db->delete(
            'notifications',
            'notification_id = ?',
            [$notification_id]
        );
    }

    public function deleteOldNotifications($days = 30) {
        return $this->db->query(
            "DELETE FROM notifications 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }

    // Notification types and their messages
    public function sendOrderStatusNotification($user_id, $order_id, $status) {
        $message = "Your order #$order_id status has been updated to: $status";
        return $this->createNotification($user_id, $message);
    }

    public function sendReviewNotification($user_id, $restaurant_id, $rating) {
        $message = "Thank you for your $rating-star review!";
        return $this->createNotification($user_id, $message);
    }

    public function sendPromotionNotification($user_id, $promotion) {
        $message = "New promotion available: $promotion";
        return $this->createNotification($user_id, $message);
    }

    public function sendSystemNotification($user_id, $message) {
        return $this->createNotification($user_id, $message);
    }
} 