<?php

class OrderRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Fetch a saved order with its items after the transaction commits.
     */
    public function getOrderDetailsById(int $orderId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT
                o.id,
                o.user_id,
                o.name,
                o.phone,
                o.address,
                o.city,
                o.state,
                o.pincode,
                o.total,
                o.created_at,
                u.username
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            WHERE o.id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        $itemStmt = $this->conn->prepare("
            SELECT
                oi.product_id,
                oi.quantity,
                oi.price,
                p.name AS product_name
            FROM order_items oi
            INNER JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");
        $itemStmt->execute([$orderId]);

        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }
}
