<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/CartManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$cart_manager = new CartManager($auth->getCurrentUser()['id']);
$cart_items = $cart_manager->getItems();
$cart_summary = $cart_manager->getCartSummary();

// Handle POST requests for cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $cart_manager->updateQuantity($_POST['item_id'], $_POST['quantity']);
                break;
            case 'remove':
                $cart_manager->removeItem($_POST['item_id']);
                break;
            case 'apply_coupon':
                $cart_manager->applyCoupon($_POST['coupon_code']);
                break;
        }
        header('Location: cart.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <h1>Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3>
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h3>
                                <p class="shop-name">
                                    Sold by: <?php echo htmlspecialchars($item['shop_name']); ?>
                                </p>
                                <?php if (!empty($item['options'])): ?>
                                    <div class="item-options">
                                        <?php foreach ($item['options'] as $option): ?>
                                            <span class="option">
                                                <?php echo htmlspecialchars($option['name']); ?>: 
                                                <?php echo htmlspecialchars($option['value']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="item-price">
                                $<?php echo number_format($item['price'], 2); ?>
                            </div>
                            <div class="item-quantity">
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           onchange="this.form.submit()">
                                    <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1)">+</button>
                                </form>
                            </div>
                            <div class="item-total">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                            <div class="item-actions">
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($cart_summary['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($cart_summary['discount']): ?>
                        <div class="summary-row discount">
                            <span>Discount</span>
                            <span>-$<?php echo number_format($cart_summary['discount'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>$<?php echo number_format($cart_summary['shipping'], 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($cart_summary['total'], 2); ?></span>
                    </div>

                    <!-- Coupon Code -->
                    <form method="POST" class="coupon-form">
                        <input type="hidden" name="action" value="apply_coupon">
                        <div class="form-group">
                            <input type="text" name="coupon_code" placeholder="Enter coupon code">
                            <button type="submit" class="btn btn-secondary">Apply</button>
                        </div>
                    </form>

                    <a href="checkout.php" class="btn btn-primary checkout-btn">
                        Proceed to Checkout
                    </a>
                    <a href="index.php" class="btn btn-secondary continue-shopping">
                        Continue Shopping
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/cart.js"></script>
</body>
</html>
