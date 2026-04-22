<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
require_once '../includes/product_helpers.php';

$user_id = $_SESSION['user_id'];

// ADD TO CART
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;

    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id=? AND product_id=?");
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->rowCount() > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id=? AND product_id=?");
        $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

// REMOVE ITEM
if (isset($_POST['remove_from_cart'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?");
    $stmt->execute([$user_id, $_POST['product_id']]);
}

// UPDATE QUANTITY
if (isset($_POST['update_quantity'])) {
    $qty = max(1, (int)$_POST['quantity']);
    $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?");
    $stmt->execute([$qty, $user_id, $_POST['product_id']]);
}

// FETCH CART ITEMS
$stmt = $conn->prepare("
    SELECT c.quantity, p.* 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id=?
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Your Cart</title>

<style>
body {
    font-family: 'Segoe UI';
    background: #f1f3f6;
    margin: 0;
}

.container {
    width: 85%;
    margin: 30px auto;
}

h2 {
    text-align: center;
}

/* CART CARD */
.cart-item {
    display: flex;
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: 0.3s;
}

.cart-item:hover {
    transform: translateY(-3px);
}

/* IMAGE */
.cart-item img {
    width: 130px;
    height: 130px;
    border-radius: 10px;
    object-fit: contain;
    background: #f9f9f9;
}

/* DETAILS */
.details {
    flex: 1;
    padding: 0 25px;
}

.name {
    font-size: 20px;
    font-weight: bold;
}

.price {
    color: #2e7d32;
    font-size: 18px;
    margin: 8px 0;
}

/* QTY */
.qty-box {
    display: flex;
    align-items: center;
    margin-top: 10px;
}

.qty-box input {
    width: 60px;
    padding: 5px;
    text-align: center;
}

/* BUTTONS */
button {
    padding: 10px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
}

button[name="update_quantity"] {
    background: #2874f0;
    color: white;
}

.remove-btn {
    background: #ff3d00;
    color: white;
}

/* SUMMARY */
.summary {
    background: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.total {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 15px;
}

.checkout-btn {
    display: block;
    width: 100%;
    padding: 14px;
    background: linear-gradient(to right, #ff512f, #dd2476);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-size: 16px;
}

.checkout-btn:hover {
    opacity: 0.9;
}

.back-link {
    display: block;
    margin-top: 10px;
    color: #2874f0;
    text-decoration: none;
}

.empty {
    text-align: center;
    padding: 50px;
    font-size: 18px;
}
</style>

</head>
<body>

<div class="container">

<h2>🛒 Your Cart</h2>

<?php if (empty($items)): ?>
    <div class="empty">Your cart is empty 😢</div>
<?php else: ?>

<?php foreach ($items as $item): 
    
    // IMAGE FIX
    $image = $item['image'] ?? 'default.png';

    $imgPath = resolveProductImagePath($image);

    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
?>

<div class="cart-item">

    <img src="<?= $imgPath ?>">

    <div class="details">
        <div class="name"><?= htmlspecialchars($item['name']) ?></div>

        <div class="price">
            ₹<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?>
        </div>

        <div>Subtotal: ₹<?= number_format($subtotal, 2) ?></div>

        <!-- UPDATE -->
        <form method="POST" class="qty-box">
            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1">
            <button name="update_quantity">Update</button>
        </form>
    </div>

    <!-- REMOVE -->
    <form method="POST">
        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
        <button class="remove-btn" name="remove_from_cart">Remove</button>
    </form>

</div>

<?php endforeach; ?>

<!-- SUMMARY -->
<div class="summary">
    <div class="total">Total: ₹<?= number_format($total, 2) ?></div>

    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
    <a href="index.php" class="back-link">← Back to Shop</a>
</div>

<?php endif; ?>

</div>

<?php include 'includes/ai_assistant.php'; ?>
</body>
</html>
