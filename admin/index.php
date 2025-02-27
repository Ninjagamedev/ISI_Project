<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get dashboard statistics
$db = Database::getInstance()->getConnection();

// Total orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch()['total'];

// Total users
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$total_users = $stmt->fetch()['total'];

// Total products
$stmt = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $stmt->fetch()['total'];

// Total revenue
$stmt = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$total_revenue = $stmt->fetch()['total'];

// Recent orders
$stmt = $db->query("
    SELECT o.*, u.full_name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $db->query("
    SELECT p.*, s.name as shop_name 
    FROM products p 
    JOIN shops s ON p.shop_id = s.id 
    WHERE p.stock_quantity <= p.low_stock_threshold 
    ORDER BY p.stock_quantity ASC 
    LIMIT 5
");
$low_stock_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Shop</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="admin-layout">
        <?php include 'templates/sidebar.php'; ?>

        <main class="admin-main">
            <div class="dashboard">
                <h1>Dashboard</h1>

                <!-- Statistics Cards -->
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-content">
                            <h3>Total Orders</h3>
                            <p><?php echo number_format($total_orders); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3>Total Users</h3>
                            <p><?php echo number_format($total_users); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🛍️</div>
                        <div class="stat-content">
                            <h3>Total Products</h3>
                            <p><?php echo number_format($total_products); ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3>Total Revenue</h3>
                            <p>$<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <section class="dashboard-section">
                    <h2>Recent Orders</h2>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-small">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="orders.php" class="btn btn-primary">View All Orders</a>
                </section>

                <!-- Low Stock Products -->
                <section class="dashboard-section">
                    <h2>Low Stock Products</h2>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Shop</th>
                                    <th>Stock</th>
                                    <th>Threshold</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['shop_name']); ?></td>
                                        <td>
                                            <span class="stock-warning">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product['low_stock_threshold']; ?></td>
                                        <td>
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-small">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="products.php?filter=low_stock" class="btn btn-primary">View All Low Stock Products</a>
                </section>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
