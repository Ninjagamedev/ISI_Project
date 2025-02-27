<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch();

if (!$product) {
    redirect('index.php');
}

$success_message = '';
$error_message = '';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }

    $quantity = (int)$_POST['quantity'];
    if ($quantity < 1) $quantity = 1;

    try {
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product['id']]);
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            // Update quantity
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $cart_item['id']]);
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product['id'], $quantity]);
        }
        $success_message = "Product added to cart successfully!";
    } catch (PDOException $e) {
        $error_message = "Failed to add product to cart. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Online Shopping System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="product-detail">
            <div class="product-image">
                <img src="<?php echo $product['thumbnail']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="price">$<?php echo format_price($product['price']); ?></p>
                <div class="description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <form method="POST" action="" class="add-to-cart-form">
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" name="quantity" value="1" min="1" required>
                    </div>
                    <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
