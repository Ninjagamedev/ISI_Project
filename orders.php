<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get user's orders
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Online Shopping System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>My Orders</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">Order placed successfully!</div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p>You haven't placed any orders yet.</p>
            <a href="index.php" class="btn">Start Shopping</a>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                            <p class="order-date">Placed on: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            <p class="order-status">Status: <?php echo ucfirst($order['status']); ?></p>
                        </div>
                        
                        <?php
                        // Get order items
                        $stmt = $conn->prepare("
                            SELECT oi.*, p.name 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?
                        ");
                        $stmt->execute([$order['id']]);
                        $items = $stmt->fetchAll();
                        ?>

                        <div class="order-items">
                            <?php foreach ($items as $item): ?>
                                <div class="order-item">
                                    <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                    <span class="item-price">$<?php echo format_price($item['unit_price']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <p class="order-total">Total: $<?php echo format_price($order['total_amount']); ?></p>
                            <p class="shipping-address">Shipping to: <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
