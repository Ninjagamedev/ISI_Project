<?php
require_once 'config.php';
require_once 'database.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_product_id() {
    return 'PRD' . date('Ymd') . rand(1000, 9999);
}

function generate_order_number() {
    return 'ORD' . date('Ymd') . rand(1000, 9999);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function get_cart_count() {
    if (!is_logged_in()) return 0;
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn();
}

function format_price($price) {
    return number_format($price, 2);
}
