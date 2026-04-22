<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
require_once '../includes/order_system.php';

ensureOrderSystemSchema($conn);

$userId = (int) $_SESSION['user_id'];
$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    header("Location: orders.php");
    exit();
}

$otpError = '';
$otpSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['delivery_otp'] ?? '');

    if (verifyDeliveryOtp($conn, $orderId, $userId, $otp)) {
        $otpSuccess = 'Delivery OTP verified successfully. Order marked as delivered.';
    } else {
        $otpError = 'Invalid OTP or this order is not ready for delivery confirmation yet.';
    }
}

$order = getOrderWithItems($conn, $orderId, $userId);
if (!$order) {
    header("Location: orders.php");
    exit();
}

$steps = orderStatusSteps();
$meta = orderStatusMeta();
$currentIndex = orderStatusIndex($order['order_status'] ?? 'Order Placed');
$progress = count($steps) > 1 ? ($currentIndex / (count($steps) - 1)) * 100 : 0;
$mapApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: 'YOUR_GOOGLE_MAPS_API_KEY';
$mapLat = (float) ($order['delivery_latitude'] ?? 12.9715987);
$mapLng = (float) ($order['delivery_longitude'] ?? 77.5945660);
?>
<!DOCTYPE html>
<html>
<head>
<title>Track Order</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root {
    --bg: #f5f8fd;
    --surface: #ffffff;
    --surface-2: #eff4fb;
    --text: #18283f;
    --muted: #6b7b90;
    --border: #d9e3ef;
    --accent: #2d6df6;
    --accent-2: #ff7a18;
    --success: #179652;
    --warning: #d97b00;
    --danger: #d64550;
    --shadow: 0 22px 50px rgba(17, 39, 84, 0.10);
}

body.dark {
    --bg: #0f1724;
    --surface: #162032;
    --surface-2: #1b2940;
    --text: #eef4ff;
    --muted: #aebbd1;
    --border: #2d3b53;
    --accent: #7ca8ff;
    --accent-2: #ffaf60;
    --shadow: 0 26px 60px rgba(0, 0, 0, 0.38);
}

* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: radial-gradient(circle at top, rgba(45,109,246,0.10) 0%, transparent 34%), var(--bg);
    color: var(--text);
}

.container {
    width: min(1150px, 94%);
    margin: 28px auto 40px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}

.topbar h1 {
    margin: 0 0 6px;
}

.topbar p {
    margin: 0;
    color: var(--muted);
}

.toggle-btn, .back-btn {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    border-radius: 999px;
    padding: 10px 14px;
    text-decoration: none;
    cursor: pointer;
}

.grid {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 24px;
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 22px;
    padding: 24px;
    box-shadow: var(--shadow);
}

.status-badge {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    color: #fff;
    padding: 9px 14px;
    border-radius: 999px;
    font-weight: 700;
}

.progress-track {
    margin: 26px 0 20px;
    position: relative;
    height: 10px;
    background: var(--surface-2);
    border-radius: 999px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
    border-radius: 999px;
    width: 0;
    animation: fillProgress 1.1s ease forwards;
}

.timeline {
    display: grid;
    gap: 16px;
}

.timeline-step {
    display: grid;
    grid-template-columns: 52px 1fr;
    gap: 14px;
    align-items: start;
}

.timeline-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: var(--surface-2);
    color: var(--muted);
    font-size: 18px;
}

.timeline-step.active .timeline-icon {
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
    color: #fff;
}

.timeline-step.done .timeline-icon {
    background: linear-gradient(135deg, #18a558, #0f8b49);
    color: #fff;
}

.timeline-step p {
    margin: 4px 0 0;
    color: var(--muted);
}

.map-box {
    height: 320px;
    border-radius: 18px;
    background: var(--surface-2);
    overflow: hidden;
}

.map-fallback {
    display: grid;
    place-items: center;
    text-align: center;
    height: 100%;
    color: var(--muted);
    padding: 20px;
}

.info-grid {
    display: grid;
    gap: 12px;
}

.info-pill {
    background: var(--surface-2);
    border-radius: 14px;
    padding: 14px;
    color: var(--muted);
}

.info-pill strong {
    display: block;
    color: var(--text);
    margin-bottom: 6px;
}

.otp-box {
    margin-top: 18px;
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(45,109,246,0.12), rgba(255,122,24,0.14));
}

.otp-code {
    font-size: 34px;
    font-weight: 800;
    letter-spacing: 8px;
    color: var(--accent);
}

.otp-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 18px;
}

.otp-form input {
    flex: 1;
    min-width: 180px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    padding: 12px;
}

.otp-form button {
    border: none;
    border-radius: 12px;
    background: var(--success);
    color: #fff;
    padding: 12px 18px;
    font-weight: 700;
    cursor: pointer;
}

.feedback {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 12px;
}

.feedback.error {
    background: rgba(214,69,80,0.12);
    color: var(--danger);
}

.feedback.success {
    background: rgba(23,150,82,0.12);
    color: var(--success);
}

.log-list {
    display: grid;
    gap: 12px;
    margin-top: 18px;
}

.log-item {
    padding: 14px;
    border-radius: 14px;
    background: var(--surface-2);
}

.log-item strong {
    display: block;
}

@keyframes fillProgress {
    from { width: 0; }
    to { width: var(--progress-width); }
}

@media (max-width: 960px) {
    .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div>
            <h1>Track Order #<?= (int) $order['id'] ?></h1>
            <p>Live order history, delivery progress, OTP verification, and map tracking.</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="orders.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> My Orders</a>
            <button class="toggle-btn" id="themeToggle"><i class="fa-solid fa-moon"></i> Dark Mode</button>
        </div>
    </div>

    <?php if ($otpError !== ''): ?>
        <div class="feedback error" style="margin-bottom:18px;"><?= htmlspecialchars($otpError) ?></div>
    <?php endif; ?>
    <?php if ($otpSuccess !== ''): ?>
        <div class="feedback success" style="margin-bottom:18px;"><?= htmlspecialchars($otpSuccess) ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <?php $badgeMeta = $meta[$order['order_status']] ?? $meta['Confirmed']; ?>
            <span class="status-badge" style="background: <?= htmlspecialchars($badgeMeta['badge']) ?>;">
                <i class="fa-solid <?= htmlspecialchars($badgeMeta['icon']) ?>"></i>
                <?= htmlspecialchars($order['order_status']) ?>
            </span>

            <div class="progress-track">
                <div class="progress-fill" style="--progress-width: <?= number_format($progress, 2) ?>%;"></div>
            </div>

            <div class="timeline">
                <?php foreach ($steps as $index => $step): ?>
                    <?php
                    $stepClass = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'active' : '');
                    $stepMeta = $meta[$step];
                    ?>
                    <div class="timeline-step <?= $stepClass ?>">
                        <div class="timeline-icon">
                            <i class="fa-solid <?= htmlspecialchars($stepMeta['icon']) ?>"></i>
                        </div>
                        <div>
                            <strong><?= htmlspecialchars($step) ?></strong>
                            <p>
                                <?php
                                $matchingLog = null;
                                foreach ($order['tracking_logs'] as $log) {
                                    if ($log['status'] === $step) {
                                        $matchingLog = $log;
                                    }
                                }
                                echo $matchingLog
                                    ? htmlspecialchars(($matchingLog['note'] ?: 'Status updated') . ' on ' . $matchingLog['created_at'])
                                    : 'Waiting for this update.';
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (($order['order_status'] ?? '') === 'Out for Delivery'): ?>
                <div class="otp-box">
                    <div style="color:var(--muted);">Delivery OTP</div>
                    <div class="otp-code"><?= htmlspecialchars($order['delivery_otp'] ?? '----') ?></div>
                    <div style="margin-top:8px; color:var(--muted);">Share this OTP with the delivery agent to complete the order.</div>

                    <form method="POST" class="otp-form">
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="text" name="delivery_otp" maxlength="4" placeholder="Enter OTP to confirm delivery" required>
                        <button type="submit" name="verify_otp">Verify OTP</button>
                    </form>

                </div>
            <?php endif; ?>
        </div>

        <div style="display:grid; gap:24px;">
            <div class="card">
                <h2 style="margin-top:0;">Live Delivery Map</h2>
                <div class="map-box">
                    <div id="map" style="height:100%; width:100%;"></div>
                </div>
            </div>

            <div class="card">
                <h2 style="margin-top:0;">Order Details</h2>
                <div class="info-grid">
                    <div class="info-pill">
                        <strong>Payment Method</strong>
                        <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
                    </div>
                    <div class="info-pill">
                        <strong>Total Amount</strong>
                        Rs <?= number_format((float) $order['total'], 2) ?>
                    </div>
                    <div class="info-pill">
                        <strong>Delivery Address</strong>
                        <?= htmlspecialchars(($order['address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['state'] ?? '') . ' - ' . ($order['pincode'] ?? '')) ?>
                    </div>
                </div>

                <h3 style="margin:22px 0 10px;">Tracking History</h3>
                <div class="log-list">
                    <?php foreach ($order['tracking_logs'] as $log): ?>
                        <div class="log-item">
                            <strong><?= htmlspecialchars($log['status']) ?></strong>
                            <div style="color:var(--muted);"><?= htmlspecialchars($log['created_at']) ?></div>
                            <?php if (!empty($log['note'])): ?>
                                <div style="margin-top:6px; color:var(--text);"><?= htmlspecialchars($log['note']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const themeToggle = document.getElementById('themeToggle');
const savedTheme = localStorage.getItem('order-theme');
if (savedTheme === 'dark') {
    document.body.classList.add('dark');
}
themeToggle.addEventListener('click', function () {
    document.body.classList.toggle('dark');
    localStorage.setItem('order-theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});

function initMap() {
    const position = { lat: <?= json_encode($mapLat) ?>, lng: <?= json_encode($mapLng) ?> };
    const mapEl = document.getElementById('map');
    const map = new google.maps.Map(mapEl, {
        zoom: 13,
        center: position,
        styles: document.body.classList.contains('dark') ? [
            { elementType: 'geometry', stylers: [{ color: '#1d2c4d' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#1a3646' }] }
        ] : []
    });

    new google.maps.Marker({
        position,
        map,
        title: 'Delivery Agent',
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: '#ff7a18',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3
        }
    });
}

window.initMap = initMap;
</script>
<?php if ($mapApiKey !== 'YOUR_GOOGLE_MAPS_API_KEY'): ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($mapApiKey) ?>&callback=initMap"></script>
<?php else: ?>
    <script>
    document.getElementById('map').innerHTML =
        '<div class="map-fallback"><div><strong>Google Maps API key not configured.</strong><br>Demo delivery agent coordinates:<br><?= htmlspecialchars(number_format($mapLat, 5)) ?>, <?= htmlspecialchars(number_format($mapLng, 5)) ?></div></div>';
    </script>
<?php endif; ?>
<?php include 'includes/ai_assistant.php'; ?>
</body>
</html>
