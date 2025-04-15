<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get user's role
$stmt = $pdo->prepare("
    SELECT r.role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.role_id 
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();
?>

<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h3>MbogaFasta CMS</h3>
        <button type="button" id="sidebarCollapse" class="btn btn-dark d-md-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <ul class="list-unstyled components">
            <?php if ($user_role === 'Admin'): ?>
                <!-- Admin Menu Items -->
                <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'restaurants.php' ? 'active' : ''; ?>">
                    <a href="restaurants.php">
                        <i class="fas fa-store"></i>
                        <span>Restaurants</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'managers.php' ? 'active' : ''; ?>">
                    <a href="managers.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Managers</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            <?php else: ?>
                <!-- Restaurant Manager Menu Items -->
                <li class="<?php echo $current_page == 'restaurant_dashboard.php' ? 'active' : ''; ?>">
                    <a href="restaurant_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'restaurant_products.php' ? 'active' : ''; ?>">
                    <a href="restaurant_products.php">
                        <i class="fas fa-utensils"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'restaurant_orders.php' ? 'active' : ''; ?>">
                    <a href="restaurant_orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'restaurant_customers.php' ? 'active' : ''; ?>">
                    <a href="restaurant_customers.php">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="<?php echo $current_page == 'restaurant_reports.php' ? 'active' : ''; ?>">
                    <a href="restaurant_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <ul class="list-unstyled components mt-auto">
            <li class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <a href="profile.php">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    min-width: 250px;
    max-width: 250px;
    background: #343a40;
    color: #fff;
    transition: all 0.3s;
    position: fixed;
    height: 100vh;
    z-index: 1000;
}

.sidebar-header {
    padding: 20px;
    background: #2c3136;
    text-align: center;
}

.sidebar-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.sidebar-content {
    padding: 20px 0;
    height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
}

.components {
    padding: 0;
}

.components li {
    padding: 0;
}

.components li a {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s;
}

.components li a:hover {
    background: #2c3136;
    color: #fff;
}

.components li.active a {
    background: #007bff;
    color: #fff;
}

.components li a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.components li a.text-danger {
    color: #dc3545;
}

.components li a.text-danger:hover {
    background: #dc3545;
    color: #fff;
}

@media (max-width: 768px) {
    .sidebar {
        margin-left: -250px;
    }
    .sidebar.active {
        margin-left: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const mainContent = document.getElementById('mainContent');

    sidebarCollapse.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('active');
    });
});
</script> 