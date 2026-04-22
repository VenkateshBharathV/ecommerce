<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

/**
 * Normalize an Indian mobile number and save it as +91XXXXXXXXXX.
 */
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

$user_id = (int) $_SESSION['user_id'];
$paymentData = $_SESSION['checkout_data'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = formatPhoneNumber((string) ($_POST['phone'] ?? ''));
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $total = (float) ($_POST['total'] ?? 0);

    // Confirm the cart still has items before moving to payment selection.
    $cartStmt = $conn->prepare("
        SELECT c.quantity, p.name, p.price
        FROM cart c
        INNER JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
        ORDER BY c.id ASC
    ");
    $cartStmt->execute([$user_id]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($name === '' || $phone === '' || $address === '' || $city === '' || $state === '' || $pincode === '' || $total <= 0 || empty($cartItems)) {
        header("Location: checkout.php");
        exit();
    }

    $_SESSION['checkout_data'] = [
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'pincode' => $pincode,
        'total' => $total,
        'items' => $cartItems,
    ];

    $paymentData = $_SESSION['checkout_data'];
}

if (!$paymentData) {
    header("Location: checkout.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Select Payment Method</title>
<meta charset="UTF-8">
<style>
:root {
    --bg: linear-gradient(135deg, #eef4ff 0%, #f8fbff 50%, #fdf7f0 100%);
    --surface: #fff;
    --text: #183153;
    --muted: #60708a;
    --border: #dbe5f1;
}

body.dark {
    --bg: linear-gradient(135deg, #0f1724 0%, #101a2c 50%, #151b28 100%);
    --surface: #162032;
    --text: #eef4ff;
    --muted: #a9b8d0;
    --border: #2b3c56;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
}

.page {
    width: 92%;
    max-width: 960px;
    margin: 30px auto;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 24px;
}

.card {
    background: var(--surface);
    border-radius: 18px;
    box-shadow: 0 18px 45px rgba(17, 39, 84, 0.10);
    padding: 28px;
}

.title {
    margin: 0 0 8px;
    font-size: 28px;
    color: var(--text);
}

.subtitle {
    margin: 0 0 24px;
    color: var(--muted);
}

.payment-option {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.payment-option:hover {
    border-color: #7c9cff;
    box-shadow: 0 10px 24px rgba(80, 108, 255, 0.10);
    transform: translateY(-1px);
}

.payment-option input {
    margin-top: 4px;
}

.payment-option strong {
    display: block;
    color: var(--text);
    font-size: 16px;
    margin-bottom: 4px;
}

.payment-option span {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.5;
}

.summary-title {
    margin-top: 0;
    color: var(--text);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
    color: var(--muted);
}

.summary-total {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
    font-size: 22px;
    font-weight: 700;
    color: #1d7a32;
}

.address-box {
    margin-top: 20px;
    padding: 14px;
    background: rgba(127, 156, 255, 0.08);
    border-radius: 12px;
    color: var(--muted);
    line-height: 1.6;
}

.item-list {
    margin-top: 18px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    color: var(--text);
}

.confirm-btn {
    width: 100%;
    margin-top: 18px;
    border: none;
    border-radius: 12px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #ff7a18, #ff5722);
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
}

.back-link {
    display: inline-block;
    margin-top: 16px;
    color: #3767d6;
    text-decoration: none;
    font-weight: 600;
}

.topbar {
    width: 92%;
    max-width: 960px;
    margin: 24px auto 0;
    display: flex;
    justify-content: flex-end;
}

.toggle-btn {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    border-radius: 999px;
    padding: 10px 14px;
    cursor: pointer;
}

@media (max-width: 860px) {
    .page {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="topbar">
    <button class="toggle-btn" id="themeToggle">Dark Mode</button>
</div>

<div class="page">
    <div class="card">
        <h1 class="title">Choose Payment Method</h1>
        <p class="subtitle">Select how you want to pay before we confirm and place your order.</p>

        <form method="POST" action="place_order.php">
            <label class="payment-option">
                <input type="radio" name="payment_method" value="Cash on Delivery" checked>
                <div>
                    <strong>Cash on Delivery (COD)</strong>
                    <span>Pay in cash when your order arrives at your doorstep.</span>
                </div>
            </label>

            <label class="payment-option">
                <input type="radio" name="payment_method" value="UPI">
                <div>
                    <strong>UPI</strong>
                    <span>Use any UPI app. This is a placeholder flow for now.</span>
                </div>
            </label>

            <label class="payment-option">
                <input type="radio" name="payment_method" value="Card">
                <div>
                    <strong>Card</strong>
                    <span>Debit or credit card. This is a dummy payment option for now.</span>
                </div>
            </label>

            <button type="submit" class="confirm-btn">Confirm Payment &amp; Place Order</button>
            <a href="checkout.php" class="back-link">Back to Checkout</a>
        </form>
    </div>

    <div class="card">
        <h2 class="summary-title">Order Summary</h2>

        <div class="summary-row">
            <span>Customer</span>
            <strong><?= htmlspecialchars($paymentData['name']) ?></strong>
        </div>
        <div class="summary-row">
            <span>Phone</span>
            <strong><?= htmlspecialchars($paymentData['phone']) ?></strong>
        </div>
        <div class="summary-row">
            <span>Items</span>
            <strong><?= count($paymentData['items']) ?></strong>
        </div>

        <div class="item-list">
            <?php foreach ($paymentData['items'] as $item): ?>
                <div class="item-row">
                    <span><?= htmlspecialchars($item['name']) ?> x <?= (int) $item['quantity'] ?></span>
                    <strong>Rs <?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-total">Total: Rs <?= number_format((float) $paymentData['total'], 2) ?></div>

        <div class="address-box">
            <strong>Delivery Address</strong><br>
            <?= htmlspecialchars($paymentData['address']) ?><br>
            <?= htmlspecialchars($paymentData['city']) ?>, <?= htmlspecialchars($paymentData['state']) ?> - <?= htmlspecialchars($paymentData['pincode']) ?>
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
