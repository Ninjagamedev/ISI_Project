<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/ProductManager.php';

$auth = Auth::getInstance();
$product_manager = new ProductManager();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $product_manager->getProduct($product_id);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Get product images
$images = $product_manager->getProductImages($product_id);

// Get product options (if configurable)
$options = $product_manager->getProductOptions($product_id);

// Get related products
$related_products = $product_manager->getRelatedProducts($product_id, 4);

// Get product reviews
$reviews = $product_manager->getProductReviews($product_id);
$avg_rating = $product_manager->getAverageRating($product_id);
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Online Shop</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['meta_description'] ?? $product['description']); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/product.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <main class="container">
        <div class="product-detail">
            <!-- Product Images -->
            <div class="product-gallery">
                <div class="main-image">
                    <img id="main-product-image" 
                         src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-list">
                        <?php foreach ($images as $image): ?>
                            <div class="thumbnail" onclick="updateMainImage('<?php echo htmlspecialchars($image['image_path']); ?>')">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta">
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                        <span class="review-count">(<?php echo count($reviews); ?> reviews)</span>
                    </div>
                    <div class="shop-info">
                        Sold by: <a href="shop.php?id=<?php echo $product['shop_id']; ?>">
                            <?php echo htmlspecialchars($product['shop_name']); ?>
                        </a>
                    </div>
                </div>

                <div class="price-box">
                    <?php if ($product['sale_price']): ?>
                        <span class="original-price">$<?php echo number_format($product['price'], 2); ?></span>
                        <span class="sale-price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                    <?php else: ?>
                        <span class="regular-price">$<?php echo number_format($product['price'], 2); ?></span>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <form id="add-to-cart-form" class="product-options">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    
                    <?php if ($options): ?>
                        <?php foreach ($options as $option): ?>
                            <div class="option-group">
                                <label><?php echo htmlspecialchars($option['name']); ?>:</label>
                                <select name="options[<?php echo $option['id']; ?>]" required>
                                    <option value="">Select <?php echo htmlspecialchars($option['name']); ?></option>
                                    <?php foreach ($option['values'] as $value): ?>
                                        <option value="<?php echo $value['id']; ?>"
                                                data-price="<?php echo $value['price_adjustment']; ?>">
                                            <?php echo htmlspecialchars($value['value']); ?>
                                            <?php if ($value['price_adjustment']): ?>
                                                (+$<?php echo number_format($value['price_adjustment'], 2); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="quantity-selector">
                        <label>Quantity:</label>
                        <div class="quantity-controls">
                            <button type="button" onclick="updateQuantity(-1)">-</button>
                            <input type="number" name="quantity" value="1" min="1" 
                                   max="<?php echo $product['stock_quantity']; ?>">
                            <button type="button" onclick="updateQuantity(1)">+</button>
                        </div>
                        <span class="stock-info">
                            <?php echo $product['stock_quantity']; ?> items available
                        </span>
                    </div>

                    <div class="product-actions">
                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                        <?php if ($auth->isLoggedIn()): ?>
                            <button type="button" class="btn btn-secondary add-to-wishlist" 
                                    data-product-id="<?php echo $product_id; ?>">
                                Add to Wishlist
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Product Reviews -->
        <section class="product-reviews">
            <h2>Customer Reviews</h2>
            
            <?php if ($auth->isLoggedIn()): ?>
                <button class="btn btn-secondary" onclick="showReviewForm()">Write a Review</button>
                
                <form id="review-form" style="display: none;" class="review-form">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <div class="rating-input">
                        <label>Your Rating:</label>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                            <label class="star">★</label>
                        <?php endfor; ?>
                    </div>
                    <div class="form-group">
                        <label>Your Review:</label>
                        <textarea name="review" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            <?php endif; ?>

            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="review-meta">
                                by <?php echo htmlspecialchars($review['user_name']); ?>
                                on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Related Products -->
        <?php if ($related_products): ?>
            <section class="related-products">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($related['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                            </div>
                            <div class="product-info">
                                <h3>
                                    <a href="product.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                    </a>
                                </h3>
                                <p class="price">$<?php echo number_format($related['price'], 2); ?></p>
                                <a href="product.php?id=<?php echo $related['id']; ?>" 
                                   class="btn btn-secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'templates/footer.php'; ?>

    <script src="assets/js/product.js"></script>
</body>
</html>
