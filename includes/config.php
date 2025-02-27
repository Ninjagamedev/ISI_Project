<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'online_shop');

// Base URL - Update this according to your setup
define('BASE_URL', 'http://localhost/online%20shopping%20system/');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone setting
date_default_timezone_set('Asia/Hong_Kong');
