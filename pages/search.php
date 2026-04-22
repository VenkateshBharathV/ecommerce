<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include '../includes/db.php';
require_once '../includes/product_helpers.php';
require_once '../includes/services/SearchService.php';

$query = trim((string) ($_GET['q'] ?? ''));
$mode = trim((string) ($_GET['mode'] ?? 'search'));

$service = new SearchService();

if ($mode === 'suggest') {
    echo json_encode([
        'suggestions' => $service->getSuggestions($conn, $query, 6),
    ]);
    exit();
}

$results = $service->searchProducts($conn, $query, 12);

ob_start();
foreach ($results['products'] as $product) {
    $image = $product['image'] ?? '';
    $imagePath = resolveProductImagePath($image);
    ?>
    <div class="card ai-fade-in">
        <div class="badge">AI Pick</div>
        <div class="img-box">
            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="card-body">
            <h3><?= htmlspecialchars($product['name']) ?></h3>
            <p class="desc"><?= htmlspecialchars(substr($product['description'] ?? 'No description available', 0, 80)) ?>...</p>
            <p class="price">Rs <?= number_format((float) $product['price'], 2) ?></p>
            <form method="POST" action="cart.php">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <div class="cart-row">
                    <input type="number" name="quantity" value="1" min="1">
                    <button type="submit" name="add_to_cart" class="btn">Add</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
$html = ob_get_clean();

echo json_encode([
    'intent' => $results['intent'],
    'count' => count($results['products']),
    'html' => $html,
    'products' => $results['products'],
]);
