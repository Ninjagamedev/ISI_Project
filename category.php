<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/ProductManager.php';

$auth = Auth::getInstance();
$product_manager = new ProductManager();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Get category details
$stmt = Database::getInstance()->getConnection()->prepare("
    SELECT * FROM categories WHERE id = ?
");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

// Get filter parameters
$filters = [
    'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
    'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'newest',
    'shop_id' => isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null,
];

// Get products
$products = $product_manager->getCategoryProducts($category_id, $page, $per_page, $filters);
$total_products = $product_manager->getCategoryProductCount($category_id, $filters);
$total_pages = ceil($total_products / $per_page);
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/category.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="category-header">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <p><?php echo htmlspecialchars($category['description']); ?></p>
        </div>

        <div class="category-layout">
            <!-- Filters Sidebar -->
            <aside class="filters">
                <form id="filter-form" method="GET" action="">
                    <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    
                    <div class="filter-section">
                        <h3>Price Range</h3>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" 
                                   value="<?php echo $filters['min_price']; ?>">
                            <span>to</span>
                            <input type="number" name="max_price" placeholder="Max" 
                                   value="<?php echo $filters['max_price']; ?>">
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3>Sort By</h3>
                        <select name="sort">
                            <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>
                                Newest First
                            </option>
                            <option value="price_low" <?php echo $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>
                                Price: Low to High
                            </option>
                            <option value="price_high" <?php echo $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>
                                Price: High to Low
                            </option>
                            <option value="popular" <?php echo $filters['sort'] === 'popular' ? 'selected' : ''; ?>>
                                Most Popular
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </form>
            </aside>

            <!-- Products Grid -->
            <div class="products-section">
                <div class="products-header">
                    <p>Showing <?php echo ($page - 1) * $per_page + 1; ?>-<?php echo min($page * $per_page, $total_products); ?> 
                       of <?php echo $total_products; ?> products</p>
                </div>

                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                <p class="shop-name">
                                    <a href="shop.php?id=<?php echo $product['shop_id']; ?>">
                                        <?php echo htmlspecialchars($product['shop_name']); ?>
                                    </a>
                                </p>
                                <div class="price-box">
                                    <?php if ($product['sale_price']): ?>
                                        <span class="original-price">
                                            $<?php echo number_format($product['price'], 2); ?>
                                        </span>
                                        <span class="sale-price">
                                            $<?php echo number_format($product['sale_price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="regular-price">
                                            $<?php echo number_format($product['price'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-primary">View Details</a>
                                    <?php if ($auth->isLoggedIn()): ?>
                                        <button class="btn btn-secondary add-to-wishlist" 
                                                data-product-id="<?php echo $product['id']; ?>">
                                            ♡
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?id=<?php echo $category_id; ?>&page=<?php echo $page - 1; ?>" 
                               class="btn btn-secondary">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?id=<?php echo $category_id; ?>&page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?id=<?php echo $category_id; ?>&page=<?php echo $page + 1; ?>" 
                               class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/category.js"></script>
</body>
</html>
