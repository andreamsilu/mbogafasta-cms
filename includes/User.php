<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createUser($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->db->insert('users', $data);
    }

    public function updateUser($user_id, $data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->db->update(
            'users',
            $data,
            'user_id = ?',
            [$user_id]
        );
    }

    public function deleteUser($user_id) {
        return $this->db->delete(
            'users',
            'user_id = ?',
            [$user_id]
        );
    }

    public function getUserById($user_id) {
        return $this->db->fetch(
            "SELECT u.*, r.role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.role_id
             WHERE u.user_id = ?",
            [$user_id]
        );
    }

    public function getUserByEmail($email) {
        return $this->db->fetch(
            "SELECT u.*, r.role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.role_id
             WHERE u.email = ?",
            [$email]
        );
    }

    public function getAllUsers($role_id = null) {
        $sql = "SELECT u.*, r.role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.role_id";
        
        $params = [];
        if ($role_id) {
            $sql .= " WHERE u.role_id = ?";
            $params[] = $role_id;
        }
        
        $sql .= " ORDER BY u.name";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function verifyUser($user_id) {
        return $this->db->update(
            'users',
            ['is_verified' => 1],
            'user_id = ?',
            [$user_id]
        );
    }

    public function updateProfilePicture($user_id, $profile_pic) {
        return $this->db->update(
            'users',
            ['profile_pic' => $profile_pic],
            'user_id = ?',
            [$user_id]
        );
    }

    public function updatePassword($user_id, $new_password) {
        return $this->db->update(
            'users',
            ['password_hash' => password_hash($new_password, PASSWORD_DEFAULT)],
            'user_id = ?',
            [$user_id]
        );
    }

    public function verifyPassword($user_id, $password) {
        $user = $this->getUserById($user_id);
        return $user && password_verify($password, $user['password_hash']);
    }

    public function generatePasswordResetToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->db->insert('password_reset_tokens', [
            'user_id' => $user_id,
            'token' => $token,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        return $token;
    }

    public function validatePasswordResetToken($token) {
        return $this->db->fetch(
            "SELECT * FROM password_reset_tokens 
             WHERE token = ? AND createdAt > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$token]
        );
    }

    public function getUserStatistics() {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_users,
                COUNT(CASE WHEN role_id = 1 THEN 1 END) as admin_users,
                COUNT(CASE WHEN role_id = 2 THEN 1 END) as customer_users,
                COUNT(CASE WHEN role_id = 3 THEN 1 END) as manager_users
             FROM users"
        );
    }

    public function searchUsers($query) {
        return $this->db->fetchAll(
            "SELECT u.*, r.role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.role_id
             WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?
             ORDER BY u.name",
            ["%$query%", "%$query%", "%$query%"]
        );
    }
} 