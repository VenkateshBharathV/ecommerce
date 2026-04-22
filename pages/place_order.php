<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
require_once '../includes/order_system.php';

$user_id = (int) $_SESSION['user_id'];
$checkoutData = $_SESSION['checkout_data'] ?? null;
$paymentMethod = trim($_POST['payment_method'] ?? '');
$allowedPaymentMethods = ['Cash on Delivery', 'UPI', 'Card'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$checkoutData) {
    header("Location: checkout.php");
    exit();
}

if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
    header("Location: payment.php");
    exit();
}

$name = trim($checkoutData['name'] ?? '');
$phone = formatPhoneNumber((string) ($checkoutData['phone'] ?? ''));
$address = trim($checkoutData['address'] ?? '');
$city = trim($checkoutData['city'] ?? '');
$state = trim($checkoutData['state'] ?? '');
$pincode = trim($checkoutData['pincode'] ?? '');
$total = (float) ($checkoutData['total'] ?? 0);

if ($name === '' || $phone === '' || $address === '' || $city === '' || $state === '' || $pincode === '' || $total <= 0) {
    header("Location: checkout.php");
    exit();
}

// Fetch cart items again so the final save uses live cart data at confirmation time.
$stmt = $conn->prepare("
    SELECT * FROM cart WHERE user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

ensureOrderSystemSchema($conn);

try {
    $conn->beginTransaction();

    // Save the order after payment method confirmation.
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, name, phone, address, city, state, pincode, payment_method, order_status, total, delivery_latitude, delivery_longitude)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $name,
        $phone,
        $address,
        $city,
        $state,
        $pincode,
        $paymentMethod,
        'Pending',
        $total,
        12.9715987,
        77.5945660,
    ]);

    $order_id = (int) $conn->lastInsertId();

    foreach ($cart_items as $item) {
        $productStmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
        $productStmt->execute([$item['product_id']]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException('Product not found while saving the order.');
        }

        $insertItemStmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        $insertItemStmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $product['price'],
        ]);
    }

    $deleteCartStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $deleteCartStmt->execute([$user_id]);

    logTrackingEvent($conn, $order_id, 'Pending', 'Order submitted from checkout with payment method: ' . $paymentMethod);

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Order placement failed: ' . $e->getMessage());
    echo "Unable to place the order right now.";
    exit();
}

// Fetch the saved order and its items for backend-only WhatsApp sending.
$savedOrder = getOrderWithItems($conn, $order_id, $user_id);

if ($savedOrder) {
    $customerPhone = formatWhatsAppPhone((string) $savedOrder['phone']);

    if ($customerPhone !== '') {
        sendWhatsApp($customerPhone, buildOrderMessageByStatus($savedOrder, 'Pending'));
    } else {
        error_log('WhatsApp send skipped: invalid customer phone for order #' . $order_id);
    }
}

$_SESSION['last_order_id'] = $order_id;
unset($_SESSION['checkout_data']);

header("Location: order_success.php?order_id=" . $order_id);
exit();
