<?php
session_start();
require_once 'config/error.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/auth_check.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's role
$stmt = $pdo->prepare("
    SELECT r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();

// Check if user has access to restaurant management
if ($user_role !== 'Admin' && $user_role !== 'Manager') {
    $_SESSION['error'] = "You don't have permission to manage restaurants";
    header('Location: dashboard.php');
    exit();
}

// Function to handle file upload
function handleFileUpload($file, $restaurant_id = null) {
    $uploadDir = 'uploads/restaurants/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        logSystemEvent('directory_created', "Created directory: $uploadDir");
    }

    $fileName = ($restaurant_id ? $restaurant_id : uniqid()) . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        logSystemEvent('file_upload_failed', "Failed to upload file: {$file['name']}");
        throw new Exception("Failed to upload file.");
    }

    logSystemEvent('file_upload_success', "File uploaded successfully: $fileName");
    return $targetPath;
}

// Handle restaurant creation
if (isset($_POST['add_restaurant'])) {
    try {
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['address']) || empty($_POST['phone']) || empty($_POST['email'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate phone number format (basic validation)
        if (!preg_match('/^\+?[0-9]{10,15}$/', $_POST['phone'])) {
            throw new Exception("Invalid phone number format");
        }

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $upload_result = uploadImage($_FILES['image'], 'restaurants');
                
                if (!$upload_result['success']) {
                    throw new Exception($upload_result['message']);
                }
                
                // Use the full URL path for the image
                $image_url = $upload_result['path'];
                logSystemEvent('file_upload_success', "Image uploaded successfully: " . basename($image_url));
            } catch (Exception $e) {
                logSystemEvent('file_upload_error', "Image upload failed: " . $e->getMessage());
                throw new Exception("An error occurred while uploading the image: " . $e->getMessage());
            }
        }

        // Prepare SQL statement
        $sql = "INSERT INTO restaurants (
            name, description, address, phone, email, image_url,
            opening_time, closing_time, website, facebook_url, instagram_url,
            minimum_order_amount, delivery_fee, delivery_radius, is_active,
            created_at, updated_at
        ) VALUES (
            :name, :description, :address, :phone, :email, :image_url,
            :opening_time, :closing_time, :website, :facebook_url, :instagram_url,
            :minimum_order_amount, :delivery_fee, :delivery_radius, :is_active,
            NOW(), NOW()
        )";

        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':address', $_POST['address']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':opening_time', $_POST['opening_time']);
        $stmt->bindParam(':closing_time', $_POST['closing_time']);
        $stmt->bindParam(':website', $_POST['website']);
        $stmt->bindParam(':facebook_url', $_POST['facebook_url']);
        $stmt->bindParam(':instagram_url', $_POST['instagram_url']);
        $stmt->bindParam(':minimum_order_amount', $_POST['minimum_order_amount']);
        $stmt->bindParam(':delivery_fee', $_POST['delivery_fee']);
        $stmt->bindParam(':delivery_radius', $_POST['delivery_radius']);
        $stmt->bindParam(':is_active', $_POST['is_active'] ?? 0);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Restaurant added successfully";
        } else {
            throw new Exception("Failed to add restaurant");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: restaurants.php');
    exit();
}

// Handle restaurant update
if (isset($_POST['edit_restaurant'])) {
    try {
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['address']) || empty($_POST['phone']) || empty($_POST['email'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate phone number format (basic validation)
        if (!preg_match('/^\+?[0-9]{10,15}$/', $_POST['phone'])) {
            throw new Exception("Invalid phone number format");
        }

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $upload_result = uploadImage($_FILES['image'], 'restaurants');
                
                if (!$upload_result['success']) {
                    throw new Exception($upload_result['message']);
                }
                
                // Use the full URL path for the image
                $image_url = $upload_result['path'];
                logSystemEvent('file_upload_success', "Image uploaded successfully: " . basename($image_url));
            } catch (Exception $e) {
                logSystemEvent('file_upload_error', "Image upload failed: " . $e->getMessage());
                throw new Exception("An error occurred while uploading the image: " . $e->getMessage());
            }
        }

        // Prepare SQL statement
        $sql = "UPDATE restaurants SET 
            name = :name,
            description = :description,
            address = :address,
            phone = :phone,
            email = :email,
            opening_time = :opening_time,
            closing_time = :closing_time,
            website = :website,
            facebook_url = :facebook_url,
            instagram_url = :instagram_url,
            minimum_order_amount = :minimum_order_amount,
            delivery_fee = :delivery_fee,
            delivery_radius = :delivery_radius,
            is_active = :is_active,
            updated_at = NOW()";

        // Add image update if new image was uploaded
        if ($image_url) {
            $sql .= ", image_url = :image_url";
        }

        $sql .= " WHERE restaurant_id = :restaurant_id";

        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':address', $_POST['address']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':opening_time', $_POST['opening_time']);
        $stmt->bindParam(':closing_time', $_POST['closing_time']);
        $stmt->bindParam(':website', $_POST['website']);
        $stmt->bindParam(':facebook_url', $_POST['facebook_url']);
        $stmt->bindParam(':instagram_url', $_POST['instagram_url']);
        $stmt->bindParam(':minimum_order_amount', $_POST['minimum_order_amount']);
        $stmt->bindParam(':delivery_fee', $_POST['delivery_fee']);
        $stmt->bindParam(':delivery_radius', $_POST['delivery_radius']);
        $stmt->bindParam(':is_active', $_POST['is_active'] ?? 0);
        $stmt->bindParam(':restaurant_id', $_POST['restaurant_id']);

        if ($image_url) {
            $stmt->bindParam(':image_url', $image_url);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Restaurant updated successfully";
        } else {
            throw new Exception("Failed to update restaurant");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: restaurants.php');
    exit();
}

// Handle restaurant deletion
if (isset($_POST['delete_restaurant'])) {
    try {
        // Check if restaurant exists
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE restaurant_id = ?");
        $stmt->execute([$_POST['restaurant_id']]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            throw new Exception("Restaurant not found");
        }

        // Delete restaurant
        $stmt = $pdo->prepare("DELETE FROM restaurants WHERE restaurant_id = ?");
        if ($stmt->execute([$_POST['restaurant_id']])) {
            $_SESSION['success'] = "Restaurant deleted successfully";
        } else {
            throw new Exception("Failed to delete restaurant");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: restaurants.php');
    exit();
}

// Redirect back to restaurants page
header('Location: restaurants.php');
exit();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get form data
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $address = $_POST['address'] ?? '';
    $latitude = $_POST['latitude'] ?? 0;
    $longitude = $_POST['longitude'] ?? 0;
    $manager_id = $_POST['manager_id'] ?? null;

    // Validate required fields
    if (empty($name) || empty($description) || empty($address)) {
        throw new Exception('All fields are required');
    }

    // Validate manager_id if provided
    if ($manager_id) {
        $stmt = $pdo->prepare("SELECT role_id FROM users WHERE user_id = ?");
        $stmt->execute([$manager_id]);
        $user_role = $stmt->fetchColumn();
        
        if ($user_role != 3) {
            throw new Exception('Selected user is not a manager');
        }
    }

    // Handle image upload
    $logo_filename = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['logo'], 'restaurants');
        if (!$upload_result['success']) {
            echo json_encode($upload_result);
            exit;
        }
        $logo_filename = $upload_result['filename'];
    }

    // Check if we're updating an existing restaurant
    if (isset($_POST['restaurant_id'])) {
        $restaurant_id = $_POST['restaurant_id'];
        
        // Update restaurant
        $sql = "UPDATE restaurants SET 
                name = ?, 
                description = ?, 
                address = ?, 
                latitude = ?, 
                longitude = ?, 
                manager_id = ?";
        
        $params = [$name, $description, $address, $latitude, $longitude, $manager_id];
        
        if ($logo_filename) {
            $sql .= ", logo_filename = ?";
            $params[] = $logo_filename;
        }
        
        $sql .= " WHERE restaurant_id = ?";
        $params[] = $restaurant_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $response['message'] = 'Restaurant updated successfully';
    } else {
        // Create new restaurant
        if (!$logo_filename) {
            throw new Exception('Logo is required for new restaurants');
        }
        
        $sql = "INSERT INTO restaurants (name, description, address, latitude, longitude, logo_filename, manager_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $description, $address, $latitude, $longitude, $logo_filename, $manager_id]);
        
        $response['message'] = 'Restaurant created successfully';
    }

    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logEvent('error', 'Restaurant processing error: ' . $e->getMessage());
}

echo json_encode($response); 