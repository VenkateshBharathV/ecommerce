<?php

$orderSystemAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($orderSystemAutoload)) {
    require_once $orderSystemAutoload;
}

require_once __DIR__ . '/services/WhatsAppService.php';

function orderSystemConfig(): array
{
    $configFile = __DIR__ . '/config.php';

    if (file_exists($configFile)) {
        $config = require $configFile;
        return is_array($config) ? $config : [];
    }

    return [];
}

function formatPhoneNumber(string $number, string $defaultCountryCode = '+91'): string
{
    $number = trim($number);

    if ($number === '') {
        return '';
    }

    $digits = preg_replace('/\D/', '', $number);

    if ($digits === '') {
        return '';
    }

    if (strlen($digits) === 12 && strpos($digits, '91') === 0) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) === 11 && strpos($digits, '0') === 0) {
        $digits = substr($digits, 1);
    }

    if (!preg_match('/^[6-9]\d{9}$/', $digits)) {
        return '';
    }

    return $defaultCountryCode . $digits;
}

function formatWhatsAppPhone(string $number): string
{
    $config = orderSystemConfig();
    $defaultCode = $config['whatsapp']['default_country_code'] ?? '+91';
    $service = new WhatsAppService($config['whatsapp'] ?? []);
    return $service->formatWhatsAppPhone($number, $defaultCode);
}

function orderStatusMeta(): array
{
    return [
        'Pending' => ['icon' => 'fa-hourglass-half', 'badge' => '#7b8794'],
        'Confirmed' => ['icon' => 'fa-circle-check', 'badge' => '#2d6df6'],
        'Packed' => ['icon' => 'fa-box-open', 'badge' => '#9b5de5'],
        'Shipped' => ['icon' => 'fa-truck-fast', 'badge' => '#f28500'],
        'Out for Delivery' => ['icon' => 'fa-map-location-dot', 'badge' => '#d64550'],
        'Delivered' => ['icon' => 'fa-house-circle-check', 'badge' => '#11854a'],
        'Cancelled' => ['icon' => 'fa-ban', 'badge' => '#d64550'],
    ];
}

function orderStatusSteps(): array
{
    return ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered'];
}

function orderAdminStatuses(): array
{
    return ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
}

function ensureOrderSystemSchema(PDO $conn): void
{
    $requiredColumns = [
        'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER pincode",
        'order_status' => "ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) NOT NULL DEFAULT 'Pending' AFTER payment_method",
        'start_date' => "ALTER TABLE orders ADD COLUMN start_date DATE NULL AFTER order_status",
        'end_date' => "ALTER TABLE orders ADD COLUMN end_date DATE NULL AFTER start_date",
        'delivery_otp' => "ALTER TABLE orders ADD COLUMN delivery_otp VARCHAR(10) NULL AFTER order_status",
        'otp_generated_at' => "ALTER TABLE orders ADD COLUMN otp_generated_at DATETIME NULL AFTER delivery_otp",
        'confirmed_at' => "ALTER TABLE orders ADD COLUMN confirmed_at DATETIME NULL AFTER created_at",
        'packed_at' => "ALTER TABLE orders ADD COLUMN packed_at DATETIME NULL AFTER confirmed_at",
        'shipped_at' => "ALTER TABLE orders ADD COLUMN shipped_at DATETIME NULL AFTER packed_at",
        'out_for_delivery_at' => "ALTER TABLE orders ADD COLUMN out_for_delivery_at DATETIME NULL AFTER shipped_at",
        'delivered_at' => "ALTER TABLE orders ADD COLUMN delivered_at DATETIME NULL AFTER out_for_delivery_at",
        'delivery_latitude' => "ALTER TABLE orders ADD COLUMN delivery_latitude DECIMAL(10,7) NULL AFTER delivered_at",
        'delivery_longitude' => "ALTER TABLE orders ADD COLUMN delivery_longitude DECIMAL(10,7) NULL AFTER delivery_latitude",
        'updated_at' => "ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    foreach ($requiredColumns as $column => $sql) {
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM orders LIKE '{$column}'");
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                $conn->exec($sql);
            }
        } catch (Throwable $e) {
            error_log('Schema update failed for ' . $column . ': ' . $e->getMessage());
        }
    }

    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS order_tracking_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                status VARCHAR(50) NOT NULL,
                note VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_tracking_logs_order_id (order_id)
            )
        ");
    } catch (Throwable $e) {
        error_log('Unable to create order_tracking_logs table: ' . $e->getMessage());
    }
}

function logTrackingEvent(PDO $conn, int $orderId, string $status, string $note = ''): void
{
    try {
        $stmt = $conn->prepare("
            INSERT INTO order_tracking_logs (order_id, status, note)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$orderId, $status, $note !== '' ? $note : null]);
    } catch (Throwable $e) {
        error_log('Unable to log tracking event: ' . $e->getMessage());
    }
}

function getOrderWithItems(PDO $conn, int $orderId, ?int $userId = null): ?array
{
    $sql = "
        SELECT *
        FROM orders
        WHERE id = ?
    ";
    $params = [$orderId];

    if ($userId !== null) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }

    $sql .= " LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        return null;
    }

    $itemStmt = $conn->prepare("
        SELECT oi.quantity, oi.price, p.name AS product_name, p.image
        FROM order_items oi
        INNER JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $itemStmt->execute([$orderId]);
    $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    $logStmt = $conn->prepare("
        SELECT *
        FROM order_tracking_logs
        WHERE order_id = ?
        ORDER BY created_at ASC, id ASC
    ");
    $logStmt->execute([$orderId]);
    $order['tracking_logs'] = $logStmt->fetchAll(PDO::FETCH_ASSOC);

    return $order;
}

function generateDeliveryOtp(): string
{
    return (string) random_int(1000, 9999);
}

function buildOrderMessageByStatus(array $order, string $status): string
{
    $name = $order['name'] ?? 'Customer';
    $orderId = $order['id'] ?? '';
    $total = (float) ($order['total'] ?? 0);

    if ($status === 'Pending') {
        $config = orderSystemConfig();
        $service = new WhatsAppService($config['whatsapp'] ?? []);
        return $service->buildOrderPlacedMessage((string) $name, (int) $orderId, $total);
    }

    switch ($status) {
        case 'Confirmed':
            return "Hello {$name}, your order #{$orderId} is confirmed.";

        case 'Packed':
            return "Hello {$name}, your order #{$orderId} is packed 📦";

        case 'Shipped':
            return "Hello {$name}, your order #{$orderId} has been shipped 🚚";

        case 'Out for Delivery':
            return "Hello {$name}, your order #{$orderId} is out for delivery 🚀";

        case 'Delivered':
            return "Hello {$name}, your order #{$orderId} is delivered 🎉 Thank you!";

        case 'Cancelled':
            return "Hello {$name}, your order #{$orderId} has been cancelled.";

        default:
            return "Hello {$name}, there is an update for your order #{$orderId}.";
    }
}

function sendWhatsApp(string $phone, string $message): bool
{
    $config = orderSystemConfig();
    $whatsapp = $config['whatsapp'] ?? [];
    $service = new WhatsAppService($whatsapp);
    return $service->sendWhatsApp($phone, $message);
}

function updateOrderStatus(PDO $conn, int $orderId, string $newStatus, string $note = '', ?string $startDate = null, ?string $endDate = null): bool
{
    $allowedStatuses = orderAdminStatuses();
    if (!in_array($newStatus, $allowedStatuses, true)) {
        return false;
    }

    $order = getOrderWithItems($conn, $orderId);
    if (!$order) {
        return false;
    }

    $currentStatus = $order['order_status'] ?? 'Pending';
    if (in_array($currentStatus, ['Delivered', 'Cancelled'], true) && $newStatus !== $currentStatus) {
        return false;
    }

    if ($startDate !== null && $startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        return false;
    }

    if ($endDate !== null && $endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        return false;
    }

    $updateData = [
        'order_status' => $newStatus,
        'delivery_latitude' => $order['delivery_latitude'] ?? 12.9715987,
        'delivery_longitude' => $order['delivery_longitude'] ?? 77.5945660,
    ];

    if ($startDate !== null) {
        $updateData['start_date'] = $startDate !== '' ? $startDate : null;
    }

    if ($endDate !== null) {
        $updateData['end_date'] = $endDate !== '' ? $endDate : null;
    }

    if ($newStatus === 'Out for Delivery') {
        $updateData['delivery_otp'] = generateDeliveryOtp();
        $updateData['otp_generated_at'] = date('Y-m-d H:i:s');
    } elseif (in_array($newStatus, ['Delivered', 'Cancelled'], true)) {
        $updateData['delivery_otp'] = null;
    } else {
        $updateData['delivery_otp'] = $order['delivery_otp'] ?? null;
    }

    $statusTimestampColumnMap = [
        'Confirmed' => 'confirmed_at',
        'Packed' => 'packed_at',
        'Shipped' => 'shipped_at',
        'Out for Delivery' => 'out_for_delivery_at',
        'Delivered' => 'delivered_at',
    ];

    if (isset($statusTimestampColumnMap[$newStatus])) {
        $updateData[$statusTimestampColumnMap[$newStatus]] = date('Y-m-d H:i:s');
    }

    $setParts = [];
    $params = [];
    foreach ($updateData as $column => $value) {
        $setParts[] = "{$column} = ?";
        $params[] = $value;
    }
    $params[] = $orderId;

    try {
        $stmt = $conn->prepare("UPDATE orders SET " . implode(', ', $setParts) . " WHERE id = ?");
        $stmt->execute($params);

        logTrackingEvent($conn, $orderId, $newStatus, $note);

        $updatedOrder = getOrderWithItems($conn, $orderId);
        if ($updatedOrder) {
            $phone = formatWhatsAppPhone((string) ($updatedOrder['phone'] ?? ''));
            if ($phone !== '') {
                sendWhatsApp($phone, buildOrderMessageByStatus($updatedOrder, $newStatus));
            }
        }

        return true;
    } catch (Throwable $e) {
        error_log('Unable to update order status: ' . $e->getMessage());
        return false;
    }
}

function verifyDeliveryOtp(PDO $conn, int $orderId, int $userId, string $otp): bool
{
    $order = getOrderWithItems($conn, $orderId, $userId);
    if (!$order || ($order['order_status'] ?? '') !== 'Out for Delivery') {
        return false;
    }

    if (($order['delivery_otp'] ?? '') !== trim($otp)) {
        return false;
    }

    return updateOrderStatus($conn, $orderId, 'Delivered', 'Delivery OTP verified successfully');
}

function orderStatusIndex(string $status): int
{
    $steps = orderStatusSteps();
    $index = array_search($status, $steps, true);
    return $index === false ? 0 : (int) $index;
}
