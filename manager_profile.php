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

    // Get manager's information
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as restaurant_name
        FROM users u
        LEFT JOIN restaurants r ON u.user_id = r.manager_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $manager = $stmt->fetch();

    if (!$manager) {
        throw new Exception("Manager not found");
    }

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
                    <h4 class="mb-0">Manager Profile</h4>
                    <div>
                        <a href="restaurant_dashboard.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php else: ?>
                        <div class="row">
                            <!-- Profile Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Personal Information</h5>
                                        <form id="managerForm" class="needs-validation" novalidate>
                                            <input type="hidden" name="user_id" value="<?php echo $manager['user_id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" 
                                                       value="<?php echo htmlspecialchars($manager['name']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($manager['email']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" name="phone_number" 
                                                       value="<?php echo htmlspecialchars($manager['phone_number']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Current Password</label>
                                                <input type="password" class="form-control" name="current_password">
                                                <small class="text-muted">Leave blank if you don't want to change your password</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">New Password</label>
                                                <input type="password" class="form-control" name="new_password">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" name="confirm_password">
                                            </div>

                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Restaurant Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Restaurant Information</h5>
                                        <?php if ($manager['restaurant_name']): ?>
                                            <p><strong>Restaurant:</strong> <?php echo htmlspecialchars($manager['restaurant_name']); ?></p>
                                            <a href="restaurant_profile.php" class="btn btn-primary">
                                                <i class="fas fa-store"></i> View Restaurant Profile
                                            </a>
                                        <?php else: ?>
                                            <p class="text-muted">No restaurant assigned yet.</p>
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

<script>
document.getElementById('managerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate password fields
    const newPassword = this.querySelector('input[name="new_password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
    
    if (newPassword && newPassword !== confirmPassword) {
        alert('New passwords do not match');
        return;
    }
    
    // Submit form data
    const formData = new FormData(this);
    
    fetch('process_manager_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully');
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            alert(data.message || 'Error updating profile');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the profile');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 