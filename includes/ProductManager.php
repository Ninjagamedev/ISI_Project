<?php
class ProductManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createProduct($data, $images) {
        try {
            $this->db->beginTransaction();

            // Insert product
            $stmt = $this->db->prepare("
                INSERT INTO products (
                    shop_id, name, slug, description, price,
                    category_id, is_configurable, status,
                    meta_title, meta_description, tags
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $slug = $this->generateSlug($data['name']);
            
            $stmt->execute([
                $data['shop_id'],
                $data['name'],
                $slug,
                $data['description'],
                $data['price'],
                $data['category_id'],
                isset($data['is_configurable']) ? 1 : 0,
                'active',
                $data['meta_title'] ?? $data['name'],
                $data['meta_description'] ?? '',
                $data['tags'] ?? ''
            ]);

            $product_id = $this->db->lastInsertId();

            // Handle images
            foreach ($images as $index => $image) {
                $this->addProductImage($product_id, $image, $index === 0);
            }

            // Handle configurable options if any
            if (isset($data['options']) && is_array($data['options'])) {
                $this->addProductOptions($product_id, $data['options']);
            }

            $this->db->commit();
            return $product_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating product: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        return $slug;
    }

    private function addProductImage($product_id, $image, $is_primary = false) {
        $upload_dir = UPLOAD_DIR . 'products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = uniqid() . '_' . basename($image['name']);
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($image['tmp_name'], $filepath)) {
            $stmt = $this->db->prepare("
                INSERT INTO product_images (product_id, image_path, is_primary)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$product_id, 'uploads/products/' . $filename, $is_primary]);
            return true;
        }
        return false;
    }

    private function addProductOptions($product_id, $options) {
        foreach ($options as $option) {
            $stmt = $this->db->prepare("
                INSERT INTO product_options (product_id, option_name, option_values)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $product_id,
                $option['name'],
                json_encode($option['values'])
            ]);
        }
    }

    public function getProduct($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name,
                   GROUP_CONCAT(DISTINCT pi.image_path) as images
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $product['images'] = explode(',', $product['images']);
            $product['options'] = $this->getProductOptions($id);
        }

        return $product;
    }

    public function getProductOptions($product_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM product_options
            WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetchAll();
    }

    public function searchProducts($query, $filters = []) {
        $sql = "
            SELECT p.*, c.name as category_name,
                   pi.image_path as primary_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.status = 'active'
        ";
        
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['price_min'])) {
            $sql .= " AND p.price >= ?";
            $params[] = $filters['price_min'];
        }

        if (!empty($filters['price_max'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['price_max'];
        }

        if (!empty($filters['shop_id'])) {
            $sql .= " AND p.shop_id = ?";
            $params[] = $filters['shop_id'];
        }

        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateProduct($id, $data) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE products SET
                    name = ?,
                    description = ?,
                    price = ?,
                    category_id = ?,
                    meta_title = ?,
                    meta_description = ?,
                    tags = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $data['category_id'],
                $data['meta_title'] ?? $data['name'],
                $data['meta_description'] ?? '',
                $data['tags'] ?? '',
                $id
            ]);

            if (isset($data['options'])) {
                // Delete existing options
                $stmt = $this->db->prepare("DELETE FROM product_options WHERE product_id = ?");
                $stmt->execute([$id]);

                // Add new options
                $this->addProductOptions($id, $data['options']);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating product: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteProduct($id) {
        try {
            $this->db->beginTransaction();

            // Delete product images
            $stmt = $this->db->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->execute([$id]);

            // Delete product options
            $stmt = $this->db->prepare("DELETE FROM product_options WHERE product_id = ?");
            $stmt->execute([$id]);

            // Delete product
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deleting product: " . $e->getMessage());
            throw $e;
        }
    }
}
