<?php
class ShopManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createShop($vendor_id, $data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO shops (
                    vendor_id, name, slug, description,
                    logo_path, contact_email, contact_phone,
                    meta_title, meta_description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $slug = $this->generateSlug($data['name']);
            
            return $stmt->execute([
                $vendor_id,
                $data['name'],
                $slug,
                $data['description'],
                $data['logo_path'] ?? null,
                $data['contact_email'],
                $data['contact_phone'],
                $data['meta_title'] ?? $data['name'],
                $data['meta_description'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error creating shop: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        return $slug;
    }

    public function getShop($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name as vendor_name
            FROM shops s
            JOIN users u ON s.vendor_id = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateShop($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE shops SET
                    name = ?,
                    description = ?,
                    contact_email = ?,
                    contact_phone = ?,
                    meta_title = ?,
                    meta_description = ?
                WHERE id = ?
            ");

            return $stmt->execute([
                $data['name'],
                $data['description'],
                $data['contact_email'],
                $data['contact_phone'],
                $data['meta_title'] ?? $data['name'],
                $data['meta_description'] ?? '',
                $id
            ]);
        } catch (Exception $e) {
            error_log("Error updating shop: " . $e->getMessage());
            throw $e;
        }
    }

    public function getShopProducts($shop_id, $filters = []) {
        $sql = "
            SELECT p.*, c.name as category_name,
                   pi.image_path as primary_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.shop_id = ? AND p.status = 'active'
        ";
        
        $params = [$shop_id];

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
