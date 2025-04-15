<?php
class Banner {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createBanner($image_path, $title, $description = null) {
        return $this->db->insert('banners', [
            'image_path' => $image_path,
            'title' => $title,
            'description' => $description,
            'is_active' => 1
        ]);
    }

    public function updateBanner($banner_id, $data) {
        return $this->db->update(
            'banners',
            $data,
            'id = ?',
            [$banner_id]
        );
    }

    public function deleteBanner($banner_id) {
        return $this->db->delete(
            'banners',
            'id = ?',
            [$banner_id]
        );
    }

    public function getBannerById($banner_id) {
        return $this->db->fetch(
            "SELECT * FROM banners WHERE id = ?",
            [$banner_id]
        );
    }

    public function getAllBanners($active_only = false) {
        $sql = "SELECT * FROM banners";
        $params = [];

        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }

        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function toggleBannerStatus($banner_id) {
        $banner = $this->getBannerById($banner_id);
        if (!$banner) {
            throw new Exception("Banner not found");
        }

        return $this->db->update(
            'banners',
            ['is_active' => !$banner['is_active']],
            'id = ?',
            [$banner_id]
        );
    }

    public function getActiveBanners() {
        return $this->getAllBanners(true);
    }

    public function updateBannerImage($banner_id, $new_image_path) {
        return $this->db->update(
            'banners',
            ['image_path' => $new_image_path],
            'id = ?',
            [$banner_id]
        );
    }

    public function getBannerStatistics() {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as total_banners,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_banners,
                COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_banners
             FROM banners"
        );
    }
} 