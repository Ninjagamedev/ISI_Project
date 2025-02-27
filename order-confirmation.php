<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/OrderManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$order_ids = explode(',', $_GET['order_id'] ?? '');
if (empty($order_ids)) {
    header('Location: account.php');
    exit;
}

$order_manager = new OrderManager();
$orders = [];
foreach ($order_ids as $order_id) {
    $order = $order_manager->getOrder($order_id, $auth->getCurrentUser()['id']);
    if ($order) {
        $orders[] = $order;
    }
}

if (empty($orders)) {
    header('Location: account.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/order-confirmation.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="order-confirmation">
            <div class="confirmation-header">
                <div class="success-icon">✓</div>
                <h1>Thank You for Your Order!</h1>
                <p>Your order has been successfully placed.</p>
            </div>

            <?php foreach ($orders as $order): ?>
                <div class="order-details">
                    <div class="order-header">
                        <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                        <div class="order-meta">
                            <span>Date: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            <span>Status: <?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                        </div>
                    </div>

                    <div class="shop-info">
                        <h3>Seller: <?php echo htmlspecialchars($order['shop_name']); ?></h3>
                    </div>

                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p class="quantity">Quantity: <?php echo $item['quantity']; ?></p>
                                    <p class="price">$<?php echo number_format($item['unit_price'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                        </div>
                        <?php if ($order['discount']): ?>
                            <div class="summary-row">
                                <span>Discount</span>
                                <span>-$<?php echo number_format($order['discount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>$<?php echo number_format($order['shipping_fee'], 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>

                    <div class="shipping-info">
                        <h3>Shipping Information</h3>
                        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="confirmation-actions">
                <a href="orders.php" class="btn btn-primary">View All Orders</a>
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
