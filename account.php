<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/OrderManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user = $auth->getCurrentUser();
$order_manager = new OrderManager();
$recent_orders = $order_manager->getUserOrders($user['id'], 1, 5);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_profile':
            $auth->updateProfile([
                'full_name' => $_POST['full_name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone']
            ]);
            $success_message = 'Profile updated successfully!';
            break;

        case 'change_password':
            try {
                $auth->changePassword(
                    $_POST['current_password'],
                    $_POST['new_password'],
                    $_POST['confirm_password']
                );
                $success_message = 'Password changed successfully!';
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/account.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="account-layout">
            <!-- Sidebar Navigation -->
            <aside class="account-nav">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if ($user['avatar_path']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                </div>

                <nav>
                    <a href="#dashboard" class="active">Dashboard</a>
                    <a href="#orders">My Orders</a>
                    <a href="#profile">Profile Settings</a>
                    <a href="#addresses">Addresses</a>
                    <a href="#wishlist">Wishlist</a>
                    <a href="#notifications">Notifications</a>
                    <?php if ($user['is_vendor']): ?>
                        <a href="vendor/dashboard.php">Vendor Dashboard</a>
                    <?php endif; ?>
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="account-content">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- Dashboard Overview -->
                <section id="dashboard" class="dashboard-section">
                    <h1>My Dashboard</h1>
                    
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h3>Total Orders</h3>
                            <p><?php echo $order_manager->getUserOrderCount($user['id']); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Wishlist Items</h3>
                            <p><?php echo $order_manager->getUserWishlistCount($user['id']); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Reviews</h3>
                            <p><?php echo $order_manager->getUserReviewCount($user['id']); ?></p>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="recent-orders">
                        <h2>Recent Orders</h2>
                        <?php if ($recent_orders): ?>
                            <div class="order-list">
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                            <span class="order-date">
                                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="order-status">
                                            Status: <span class="status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                        <div class="order-total">
                                            Total: $<?php echo number_format($order['total_amount'], 2); ?>
                                        </div>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-secondary">View Details</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="#orders" class="btn btn-primary">View All Orders</a>
                        <?php else: ?>
                            <p>No orders yet.</p>
                            <a href="index.php" class="btn btn-primary">Start Shopping</a>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Profile Settings -->
                <section id="profile" class="profile-section" style="display: none;">
                    <h2>Profile Settings</h2>
                    
                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>

                    <h3>Change Password</h3>
                    <form method="POST" class="password-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/account.js"></script>
</body>
</html>
