<?php
require_once 'includes/auth_check.php';
require_once 'includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Handle profile picture upload if present
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_result = uploadImage($_FILES['profile_picture'], 'users');
        if (!$upload_result['success']) {
            echo json_encode($upload_result);
            exit;
        }
        $profile_picture = $upload_result['path'];
    }

    // Get form data
    $user_id = $_POST['user_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate required fields
    if (empty($name) || empty($email)) {
        throw new Exception('Name and email are required');
    }

    if ($user_id) {
        // Update existing user
        $sql = "UPDATE users SET 
                name = ?, 
                email = ?, 
                phone = ?, 
                role = ?, 
                is_active = ?" . 
                ($profile_picture ? ", profile_picture = ?" : "") . "
                WHERE user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $params = [$name, $email, $phone, $role, $is_active];
        if ($profile_picture) {
            $params[] = $profile_picture;
        }
        $params[] = $user_id;
        
        $stmt->execute($params);
        $response['message'] = 'User updated successfully';
    } else {
        // Create new user
        $password = $_POST['password'] ?? '';
        if (empty($password)) {
            throw new Exception('Password is required for new users');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, email, phone, password, role, is_active" . 
               ($profile_picture ? ", profile_picture" : "") . ") 
                VALUES (?, ?, ?, ?, ?, ?" . 
               ($profile_picture ? ", ?" : "") . ")";
        
        $stmt = $conn->prepare($sql);
        $params = [$name, $email, $phone, $hashed_password, $role, $is_active];
        if ($profile_picture) {
            $params[] = $profile_picture;
        }
        
        $stmt->execute($params);
        $response['message'] = 'User created successfully';
    }

    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logEvent('error', 'User processing error: ' . $e->getMessage());
}

echo json_encode($response); 