<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include '../includes/db.php';
require_once '../includes/product_helpers.php';
require_once '../includes/order_system.php';
require_once '../includes/services/RecommendationService.php';

ensureOrderSystemSchema($conn);

$userId = (int) $_SESSION['user_id'];
$statusMeta = orderStatusMeta();
$recommendationService = new RecommendationService();

$stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$recommendedProducts = $recommendationService->getRecommendedProducts($conn, $userId, 4);

function canCancelOrder(string $status): bool
{
    return !in_array($status, ['Delivered', 'Cancelled'], true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>My Orders</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/ai-assistant.css">
<style>
body {
    margin: 0;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(255, 122, 0, 0.10), transparent 24%),
        linear-gradient(180deg, #fff8f2 0%, #f5f7fb 100%);
    color: #17253a;
}

.orders-shell {
    width: min(1180px, calc(100% - 28px));
    margin: 28px auto 42px;
}

.orders-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
    gap: 20px;
    margin-bottom: 24px;
}

.hero-card,
.hero-stat,
.order-card {
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(16px);
    box-shadow: 0 24px 45px rgba(15, 23, 42, 0.08);
}

.hero-card {
    padding: 28px;
    border-radius: 30px;
}

.hero-card h1 {
    margin: 0 0 10px;
    font-size: clamp(2rem, 4vw, 3.1rem);
}

.hero-card p {
    margin: 0;
    color: #5f7087;
    line-height: 1.7;
}

.hero-pills {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 18px;
}

.hero-pills span,
.hero-stat strong {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 14px;
    border-radius: 999px;
    background: rgba(255, 122, 0, 0.1);
    color: #b55a00;
    font-weight: 700;
}

.hero-side {
    display: grid;
    gap: 18px;
}

.hero-stat {
    padding: 22px;
    border-radius: 24px;
}

.hero-stat p {
    margin: 12px 0 0;
    color: #67778d;
    line-height: 1.6;
}

.order-grid {
    display: grid;
    gap: 22px;
}

.order-card {
    border-radius: 28px;
    padding: 24px;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 28px 50px rgba(15, 23, 42, 0.11);
}

.order-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
}

.order-head h2 {
    margin: 0 0 10px;
}

.order-meta {
    color: #607088;
    line-height: 1.8;
}

.order-price {
    text-align: right;
}

.order-price strong {
    display: block;
    font-size: 1.7rem;
    color: #11854a;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    margin-top: 12px;
    transition: background 0.25s ease, opacity 0.25s ease;
}

.item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
    margin: 22px 0;
}

.item-card {
    display: flex;
    gap: 12px;
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(255, 255, 255, 0.65);
    border-radius: 20px;
    padding: 14px;
}

.item-card img {
    width: 78px;
    height: 78px;
    object-fit: contain;
    background: #fff;
    border-radius: 16px;
    padding: 7px;
}

.item-info {
    color: #5f7087;
    line-height: 1.6;
}

.item-info strong {
    display: block;
    color: #17253a;
}

.action-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    border-top: 1px solid rgba(17, 24, 39, 0.06);
    padding-top: 18px;
}

.summary-wrap {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.summary-chip {
    color: #5f7087;
    background: rgba(255, 122, 0, 0.08);
    border-radius: 999px;
    padding: 10px 14px;
    font-size: 0.84rem;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.track-btn,
.cancel-btn {
    border: 0;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.22s ease, filter 0.22s ease, opacity 0.22s ease, background 0.22s ease;
}

.track-btn {
    background: linear-gradient(135deg, #10203a, #ff7a00);
    color: #fff;
}

.cancel-btn {
    background: linear-gradient(135deg, #ff5a63, #ff7a00);
    color: #fff;
    min-width: 145px;
}

.track-btn:hover,
.cancel-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.02);
}

.cancel-btn.is-loading,
.cancel-btn.is-cancelled {
    pointer-events: none;
}

.cancel-btn.is-loading {
    opacity: 0.85;
}

.cancel-btn.is-cancelled {
    background: linear-gradient(135deg, #97a2b4, #7d8597);
    opacity: 0.92;
}

.cancel-feedback {
    width: 100%;
    margin-top: 8px;
    color: #67778d;
    font-size: 0.92rem;
}

.empty {
    text-align: center;
    padding: 70px 24px;
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.8);
    box-shadow: 0 24px 45px rgba(15, 23, 42, 0.08);
}

.empty a {
    display: inline-flex;
    margin-top: 12px;
    padding: 12px 18px;
    border-radius: 16px;
    text-decoration: none;
    color: #fff;
    background: linear-gradient(135deg, #ff7a00, #ffb347);
}

@media (max-width: 900px) {
    .orders-hero {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .orders-shell {
        width: calc(100% - 20px);
    }

    .order-head,
    .action-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .order-price {
        text-align: left;
    }
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="orders-shell">
    <section class="orders-hero">
        <div class="hero-card">
            <h1>My Orders</h1>
            <p>Track each purchase, cancel eligible orders instantly, and keep every update in one polished workspace.</p>
            <div class="hero-pills">
                <span><i class="fa-solid fa-bolt"></i> Instant cancellation</span>
                <span><i class="fa-solid fa-location-crosshairs"></i> Order tracking</span>
                <span><i class="fa-solid fa-robot"></i> AI shopping help</span>
            </div>
        </div>

        <div class="hero-side">
            <div class="hero-stat">
                <strong><i class="fa-solid fa-bag-shopping"></i> <?= count($orders) ?> Total Order(s)</strong>
                <p>Your order history is now optimized with live actions and premium status cards.</p>
            </div>
            <div class="hero-stat">
                <strong><i class="fa-solid fa-star"></i> You May Also Like</strong>
                <p>Scroll down for recommendations based on your recent cart and order patterns.</p>
            </div>
        </div>
    </section>

    <?php if (empty($orders)): ?>
        <div class="empty">
            <h2>No orders yet</h2>
            <p>Your recent purchases will appear here once you place an order.</p>
            <a href="index.php">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="order-grid">
            <?php foreach ($orders as $order): ?>
                <?php
                $itemsStmt = $conn->prepare('
                    SELECT oi.quantity, oi.price, p.name, p.image
                    FROM order_items oi
                    INNER JOIN products p ON p.id = oi.product_id
                    WHERE oi.order_id = ?
                ');
                $itemsStmt->execute([(int) $order['id']]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                $status = (string) ($order['order_status'] ?? 'Pending');
                $meta = $statusMeta[$status] ?? $statusMeta['Pending'];
                ?>
                <article class="order-card">
                    <div class="order-head">
                        <div>
                            <h2>Order #<?= (int) $order['id'] ?></h2>
                            <div class="order-meta">
                                <div><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars((string) $order['created_at']) ?></div>
                                <div><i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars((string) ($order['payment_method'] ?? 'Payment Pending')) ?></div>
                                <div><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string) ($order['city'] ?? '')) ?>, <?= htmlspecialchars((string) ($order['state'] ?? '')) ?></div>
                            </div>
                            <span class="badge" data-order-status style="background: <?= htmlspecialchars((string) $meta['badge']) ?>;">
                                <i class="fa-solid <?= htmlspecialchars((string) $meta['icon']) ?>"></i>
                                <span data-order-status-text><?= htmlspecialchars($status) ?></span>
                            </span>
                        </div>

                        <div class="order-price">
                            <strong>Rs <?= number_format((float) $order['total'], 2) ?></strong>
                            <span>Total Amount</span>
                        </div>
                    </div>

                    <div class="item-grid">
                        <?php foreach ($items as $item): ?>
                            <?php $imagePath = resolveProductImagePath($item['image'] ?? ''); ?>
                            <div class="item-card">
                                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars((string) $item['name']) ?>">
                                <div class="item-info">
                                    <strong><?= htmlspecialchars((string) $item['name']) ?></strong>
                                    Qty: <?= (int) $item['quantity'] ?><br>
                                    Price: Rs <?= number_format((float) $item['price'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="action-row">
                        <div class="summary-wrap">
                            <span class="summary-chip">Phone: <?= htmlspecialchars((string) $order['phone']) ?></span>
                            <span class="summary-chip">Address: <?= htmlspecialchars((string) $order['address']) ?></span>
                        </div>

                        <div class="action-buttons">
                            <a href="track_order.php?order_id=<?= (int) $order['id'] ?>" class="track-btn">
                                <i class="fa-solid fa-location-crosshairs"></i> Track Order
                            </a>

                            <?php if (canCancelOrder($status)): ?>
                                <button
                                    type="button"
                                    class="cancel-btn"
                                    data-id="<?= (int) $order['id'] ?>"
                                    data-endpoint="../cancel_order.php"
                                >
                                    <span data-cancel-label>Cancel Order</span>
                                </button>
                            <?php else: ?>
                                <button type="button" class="cancel-btn is-cancelled" disabled>
                                    <span data-cancel-label><?= $status === 'Cancelled' ? 'Cancelled' : 'Locked' ?></span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="cancel-feedback" data-cancel-feedback>
                            <?= $status === 'Cancelled' ? 'This order has already been cancelled.' : 'You can cancel eligible orders here without reloading the page.' ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($recommendedProducts)): ?>
    <div class="ai-section">
        <div class="ai-section__header">
            <div>
                <h3>You may also like</h3>
                <p>Recommended from your recent shopping patterns and similar categories.</p>
            </div>
        </div>

        <div class="ai-mini-grid">
            <?php foreach ($recommendedProducts as $product): ?>
                <?php $imagePath = resolveProductImagePath($product['image'] ?? ''); ?>
                <div class="ai-mini-card">
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars((string) $product['name']) ?>">
                    <h4><?= htmlspecialchars((string) $product['name']) ?></h4>
                    <p><?= htmlspecialchars(substr((string) ($product['description'] ?? 'No description available'), 0, 70)) ?>...</p>
                    <strong>Rs <?= number_format((float) $product['price'], 2) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$includeAiAssistantAssets = false;
include 'includes/ai_assistant.php';
?>
<script defer src="assets/ai-assistant.js"></script>
</body>
</html>
