<header>
    <nav class="main-nav">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>">Online Shop</a>
        </div>
        <ul class="nav-links">
            <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>products.php">Products</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="<?php echo BASE_URL; ?>cart.php">Cart (<?php echo get_cart_count(); ?>)</a></li>
                <li><a href="<?php echo BASE_URL; ?>orders.php">My Orders</a></li>
                <li><a href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
