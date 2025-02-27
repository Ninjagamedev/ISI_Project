<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/CartManager.php';
require_once 'includes/OrderManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$cart_manager = new CartManager($auth->getCurrentUser()['id']);
$cart_items = $cart_manager->getItems();
$cart_summary = $cart_manager->getCartSummary();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $order_manager = new OrderManager();
        
        // Create the order(s)
        $order_ids = $order_manager->createOrder(
            $auth->getCurrentUser()['id'],
            $cart_items,
            [
                'shipping_address' => $_POST['shipping_address'],
                'billing_address' => $_POST['billing_address'],
                'payment_method' => $_POST['payment_method'],
                'notes' => $_POST['order_notes']
            ]
        );

        // Clear the cart
        $cart_manager->clear();

        // Redirect to order confirmation
        header('Location: order-confirmation.php?order_id=' . implode(',', $order_ids));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's saved addresses
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmt->execute([$auth->getCurrentUser()['id']]);
$saved_addresses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <h1>Checkout</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="checkout-form" class="checkout-layout">
            <!-- Customer Information -->
            <div class="checkout-section">
                <div class="shipping-address">
                    <h2>Shipping Address</h2>
                    <?php if ($saved_addresses): ?>
                        <div class="saved-addresses">
                            <label>Select a saved address:</label>
                            <select name="saved_shipping_address" onchange="fillShippingAddress(this.value)">
                                <option value="">-- Select Address --</option>
                                <?php foreach ($saved_addresses as $address): ?>
                                    <option value="<?php echo htmlspecialchars(json_encode($address)); ?>">
                                        <?php echo htmlspecialchars($address['address_line1']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="shipping_fullname">Full Name</label>
                        <input type="text" name="shipping_fullname" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_address">Address Line 1</label>
                        <input type="text" name="shipping_address[line1]" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_address2">Address Line 2</label>
                        <input type="text" name="shipping_address[line2]">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_city">City</label>
                            <input type="text" name="shipping_address[city]" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_state">State</label>
                            <input type="text" name="shipping_address[state]" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_postal">Postal Code</label>
                            <input type="text" name="shipping_address[postal]" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="shipping_phone">Phone Number</label>
                        <input type="tel" name="shipping_phone" required>
                    </div>
                </div>

                <div class="billing-address">
                    <h2>Billing Address</h2>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="same_as_shipping" checked 
                                   onchange="toggleBillingAddress(this.checked)">
                            Same as shipping address
                        </label>
                    </div>
                    <div id="billing-address-fields" style="display: none;">
                        <!-- Billing address fields (similar to shipping) -->
                    </div>
                </div>
            </div>

            <!-- Order Review -->
            <div class="checkout-section">
                <h2>Order Review</h2>
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="shop-name">
                                    Sold by: <?php echo htmlspecialchars($item['shop_name']); ?>
                                </p>
                                <p class="quantity">
                                    Quantity: <?php echo $item['quantity']; ?>
                                </p>
                                <p class="price">
                                    $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($cart_summary['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($cart_summary['discount']): ?>
                        <div class="summary-row">
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
                </div>
            </div>

            <!-- Payment Information -->
            <div class="checkout-section">
                <h2>Payment Method</h2>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" name="payment_method" value="credit_card" id="credit_card" required>
                        <label for="credit_card">Credit Card</label>
                        <div class="payment-details" id="credit-card-fields">
                            <div class="form-group">
                                <label>Card Number</label>
                                <input type="text" name="card_number" pattern="\d{16}" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input type="text" name="card_expiry" pattern="\d{2}/\d{2}" placeholder="MM/YY">
                                </div>
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input type="text" name="card_cvv" pattern="\d{3,4}" placeholder="123">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-method">
                        <input type="radio" name="payment_method" value="paypal" id="paypal">
                        <label for="paypal">PayPal</label>
                    </div>
                </div>
            </div>

            <!-- Order Notes -->
            <div class="checkout-section">
                <h2>Order Notes</h2>
                <div class="form-group">
                    <textarea name="order_notes" placeholder="Special instructions for delivery"></textarea>
                </div>
            </div>

            <!-- Place Order Button -->
            <div class="checkout-actions">
                <button type="submit" class="btn btn-primary">Place Order</button>
                <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
            </div>
        </form>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/checkout.js"></script>
</body>
</html>
