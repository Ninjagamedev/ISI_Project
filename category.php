<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/ProductManager.php';

$auth = Auth::getInstance();
$product_manager = new ProductManager();

$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$category_id) {
    header('Location: index.php');
    exit;
}

// Get category details
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}

// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Filtering and sorting options
$sort = filter_input(INPUT_GET, 'sort') ?: 'newest';
$min_price = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$shop_id = filter_input(INPUT_GET, 'shop_id', FILTER_VALIDATE_INT);

// Get products
$filters = [
    'category_id' => $category_id,
    'min_price' => $min_price,
    'max_price' => $max_price,
    'shop_id' => $shop_id,
    'sort' => $sort,
    'offset' => $offset,
    'limit' => $items_per_page
];

$products = $product_manager->getProducts($filters);
$total_products = $product_manager->getProductsCount($filters);
$total_pages = ceil($total_products / $items_per_page);

// Get available shops in this category
$stmt = $db->prepare("
    SELECT DISTINCT s.id, s.name, COUNT(p.id) as product_count
    FROM shops s
    JOIN products p ON s.id = p.shop_id
    WHERE p.category_id = ? AND p.status = 'active'
    GROUP BY s.id
    ORDER BY product_count DESC
");
$stmt->execute([$category_id]);
$shops = $stmt->fetchAll();
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

        <div class="category-content">
            <!-- Filters Sidebar -->
            <aside class="filters">
                <form action="" method="GET" id="filter-form">
                    <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    
                    <div class="filter-section">
                        <h3>Price Range</h3>
                        <div class="price-range">
                            <input type="number" name="min_price" 
                                   value="<?php echo $min_price; ?>" 
                                   placeholder="Min" step="0.01">
                            <span>to</span>
                            <input type="number" name="max_price" 
                                   value="<?php echo $max_price; ?>" 
                                   placeholder="Max" step="0.01">
                        </div>
                    </div>

                    <?php if (!empty($shops)): ?>
                        <div class="filter-section">
                            <h3>Shops</h3>
                            <?php foreach ($shops as $s): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="shop_id[]" 
                                           value="<?php echo $s['id']; ?>"
                                           <?php echo $shop_id == $s['id'] ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                    (<?php echo $s['product_count']; ?>)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </form>
            </aside>

            <!-- Products Grid -->
            <div class="products-section">
                <div class="products-header">
                    <div class="product-count">
                        <?php echo $total_products; ?> products found
                    </div>
                    <div class="sort-options">
                        <select name="sort" onchange="updateSort(this.value)">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                                Newest First
                            </option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>
                                Price: Low to High
                            </option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>
                                Price: High to Low
                            </option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>
                                Most Popular
                            </option>
                        </select>
                    </div>
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
                                <div
