<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Check if user is a restaurant manager
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header('Location: dashboard.php');
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Get manager's restaurant
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as manager_name, u.email as manager_email, u.phone_number as manager_phone
        FROM restaurants r 
        LEFT JOIN users u ON r.manager_id = u.user_id
        WHERE r.manager_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $restaurant = $stmt->fetch();

    if (!$restaurant) {
        throw new Exception("No restaurant found for this manager");
    }

    // Get restaurant images
    $stmt = $pdo->prepare("SELECT * FROM restaurant_images WHERE restaurant_id = ?");
    $stmt->execute([$restaurant['restaurant_id']]);
    $images = $stmt->fetchAll();

    // Get restaurant reviews
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.user_id 
        WHERE r.restaurant_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$restaurant['restaurant_id']]);
    $reviews = $stmt->fetchAll();

    // Calculate average rating
    $total_rating = 0;
    $review_count = count($reviews);
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $average_rating = $review_count > 0 ? $total_rating / $review_count : 0;

} catch (Exception $e) {
    $error = $e->getMessage();
}

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Restaurant Profile</h4>
                    <div>
                        <a href="restaurant_dashboard.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                            <i class="fas fa-image"></i> Upload Image
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php else: ?>
                        <div class="row">
                            <!-- Restaurant Images -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Restaurant Images</h5>
                                        <div class="row">
                                            <?php foreach ($images as $image): ?>
                                                <div class="col-6 mb-3 position-relative">
                                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                         class="img-fluid rounded" 
                                                         alt="Restaurant Image">
                                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" 
                                                            onclick="deleteImage(<?php echo $image['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Restaurant Details -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Restaurant Information</h5>
                                        <form id="restaurantForm" class="needs-validation" novalidate>
                                            <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['restaurant_id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" 
                                                       value="<?php echo htmlspecialchars($restaurant['name']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3"><?php 
                                                    echo htmlspecialchars($restaurant['description']); 
                                                ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="address" 
                                                       value="<?php echo htmlspecialchars($restaurant['address']); ?>" required>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Latitude <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="latitude" step="any"
                                                           value="<?php echo htmlspecialchars($restaurant['latitude']); ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Longitude <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="longitude" step="any"
                                                           value="<?php echo htmlspecialchars($restaurant['longitude']); ?>" required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Rating</label>
                                                <div class="d-flex align-items-center">
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $average_rating ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="ms-2">(<?php echo $review_count; ?> reviews)</span>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Reviews Section -->
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Customer Reviews</h5>
                                        <?php if (empty($reviews)): ?>
                                            <p class="text-muted">No reviews yet.</p>
                                        <?php else: ?>
                                            <?php foreach ($reviews as $review): ?>
                                                <div class="border-bottom pb-3 mb-3">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                                    </div>
                                                    <div class="rating mb-2">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Restaurant Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadImageForm" enctype="multipart/form-data">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['restaurant_id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*" required>
                        <div class="form-text">Supported formats: JPG, PNG, GIF. Max size: 5MB</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="uploadImage()">Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('restaurantForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }

    const formData = new FormData(this);
    
    fetch('process_restaurant.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the changes');
    });
});

// Image upload
function uploadImage() {
    const form = document.getElementById('uploadImageForm');
    const formData = new FormData(form);
    
    fetch('process_restaurant.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while uploading the image');
    });
}

// Delete image
function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        fetch('process_restaurant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_image',
                image_id: imageId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the image');
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 