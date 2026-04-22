<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
require_once '../includes/order_system.php';

ensureOrderSystemSchema($conn);

$statuses = orderAdminStatuses();
$statusMeta = orderStatusMeta();
$flash = $_SESSION['admin_orders_flash'] ?? '';
unset($_SESSION['admin_orders_flash']);

$ordersStmt = $conn->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Orders</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
body {
    margin: 0;
    display: flex;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6f9;
}

/* SIDEBAR */
.sidebar {
    width: 220px;
    height: 100vh;
    background: linear-gradient(180deg, #ff7a00, #ff3c00);
    color: white;
    padding: 20px;
    position: fixed;
}

.sidebar a {
    display: block;
    padding: 12px;
    color: white;
    text-decoration: none;
    margin-bottom: 10px;
    border-radius: 8px;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.2);
}

/* MAIN */
.main {
    margin-left: 220px;
    width: 100%;
}

/* TOPBAR */
.topbar {
    background: white;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* TABLE BOX */
.table-box {
    margin: 30px;
    background: white;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #ff7a00, #ff3c00);
    color: white;
}

th, td {
    padding: 14px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #fafafa;
}

/* BADGE */
.badge-status {
    padding: 6px 12px;
    border-radius: 20px;
    color: white;
    font-size: 13px;
}

/* FORM */
.form-grid {
    display: grid;
    gap: 6px;
}

select, input {
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

/* BUTTON */
.update-btn {
    background: linear-gradient(135deg, #2d6df6, #ff7a00);
    border: none;
    padding: 8px;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.update-btn:hover {
    transform: scale(1.05);
}

.update-btn:disabled {
    background: gray;
}

/* FLASH */
.flash {
    margin: 20px;
    padding: 10px;
    background: #e3f2fd;
    border-radius: 10px;
}

/* RESPONSIVE */
@media(max-width:768px){
    .sidebar { width:180px; }
    .main { margin-left:180px; }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>🛒 Admin</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="manage_products.php">Products</a>
    <a href="admin_orders.php">Orders</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
    <h4>Order Management</h4>
    <span><?= date("d M Y"); ?></span>
</div>

<?php if ($flash !== ''): ?>
    <div class="flash"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="table-box">

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Total</th>
    <th>Status</th>
    <th>Start</th>
    <th>End</th>
    <th>Update</th>
</tr>
</thead>

<tbody>

<?php foreach ($orders as $order): ?>
<?php
$status = $order['order_status'] ?? 'Pending';
$meta = $statusMeta[$status] ?? $statusMeta['Pending'];
$isCompleted = in_array($status, ['Delivered', 'Cancelled'], true);
?>

<tr>

<td>#<?= $order['id'] ?></td>
<td><?= htmlspecialchars($order['name']) ?></td>
<td><?= htmlspecialchars($order['phone']) ?></td>
<td>₹<?= number_format($order['total'],2) ?></td>

<td>
<span class="badge-status" style="background:<?= $meta['badge'] ?>">
    <i class="fa <?= $meta['icon'] ?>"></i>
    <?= $status ?>
</span>
</td>

<td><?= $order['start_date'] ?: '—' ?></td>
<td><?= $order['end_date'] ?: '—' ?></td>

<td>
<form method="POST" action="admin_update_order.php" class="form-grid">

<input type="hidden" name="order_id" value="<?= $order['id'] ?>">

<select name="status" <?= $isCompleted ? 'disabled' : '' ?>>
<?php foreach ($statuses as $s): ?>
<option value="<?= $s ?>" <?= $s==$status?'selected':'' ?>>
<?= $s ?>
</option>
<?php endforeach; ?>
</select>

<input type="date" name="start_date" value="<?= $order['start_date'] ?>" <?= $isCompleted ? 'disabled' : '' ?>>
<input type="date" name="end_date" value="<?= $order['end_date'] ?>" <?= $isCompleted ? 'disabled' : '' ?>>

<button class="update-btn" <?= $isCompleted ? 'disabled' : '' ?>>
<?= $isCompleted ? 'Completed' : 'Update' ?>
</button>

</form>
</td>

</tr>
<?php endforeach; ?>

</tbody>
</table>

</div>

</div>

</body>
</html>