<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/db.php';
require_once '../includes/product_helpers.php';
require_once '../includes/services/RecommendationService.php';

$category = trim($category_name);

$stmt = $conn->prepare('SELECT * FROM products WHERE category = ?');
$stmt->execute([$category]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recommendationService = new RecommendationService();
$similarProducts = $recommendationService->getSimilarProducts(
    $conn,
    $category,
    array_map(static fn(array $product): int => (int) $product['id'], array_slice($products, 0, 2)),
    4
);
?>

<div class="products" data-search-results>
<?php foreach ($products as $product): ?>
    <?php
    $image = $product['image'] ?? '';

    $imagePath = resolveProductImagePath($image);
    ?>

    <div class="card">
        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p>Rs <?= number_format((float) $product['price'], 2) ?></p>
        <form method="POST" action="cart.php">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="number" name="quantity" value="1" min="1">
            <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
        </form>
    </div>
<?php endforeach; ?>
</div>

<div class="ai-section">
    <div class="ai-section__header">
        <div>
            <h3>You may also like</h3>
            <p>More picks from the <?= htmlspecialchars($category) ?> aisle.</p>
        </div>
    </div>

    <div class="ai-mini-grid">
        <?php foreach ($similarProducts as $product): ?>
            <?php
            $image = $product['image'] ?? '';
            $imagePath = resolveProductImagePath($image);
            ?>
            <div class="ai-mini-card">
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <h4><?= htmlspecialchars($product['name']) ?></h4>
                <p><?= htmlspecialchars(substr($product['description'] ?? 'No description available', 0, 70)) ?>...</p>
                <strong>Rs <?= number_format((float) $product['price'], 2) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>
