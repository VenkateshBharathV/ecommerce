<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$orderId = (int) ($_GET['order_id'] ?? ($_SESSION['last_order_id'] ?? 0));

if ($orderId <= 0) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Order Success</title>
<meta charset="UTF-8">
<style>
:root {
    --bg: radial-gradient(circle at top, #f7fbff 0%, #eef4fb 45%, #f6f7fb 100%);
    --surface: #fff;
    --text: #1d8f46;
    --muted: #566577;
    --secondary-bg: #edf2ff;
    --secondary-text: #27478c;
}

body.dark {
    --bg: radial-gradient(circle at top, #111c2c 0%, #0d1522 45%, #121926 100%);
    --surface: #162032;
    --text: #7ee09d;
    --muted: #a8b6cc;
    --secondary-bg: #22314d;
    --secondary-text: #dce7ff;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--bg);
}

.container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.success-box {
    width: 440px;
    max-width: 100%;
    background: var(--surface);
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(17, 39, 84, 0.12);
    padding: 42px 34px;
    text-align: center;
}

.icon {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: linear-gradient(135deg, #32b45f, #1d9248);
    color: #fff;
    font-size: 42px;
    line-height: 88px;
    margin: 0 auto 22px;
}

h1 {
    margin: 0 0 10px;
    color: var(--text);
    font-size: 34px;
}

.order-id {
    color: var(--muted);
    font-size: 17px;
    margin-bottom: 28px;
}

.btn-row {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    text-decoration: none;
    padding: 13px 18px;
    min-width: 170px;
    border-radius: 12px;
    font-weight: 700;
}

.btn.primary {
    background: #2d6df6;
    color: #fff;
}

.btn.secondary {
    background: var(--secondary-bg);
    color: var(--secondary-text);
}

.topbar {
    position: absolute;
    top: 20px;
    right: 20px;
}

.toggle-btn {
    border: none;
    border-radius: 999px;
    padding: 10px 14px;
    cursor: pointer;
}
</style>
</head>
<body>

<div class="topbar">
    <button class="toggle-btn" id="themeToggle">Dark Mode</button>
</div>

<div class="container">
    <div class="success-box">
        <div class="icon">&#10004;</div>
        <h1>Order Placed Successfully</h1>
        <div class="order-id">Order ID: <strong>#<?= $orderId ?></strong></div>
        <div class="btn-row">
            <a href="index.php" class="btn primary">Continue Shopping</a>
            <a href="orders.php" class="btn secondary">My Orders</a>
        </div>
    </div>
</div>

<script>
const themeToggle = document.getElementById('themeToggle');
if (localStorage.getItem('order-theme') === 'dark') {
    document.body.classList.add('dark');
}
themeToggle.addEventListener('click', function () {
    document.body.classList.toggle('dark');
    localStorage.setItem('order-theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});
</script>
<?php include 'includes/ai_assistant.php'; ?>
</body>
</html>
