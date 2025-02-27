<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$active_products = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetchColumn();

$stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'");
$total_sales = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Shopping System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container">
        <h1>Admin Dashboard</h1>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Active Products</h3>
                <p><?php echo $active_products; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p><?php echo $pending_orders; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Sales</h3>
                <p>$<?php echo format_price($total_sales); ?></p>
            </div>
        </div>

        <div class="quick-links">
            <h2>Quick Links</h2>
            <a href="products.php" class="btn">Manage Products</a>
            <a href="orders.php" class="btn">Process Orders</a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
