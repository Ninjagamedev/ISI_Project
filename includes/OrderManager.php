<?php
class OrderManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createOrder($user_id, $cart_items) {
        try {
            $this->db->beginTransaction();

            // Group cart items by shop
            $shop_items = [];
            foreach ($cart_items as $item) {
                $shop_id = $item['shop_id'];
                if (!isset($shop_items[$shop_id])) {
                    $shop_items[$shop_id] = [];
                }
                $shop_items[$shop_id][] = $item;
            }

            $order_ids = [];

            // Create separate orders for each shop
            foreach ($shop_items as $shop_id => $items) {
                $order_number = $this->generateOrderNumber();
                $total_amount = 0;

                foreach ($items as $item) {
                    $total_amount += $item['price'] * $item['quantity'];
                }

                // Get user's shipping address
                $stmt = $this->db->prepare("SELECT shipping_address FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $shipping_address = $stmt->fetch()['shipping_address'];

                // Create order
                $stmt = $this->db->prepare("
                    INSERT INTO orders (
                        order_number, user_id, shop_id, 
                        total_amount, shipping_address, status
                    ) VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $order_number,
                    $user_id,
                    $shop_id,
                    $total_amount,
                    $shipping_address
                ]);

                $order_id = $this->db->lastInsertId();
                $order_ids[] = $order_id;

                // Create order items
                foreach ($items as $item) {
                    $stmt = $this->db->prepare("
                        INSERT INTO order_items (
                            order_id, product_id, inventory_id,
                            quantity, unit_price
                        ) VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['inventory_id'],
                        $item['quantity'],
                        $item['price']
                    ]);

                    // Update inventory if applicable
                    if ($item['inventory_id']) {
                        $stmt = $this->db->prepare("
                            UPDATE product_inventory 
                            SET stock_quantity = stock_quantity - ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$item['quantity'], $item['inventory_id']]);
                    }
                }
            }

            $this->db->commit();
            return $order_ids;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating order: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateOrderNumber() {
        return 'ORD' . date('Ymd') . rand(1000, 9999);
    }

    public function getOrder($order_id, $user_id = null) {
        $sql = "
            SELECT o.*, u.full_name as customer_name,
                   s.name as shop_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN shops s ON o.shop_id = s.id
            WHERE o.id = ?
        ";
        
        $params = [$order_id];
        
        if ($user_id) {
            $sql .= " AND o.user_id = ?";
            $params[] = $user_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch();

        if ($order) {
            // Get order items
            $stmt = $this->db->prepare("
                SELECT oi.*, p.name as product_name,
                       pi.image_path as product_image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order['items'] = $stmt->fetchAll();

            // Get shipments
            $stmt = $this->db->prepare("
                SELECT * FROM shipments
                WHERE order_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$order_id]);
            $order['shipments'] = $stmt->fetchAll();
        }

        return $order;
    }

    public function updateOrderStatus($order_id, $status, $notes = '') {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE orders 
                SET status = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $notes, $order_id]);

            if ($status === 'shipped') {
                $tracking_number = $this->generateTrackingNumber();
                $stmt = $this->db->prepare("
                    INSERT INTO shipments (
                        order_id, tracking_number, status, shipped_at
                    ) VALUES (?, ?, 'shipped', NOW())
                ");
                $stmt->execute([$order_id, $tracking_number]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating order status: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateTrackingNumber() {
        return 'TRK' . date('Ymd') . rand(1000, 9999);
    }

    public function createPartialShipment($order_id, $items) {
        try {
            $this->db->beginTransaction();

            $tracking_number = $this->generateTrackingNumber();
            
            // Create shipment
            $stmt = $this->db->prepare("
                INSERT INTO shipments (
                    order_id, tracking_number, status, shipped_at
                ) VALUES (?, ?, 'shipped', NOW())
            ");
            $stmt->execute([$order_id, $tracking_number]);
            $shipment_id = $this->db->lastInsertId();

            // Update order items status
            foreach ($items as $item_id => $quantity) {
                $stmt = $this->db->prepare("
                    UPDATE order_items 
                    SET status = 'shipped'
                    WHERE id = ? AND order_id = ?
                ");
                $stmt->execute([$item_id, $order_id]);
            }

            // Check if all items shipped
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as remaining
                FROM order_items
                WHERE order_id = ? AND status = 'pending'
            ");
            $stmt->execute([$order_id]);
            $remaining = $stmt->fetch()['remaining'];

            // Update order status
            $new_status = $remaining > 0 ? 'partially_shipped' : 'shipped';
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $order_id]);

            $this->db->commit();
            return $tracking_number;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating partial shipment: " . $e->getMessage());
            throw $e;
        }
    }
}
