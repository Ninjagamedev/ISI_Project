<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'online_shop');

// Application configuration
define('BASE_URL', 'http://localhost/online_shopping_system/');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('DEFAULT_LANGUAGE', 'en');

// Security configuration
define('CSRF_TOKEN_SECRET', 'your-secret-key');
define('JWT_SECRET', 'your-jwt-secret');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Hong_Kong');

// Autoload classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
