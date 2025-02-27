<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/OrderManager.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$order_manager = new OrderManager();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$filters = [
    'status' => $status,
    'date_from' => $date_from,
    'date_to' => $date_to
];

$orders = $order_manager->getUserOrders($auth->getCurrentUser()['id'], $page, $per_page, $filters);
$total_orders = $order_manager->getUserOrderCount($auth->getCurrentUser()['id'], $filters);
$total_pages = ceil($total_orders / $per_page);
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Online Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/orders.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="orders-page">
            <h1>My Orders</h1>

            <!-- Order Filters -->
            <div class="order-filters">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="status">Order Status</label>
                        <select name="status" id="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="orders.php" class="btn btn-secondary">Clear Filters</a>
                </form>
            </div>

            <!-- Orders List -->
            <?php if ($orders): ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                    <span class="order-date">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </div>
                            </div>

                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <div class="item-details">
                                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                                            <p>Price: $<?php echo number_format($item['unit_price'], 2); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="order-footer">
                                <div class="order-total">
                                    Total: $<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                                <div class="order-actions">
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-primary">View Details</a>
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <a href="write-review.php?order_id=<?php echo $order['id']; ?>" 
                                           class="btn btn-secondary">Write Review</a>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button class="btn btn-danger" 
                                                onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            Cancel Order
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
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                               class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/orders.js"></script>
</body>
</html>
