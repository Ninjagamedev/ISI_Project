<?php
class CartManager {
    private $db;
    private $user_id;

    public function __construct($user_id) {
        $this->db = Database::getInstance()->getConnection();
        $this->user_id = $user_id;
    }

    public function addItem($product_id, $quantity = 1, $inventory_id = null) {
        try {
            // Check if product exists and is active
            $stmt = $this->db->prepare("
                SELECT id, price FROM products 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Product not found or inactive");
            }

            // Check if item already in cart
            $stmt = $this->db->prepare("
                SELECT id, quantity FROM cart_items 
                WHERE user_id = ? AND product_id = ? AND inventory_id IS ?
            ");
            $stmt->execute([$this->user_id, $product_id, $inventory_id]);
            $cart_item = $stmt->fetch();

            if ($cart_item) {
                // Update quantity
                $stmt = $this->db->prepare("
                    UPDATE cart_items 
                    SET quantity = quantity + ? 
                    WHERE id = ?
                ");
                return $stmt->execute([$quantity, $cart_item['id']]);
            } else {
                // Add new item
                $stmt = $this->db->prepare("
                    INSERT INTO cart_items (user_id, product_id, inventory_id, quantity)
                    VALUES (?, ?, ?, ?)
                ");
                return $stmt->execute([$this->user_id, $product_id, $inventory_id, $quantity]);
            }
        } catch (Exception $e) {
            error_log("Error adding item to cart: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateQuantity($cart_item_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($cart_item_id);
        }

        $stmt = $this->db->prepare("
            UPDATE cart_items 
            SET quantity = ? 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$quantity, $cart_item_id, $this->user_id]);
    }

    public function removeItem($cart_item_id) {
        $stmt = $this->db->prepare("
            DELETE FROM cart_items 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$cart_item_id, $this->user_id]);
    }

    public function getItems() {
        $stmt = $this->db->prepare("
            SELECT ci.*, p.name, p.price, p.shop_id,
                   pi.image_path as product_image
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE ci.user_id = ?
            ORDER BY ci.created_at DESC
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll();
    }

    public function clear() {
        $stmt = $this->db->prepare("
            DELETE FROM cart_items 
            WHERE user_id = ?
        ");
        return $stmt->execute([$this->user_id]);
    }

    public function getTotal() {
        $stmt = $this->db->prepare("
            SELECT SUM(ci.quantity * p.price) as total
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch()['total'] ?? 0;
    }
}
