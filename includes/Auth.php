<?php
class Auth {
    private $db;
    private $session;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->session = new Session();
    }

    public function login($username, $password) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE username = ?",
                [$username]
            );

            if ($user && password_verify($password, $user['password'])) {
                $this->session->set('user_id', $user['user_id']);
                $this->session->set('username', $user['username']);
                $this->session->set('role', $user['role']);
                
                // Update last login
                $this->db->update(
                    'users',
                    ['last_login' => date(DATETIME_FORMAT)],
                    'user_id = ?',
                    [$user['user_id']]
                );

                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        $this->session->destroy();
    }

    public function isLoggedIn() {
        return $this->session->has('user_id');
    }

    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            return $this->db->fetch(
                "SELECT * FROM users WHERE user_id = ?",
                [$this->session->get('user_id')]
            );
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        try {
            $role = $this->session->get('role');
            $result = $this->db->fetch(
                "SELECT p.permission_name 
                 FROM permissions p 
                 JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                 JOIN roles r ON rp.role_id = r.role_id 
                 WHERE r.role_name = ? AND p.permission_name = ?",
                [$role, $permission]
            );

            return !empty($result);
        } catch (Exception $e) {
            error_log("Permission check error: " . $e->getMessage());
            return false;
        }
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public function requirePermission($permission) {
        $this->requireLogin();
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.0 403 Forbidden');
            die('Access Denied');
        }
    }
}

class Session {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function has($key) {
        return isset($_SESSION[$key]);
    }

    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public function destroy() {
        session_destroy();
    }

    public function setFlash($key, $value) {
        $_SESSION['flash'][$key] = $value;
    }

    public function getFlash($key, $default = null) {
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);

        return $value;
    }
}

