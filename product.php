<?php
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/ProductManager.php';

$auth = Auth::getInstance();
$product_manager = new ProductManager();

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    header('Location: index.php');
    exit;
}

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
$related_products = $product_manager->getRelatedProducts($product_id);

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
    <meta name="description" content="<?php echo htmlspecialchars($product['meta_description']); ?>">
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
                    <img src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         id="main-product-image">
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-list">
                        <?php foreach ($images as $image): ?>
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>"
                                 alt="Product thumbnail"
                                 onclick="updateMainImage(this.src)"
                                 class="thumbnail">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="shop-info">
                    <a href="shop.php?id=<?php echo $product['shop_id']; ?>">
                        <?php echo htmlspecialchars($product['shop_name']); ?>
                    </a>
                </div>

                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">★</span>
                    <?php endfor; ?>
                    <span class="review-count">(<?php echo count($reviews); ?> reviews)</span>
                </div>

                <div class="price">
                    $<?php echo number_format($product['price'], 2); ?>
                </div>

                <?php if ($product['sale_price']): ?>
                    <div class="sale-price">
                        Sale: $<?php echo number_format($product['sale_price'], 2); ?>
                    </div>
                <?php endif; ?>

                <form id="add-to-cart-form" action="cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <?php if (!empty($options)): ?>
                        <?php foreach ($options as $option): ?>
                            <div class="product-option">
                                <label for="option-<?php echo $option['id']; ?>">
                                    <?php echo htmlspecialchars($option['name']); ?>:
                                </label>
                                <select name="options[<?php echo $option['id']; ?>]" 
                                        id="option-<?php echo $option['id']; ?>"
                                        required>
                                    <option value="">Select <?php echo htmlspecialchars($option['name']); ?></option>
                                    <?php foreach ($option['values'] as $value): ?>
                                        <option value="<?php echo $value['id']; ?>"
                                                data-stock="<?php echo $value['stock_quantity']; ?>">
                                            <?php echo htmlspecialchars($value['value']); ?>
                                            <?php if ($value['price_adjustment'] > 0): ?>
                                                (+$<?php echo number_format($value['price_adjustment'], 2); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="quantity">
                        <label for="quantity">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" 
                               value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                    </div>

                    <div class="product-actions">
                        <button type="submit" class="btn btn-primary"
                                <?php echo $product['stock_quantity'] < 1 ? 'disabled' : ''; ?>>
                            <?php echo $product['stock_quantity'] < 1 ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
                        <?php if ($auth->isLoggedIn()): ?>
                            <button type="button" class="btn btn-secondary add-to-wishlist"
                                    data-product-id="<?php echo $product['id']; ?>">
                                Add to Wishlist
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="product-description">
                    <h2>Product Description</h2>
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <!-- Product Specifications -->
                <?php if (!empty($product['specifications'])): ?>
                    <div class="product-specifications">
                        <h2>Specifications</h2>
                        <table>
                            <?php foreach (json_decode($product['specifications'], true) as $spec): ?>
                                <tr>
                                    <th><?php echo htmlspecialchars($spec['name']); ?></th>
                                    <td><?php echo htmlspecialchars($spec['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews Section -->
        <section class="product-reviews">
            <h2>Customer Reviews</h2>
            
            <?php if ($auth->isLoggedIn()): ?>
                <button class="btn btn-secondary" onclick="showReviewForm()">
                    Write a Review
                </button>
                
                <form id="review-form" style="display: none;" action="submit-review.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="form-group">
                        <label for="rating">Rating:</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                       id="star<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="review">Your Review:</label>
                        <textarea name="review" id="review" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            <?php endif; ?>

            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <div class="review-header">
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">
                                        ★
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <span class="reviewer">
                                <?php echo htmlspecialchars($review['user_name']); ?>
                            </span>
                            <span class="review-date">
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </span>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
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
