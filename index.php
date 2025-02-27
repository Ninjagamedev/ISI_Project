<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get featured products
$stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Search functionality
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_products = [];

if ($search_query) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active' AND name LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search_query%"]);
    $search_products = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Shopping System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="search-section">
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if ($search_query): ?>
            <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <div class="product-grid">
                <?php if (empty($search_products)): ?>
                    <p>No products found.</p>
                <?php else: ?>
                    <?php foreach ($search_products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo $product['thumbnail']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">$<?php echo format_price($product['price']); ?></p>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h2>Featured Products</h2>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo $product['thumbnail']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="price">$<?php echo format_price($product['price']); ?></p>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
