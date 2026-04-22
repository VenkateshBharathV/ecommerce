<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
require_once '../includes/order_system.php';

ensureOrderSystemSchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_orders.php");
    exit();
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$startDate = trim($_POST['start_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');

if (
    $orderId <= 0 ||
    !in_array($status, orderAdminStatuses(), true) ||
    ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) ||
    ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))
) {
    $_SESSION['admin_orders_flash'] = 'Invalid order update request.';
    header("Location: admin_orders.php");
    exit();
}

$order = getOrderWithItems($conn, $orderId);
if (!$order) {
    $_SESSION['admin_orders_flash'] = 'Order not found.';
    header("Location: admin_orders.php");
    exit();
}

$note = 'Status updated by admin';
if ($status === 'Delivered') {
    $note = 'Order completed by admin';
} elseif ($status === 'Cancelled') {
    $note = 'Order cancelled by admin';
}

if (updateOrderStatus($conn, $orderId, $status, $note, $startDate, $endDate)) {
    $_SESSION['admin_orders_flash'] = 'Order updated successfully.';
} else {
    $_SESSION['admin_orders_flash'] = 'Unable to update the order. Delivered or cancelled orders cannot be changed further.';
}

header("Location: admin_orders.php");
exit();
