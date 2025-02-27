<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/ProductManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$product_manager = new ProductManager();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'status':
            $product_manager->updateStatus($_POST['product_id'], $_POST['status']);
            break;
        case 'delete':
            $product_manager->deleteProduct($_POST['product_id']);
            break;
    }
    header('Location: products.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$shop = isset($_GET['shop']) ? $_GET['shop'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$stock = isset($_GET['stock']) ? $_GET['stock'] : '';

// Build query
$query = "
    SELECT p.*, c.name as category_name, s.name as shop_name, 
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_path
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN shops s ON p.shop_id = s.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND p.category_id = ?";
    $params[] = $category;
}

if ($shop) {
    $query .= " AND p.shop_id = ?";
    $params[] = $shop;
}

if ($status) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

if ($stock === 'low') {
    $query .= " AND p.stock_quantity <= p.low_stock_threshold";
} elseif ($stock === 'out') {
    $query .= " AND p.stock_quantity = 0";
}

// Get total count
$count_stmt = $db->prepare(str_replace('SELECT p.*', 'SELECT COUNT(*)', $query));
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$query .= " ORDER BY p.created_at DESC LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories and shops for filters
$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$shops = $db->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="admin-layout">
        <?php include 'templates/sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-content">
                <div class="content-header">
                    <h1>Product Management</h1>
                    <a href="add-product.php" class="btn btn-primary">Add New Product</a>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="form-group">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="shop">
                                <option value="">All Shops</option>
                                <?php foreach ($shops as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" 
                                            <?php echo $shop == $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="stock">
                                <option value="">All Stock</option>
                                <option value="low" <?php echo $stock === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out" <?php echo $stock === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary">Apply Filters</button>
                        <a href="products.php" class="btn btn-link">Clear Filters</a>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Shop</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image_path']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-thumbnail">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['shop_name']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="<?php echo $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'stock-warning' : ''; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <t
