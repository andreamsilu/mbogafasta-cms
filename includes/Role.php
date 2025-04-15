<?php
class Role {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createRole($role_name, $description = null) {
        return $this->db->insert('roles', [
            'role_name' => $role_name,
            'description' => $description
        ]);
    }

    public function updateRole($role_id, $data) {
        return $this->db->update(
            'roles',
            $data,
            'role_id = ?',
            [$role_id]
        );
    }

    public function deleteRole($role_id) {
        // Check if role has users
        $users = $this->db->fetchAll(
            "SELECT COUNT(*) as count FROM users WHERE role_id = ?",
            [$role_id]
        );

        if ($users[0]['count'] > 0) {
            throw new Exception("Cannot delete role with assigned users");
        }

        // Delete role permissions first
        $this->db->delete(
            'role_permissions',
            'role_id = ?',
            [$role_id]
        );

        return $this->db->delete(
            'roles',
            'role_id = ?',
            [$role_id]
        );
    }

    public function getRoleById($role_id) {
        return $this->db->fetch(
            "SELECT r.*, 
                    GROUP_CONCAT(p.permission_name) as permissions
             FROM roles r
             LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
             LEFT JOIN permissions p ON rp.permission_id = p.permission_id
             WHERE r.role_id = ?
             GROUP BY r.role_id",
            [$role_id]
        );
    }

    public function getAllRoles() {
        return $this->db->fetchAll(
            "SELECT r.*, 
                    COUNT(u.user_id) as user_count,
                    GROUP_CONCAT(p.permission_name) as permissions
             FROM roles r
             LEFT JOIN users u ON r.role_id = u.role_id
             LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
             LEFT JOIN permissions p ON rp.permission_id = p.permission_id
             GROUP BY r.role_id
             ORDER BY r.role_name"
        );
    }

    public function assignPermission($role_id, $permission_id) {
        // Check if permission is already assigned
        $existing = $this->db->fetch(
            "SELECT * FROM role_permissions 
             WHERE role_id = ? AND permission_id = ?",
            [$role_id, $permission_id]
        );

        if (!$existing) {
            return $this->db->insert('role_permissions', [
                'role_id' => $role_id,
                'permission_id' => $permission_id
            ]);
        }

        return true;
    }

    public function removePermission($role_id, $permission_id) {
        return $this->db->delete(
            'role_permissions',
            'role_id = ? AND permission_id = ?',
            [$role_id, $permission_id]
        );
    }

    public function getRolePermissions($role_id) {
        return $this->db->fetchAll(
            "SELECT p.*
             FROM permissions p
             JOIN role_permissions rp ON p.permission_id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.permission_name",
            [$role_id]
        );
    }

    public function hasPermission($role_id, $permission_name) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count
             FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.permission_id
             WHERE rp.role_id = ? AND p.permission_name = ?",
            [$role_id, $permission_name]
        );

        return $result && $result['count'] > 0;
    }

    public function getAllPermissions() {
        return $this->db->fetchAll(
            "SELECT * FROM permissions ORDER BY permission_name"
        );
    }

    public function createPermission($permission_name, $description = null) {
        return $this->db->insert('permissions', [
            'permission_name' => $permission_name,
            'description' => $description
        ]);
    }

    public function updatePermission($permission_id, $data) {
        return $this->db->update(
            'permissions',
            $data,
            'permission_id = ?',
            [$permission_id]
        );
    }

    public function deletePermission($permission_id) {
        // Remove from role_permissions first
        $this->db->delete(
            'role_permissions',
            'permission_id = ?',
            [$permission_id]
        );

        return $this->db->delete(
            'permissions',
            'permission_id = ?',
            [$permission_id]
        );
    }

    public function getRoleStatistics() {
        return $this->db->fetchAll(
            "SELECT r.role_name, 
                    COUNT(u.user_id) as user_count,
                    COUNT(rp.permission_id) as permission_count
             FROM roles r
             LEFT JOIN users u ON r.role_id = u.role_id
             LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
             GROUP BY r.role_id
             ORDER BY user_count DESC"
        );
    }
} 