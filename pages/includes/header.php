<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$searchValue = htmlspecialchars((string) ($search ?? ''));
$categoryValue = htmlspecialchars((string) ($category ?? ($_GET['category'] ?? '')));
$trackOrderId = (int) ($_SESSION['last_order_id'] ?? 0);
?>
<div class="navbar ai-navbar">
    <a href="index.php" class="ai-brand">
        <strong>SmartCart</strong>
        <span>Search, chat, voice, and premium recommendations</span>
    </a>

    <div class="ai-search" data-smart-search>
        <form method="GET" action="index.php">
            <button type="button" class="ai-search__icon" aria-label="Search icon">&#128269;</button>
            <input type="text" name="search" placeholder="Try: shoes under 1000" value="<?= $searchValue ?>">
            <?php if ($categoryValue !== ''): ?>
                <input type="hidden" name="category" value="<?= $categoryValue ?>">
            <?php endif; ?>
            <button type="button" class="ai-search__voice" data-voice-search aria-label="Voice search">&#127908;</button>
            <button type="submit" class="ai-search__submit" aria-label="Run smart search">&#10140;</button>
        </form>
        <div class="ai-search__suggestions" data-search-suggestions hidden></div>
    </div>

    <div class="nav-links ai-nav-links">
        <a href="orders.php">My Orders</a>
        <a href="<?= $trackOrderId > 0 ? 'track_order.php?order_id=' . $trackOrderId : 'orders.php' ?>">Track</a>
        <a href="cart.php">Cart</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="ai-nav-links__cta">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php" class="ai-nav-links__cta">Register</a>
        <?php endif; ?>
    </div>
</div>

<div class="categories ai-categories">
    <a href="index.php" class="cat-item">Home</a>
    <a href="fashion.php" class="cat-item">Fashion</a>
    <a href="mobiles.php" class="cat-item">Mobiles</a>
    <a href="electronics.php" class="cat-item">Electronics</a>
    <a href="appliances.php" class="cat-item">Appliances</a>
    <a href="books.php" class="cat-item">Books</a>
    <a href="sports.php" class="cat-item">Sports</a>
</div>
