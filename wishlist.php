<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/WishlistManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$wishlist_manager = new WishlistManager($auth->getCurrentUser()['id']);
$wishlist_items = $wishlist_manager->getItems();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remove':
                $wishlist_manager->removeItem($_POST['product_id']);
                break;
            case 'move_to_cart':
                $wishlist_manager->moveToCart($_POST['product_id']);
                break;
        }
        header('Location: wishlist.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/wishlist.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="wishlist-page">
            <h1>My Wishlist</h1>

            <?php if ($wishlist_items): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item">
                            <div class="item-image">
                                <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </a>
                            </div>
                            <div class="item-info">
                                <h3>
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h3>
                                <p class="shop-name">
                                    Sold by: <?php echo htmlspecialchars($item['shop_name']); ?>
                                </p>
                                <div class="price-box">
                                    <?php if ($item['sale_price']): ?>
                                        <span class="original-price">
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                        <span class="sale-price">
                                            $<?php echo number_format($item['sale_price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="regular-price">
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="stock-status">
                                    <?php if ($item['stock_quantity'] > 0): ?>
                                        <span class="in-stock">In Stock</span>
                                    <?php else: ?>
                                        <span class="out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-actions">
                                <?php if ($item['stock_quantity'] > 0): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="move_to_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-wishlist">
                    <p>Your wishlist is empty.</p>
                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/wishlist.js"></script>
</body>
</html>
