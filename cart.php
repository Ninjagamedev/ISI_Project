<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $cart_item_id = $_POST['cart_item_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) {
        // Remove item if quantity is 0 or negative
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_item_id, $_SESSION['user_id']]);
    } else {
        // Update quantity
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_item_id, $_SESSION['user_id']]);
    }
    $success_message = "Cart updated successfully!";
}

// Handle remove item
if (isset($_GET['remove'])) {
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['remove'], $_SESSION['user_id']]);
    $success_message = "Item removed from cart!";
}

// Handle checkout
if (isset($_POST['checkout'])) {
    try {
        $conn->beginTransaction();

        // Get cart items
        $stmt = $conn->prepare("
            SELECT ci.*, p.price, p.name 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll();

        if (!empty($cart_items)) {
            // Get user's shipping address
            $stmt = $conn->prepare("SELECT shipping_address FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            // Calculate total
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            // Create order
            $order_number = generate_order_number();
            $stmt = $conn->prepare("
                INSERT INTO orders (order_number, user_id, total_amount, shipping_address) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_number, $_SESSION['user_id'], $total_amount, $user['shipping_address']]);
            $order_id = $conn->lastInsertId();

            // Create order items
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $conn->commit();
            redirect('orders.php?success=1');
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Checkout failed. Please try again.";
    }
}

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.thumbnail 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Online Shopping System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Shopping Cart</h1>

        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <p>Your cart is empty.</p>
            <a href="index.php" class="btn">Continue Shopping</a>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo $item['thumbnail']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="price">$<?php echo format_price($item['price']); ?></p>
                            <form method="POST" action="" class="quantity-form">
                                <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0">
                                <button type="submit" name="update_quantity">Update</button>
                            </form>
                            <p class="subtotal">Subtotal: $<?php echo format_price($item['price'] * $item['quantity']); ?></p>
                            <a href="?remove=<?php echo $item['id']; ?>" class="remove-btn">Remove</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3>Cart Total: $<?php echo format_price($total); ?></h3>
                <form method="POST" action="">
                    <button type="submit" name="checkout" class="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
