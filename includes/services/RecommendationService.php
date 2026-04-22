<?php

class RecommendationService
{
    public function getRecommendedProducts(PDO $conn, ?int $userId = null, int $limit = 6): array
    {
        $categories = [];
        $excludeProductIds = [];

        if ($userId) {
            $cartStmt = $conn->prepare('
                SELECT DISTINCT p.category, p.id
                FROM cart c
                INNER JOIN products p ON p.id = c.product_id
                WHERE c.user_id = ?
            ');
            $cartStmt->execute([$userId]);

            foreach ($cartStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['category'])) {
                    $categories[] = $row['category'];
                }
                if (!empty($row['id'])) {
                    $excludeProductIds[] = (int) $row['id'];
                }
            }

            $orderStmt = $conn->prepare('
                SELECT DISTINCT p.category
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN products p ON p.id = oi.product_id
                WHERE o.user_id = ?
                ORDER BY o.id DESC
                LIMIT 12
            ');
            $orderStmt->execute([$userId]);

            foreach ($orderStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!empty($row['category'])) {
                    $categories[] = $row['category'];
                }
            }
        }

        $categories = array_values(array_unique($categories));
        $excludeProductIds = array_values(array_unique($excludeProductIds));

        if (!empty($categories)) {
            $categoryPlaceholders = implode(',', array_fill(0, count($categories), '?'));
            $sql = "SELECT id, name, description, category, price, image FROM products WHERE category IN ({$categoryPlaceholders})";
            $params = $categories;

            if (!empty($excludeProductIds)) {
                $excludePlaceholders = implode(',', array_fill(0, count($excludeProductIds), '?'));
                $sql .= " AND id NOT IN ({$excludePlaceholders})";
                $params = array_merge($params, $excludeProductIds);
            }

            $sql .= ' ORDER BY id DESC LIMIT ' . max(1, $limit);

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($products)) {
                return $products;
            }
        }

        $stmt = $conn->query('SELECT id, name, description, category, price, image FROM products ORDER BY id DESC LIMIT ' . max(1, $limit));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSimilarProducts(PDO $conn, string $category, array $excludeProductIds = [], int $limit = 4): array
    {
        $params = [$category];
        $sql = 'SELECT id, name, description, category, price, image FROM products WHERE category = ?';

        if (!empty($excludeProductIds)) {
            $excludePlaceholders = implode(',', array_fill(0, count($excludeProductIds), '?'));
            $sql .= " AND id NOT IN ({$excludePlaceholders})";
            $params = array_merge($params, $excludeProductIds);
        }

        $sql .= ' ORDER BY id DESC LIMIT ' . max(1, $limit);

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
