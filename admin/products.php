<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/ProductManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

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

// Filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$stock = isset($_GET['stock']) ? $_GET['stock'] : '';

$filters = [
    'search' => $search,
    'category_id' => $category_id,
    'shop_id' => $shop_id,
    'status' => $status,
    'stock' => $stock,
    'page' => $page,
    'per_page' => $per_page
];

$products = $product_manager->getProducts($filters);
$total_products = $product_manager->getProductCount($filters);
$total_pages = ceil($total_products / $per_page);

// Get categories and shops for filters
$db = Database::getInstance()->getConnection();
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$shops = $db->query("SELECT * FROM shops ORDER BY name")->fetchAll();
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
                            <select name="category_id">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id === $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="shop_id">
                                <option value="">All Shops</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?php echo $shop['id']; ?>" 
                                            <?php echo $shop_id === $shop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($shop['name']); ?>
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
                                <option value="in_stock" <?php echo $stock === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="low_stock" <?php echo $stock === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out_of_stock" <?php echo $stock === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
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
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Shop</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-thumbnail">
                                    </td>
                                    
