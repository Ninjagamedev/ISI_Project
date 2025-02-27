<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle product status toggle
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE products SET status = CASE WHEN status = 'active' THEN 'disabled' ELSE 'active' END WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $success_message = "Product status updated successfully!";
}

// Handle new product submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = sanitize_input($_POST['name']);
    $price = floatval($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $product_id = generate_product_id();
    
    // Handle file upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['thumbnail']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_path = '../uploads/products/';
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path . $new_filename)) {
                $thumbnail = 'uploads/products/' . $new_filename;
            }
        }
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO products (product_id, name, price, description, thumbnail) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $name, $price, $description, $thumbnail]);
        $success_message = "Product added successfully!";
    } catch (PDOException $e) {
        $error_message = "Failed to add product. Please try again.";
    }
}

// Get all products
$stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container">
        <h1>Manage Products</h1>

        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="add-product-section">
            <h2>Add New Product</h2>
            <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Thumbnail Image:</label>
                    <input type="file" name="thumbnail" accept="image/*" required>
                </div>
                
                <button type="submit" name="add_product">Add Product</button>
            </form>
        </div>

        <div class="products-list">
            <h2>All Products</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                            <td>
                                <?php if ($product['thumbnail']): ?>
                                    <img src="<?php echo BASE_URL . $product['thumbnail']; ?>" alt="thumbnail" class="thumbnail-preview">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo format_price($product['price']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?toggle=1&id=<?php echo $product['id']; ?>" class="btn-small">
                                    <?php echo $product['status'] == 'active' ? 'Disable' : 'Enable'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
