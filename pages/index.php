<?php
session_start();
include '../includes/db.php';
require_once '../includes/product_helpers.php';
require_once '../includes/services/SearchService.php';
require_once '../includes/services/RecommendationService.php';

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$searchService = new SearchService();
$recommendationService = new RecommendationService();

if ($search !== '') {
    $searchData = $searchService->searchProducts($conn, $search, 24);
    $products = $searchData['products'];
} elseif ($category !== '') {
    $stmt = $conn->prepare('SELECT * FROM products WHERE category = ?');
    $stmt->execute([$category]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->query('SELECT * FROM products');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$recommendedProducts = $recommendationService->getRecommendedProducts(
    $conn,
    isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
    6
);

$success = '';
$error = '';

if (isset($_POST['subscribe'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email!';
    } else {
        $check = $conn->prepare('SELECT * FROM newsletter WHERE email = ?');
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $error = 'Email already subscribed!';
        } else {
            $stmt = $conn->prepare('INSERT INTO newsletter (email) VALUES (?)');

            if ($stmt->execute([$email])) {
                $success = 'Subscribed successfully!';
            } else {
                $error = 'Something went wrong!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>SmartCart AI Store</title>
<link rel="stylesheet" href="css/style.css">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<?php include 'includes/header.php'; ?>

<div class="container-fluid carousel bg-light px-0">
    <div class="row g-0 justify-content-end">
        <div class="col-12 col-lg-7 col-xl-9">
            <div class="header-carousel owl-carousel bg-light py-5">
                <div class="row g-0 header-carousel-item align-items-center">
                    <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                        <img src="../images/carousel-1.png" class="img-fluid w-100" alt="Image">
                    </div>
                    <div class="col-xl-6 carousel-content p-4">
                        <h4 class="text-uppercase fw-bold mb-4 wow fadeInRight" data-wow-delay="0.1s" style="letter-spacing: 3px;">AI Curated Deals</h4>
                        <h1 class="display-3 text-capitalize mb-4 wow fadeInRight" data-wow-delay="0.3s">Search smarter, shop faster, and discover better picks</h1>
                        <p class="text-dark wow fadeInRight" data-wow-delay="0.5s">Voice search, smart filters, and personalized recommendations are now built in.</p>
                        <a class="btn btn-primary rounded-pill py-3 px-5 wow fadeInRight" data-wow-delay="0.7s" href="#product-grid">Shop Now</a>
                    </div>
                </div>
                <div class="row g-0 header-carousel-item align-items-center">
                    <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                        <img src="../images/carousel-2.png" class="img-fluid w-100" alt="Image">
                    </div>
                    <div class="col-xl-6 carousel-content p-4">
                        <h4 class="text-uppercase fw-bold mb-4 wow fadeInRight" data-wow-delay="0.1s" style="letter-spacing: 3px;">Smart Shopping</h4>
                        <h1 class="display-3 text-capitalize mb-4 wow fadeInRight" data-wow-delay="0.3s">Ask for laptops under 50000 or shoes under 1000</h1>
                        <p class="text-dark wow fadeInRight" data-wow-delay="0.5s">The AI search bar understands budget and category together.</p>
                        <a class="btn btn-primary rounded-pill py-3 px-5 wow fadeInRight" data-wow-delay="0.7s" href="#product-grid">Explore Products</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5 col-xl-3 wow fadeInRight" data-wow-delay="0.1s">
            <div class="carousel-header-banner h-100">
                <img src="../images/header-img.jpg" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="Image">
                <div class="carousel-banner-offer">
                    <p class="bg-primary text-white rounded fs-5 py-2 px-4 mb-0 me-3">AI Assist</p>
                    <p class="text-primary fs-5 fw-bold mb-0">24/7 Chatbot</p>
                </div>
                <div class="carousel-banner">
                    <div class="carousel-banner-content text-center p-4">
                        <a href="#" class="d-block mb-2">Talk to the assistant</a>
                        <a href="#" class="d-block text-white fs-3">Find products, track orders,<br>and get quick help</a>
                        <span class="text-primary fs-5">Now available on every storefront page</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-4 bg-white">
    <div class="row text-center">
        <div class="col-6 col-md-4 col-lg-3 border-end">
            <i class="fas fa-comments fa-2x text-warning mb-2"></i>
            <h6>AI Chatbot</h6>
            <p class="small">Product help and order guidance</p>
        </div>
        <div class="col-6 col-md-4 col-lg-3 border-end">
            <i class="fas fa-microphone fa-2x text-warning mb-2"></i>
            <h6>Voice Search</h6>
            <p class="small">Speak your shopping request</p>
        </div>
        <div class="col-6 col-md-4 col-lg-3 border-end">
            <i class="fas fa-wand-magic-sparkles fa-2x text-warning mb-2"></i>
            <h6>Smart Search</h6>
            <p class="small">Understand budgets and categories</p>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <i class="fas fa-star fa-2x text-warning mb-2"></i>
            <h6>Recommendations</h6>
            <p class="small">Personalized product picks</p>
        </div>
    </div>
</div>

<div class="ai-search-summary" data-search-summary>
    <?php if ($search !== ''): ?>
        <?= count($products) ?> result(s) for "<?= htmlspecialchars($search) ?>"
    <?php endif; ?>
</div>

<div class="container" id="product-grid">
    <div class="products" data-search-results>
        <?php foreach ($products as $product): ?>
            <?php
            $image = $product['image'] ?? '';

            $imagePath = resolveProductImagePath($image);
            ?>

            <div class="card">
                <div class="badge">HOT</div>
                <div class="img-box">
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="desc"><?= htmlspecialchars(substr($product['description'] ?? 'No description available', 0, 60)) ?>...</p>
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
        <?php endforeach; ?>
    </div>
</div>

<div class="ai-section">
    <div class="ai-section__header">
        <div>
            <h2>You may also like</h2>
            <p>Personalized from your cart, order history, and store trends.</p>
        </div>
    </div>

    <div class="ai-mini-grid">
        <?php foreach ($recommendedProducts as $product): ?>
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

<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="banner-box position-relative overflow-hidden rounded">
                    <img src="../images/product-banner.jpg" class="img-fluid w-100" alt="">
                    <div class="banner-overlay">
                        <h3>Smart Picks This Week</h3>
                        <p>Fresh recommendations tuned by your browsing and cart activity.</p>
                        <a href="#product-grid" class="btn btn-primary">Explore Now</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="banner-box position-relative overflow-hidden rounded text-center">
                    <img src="../images/product-banner-2.jpg" class="img-fluid w-100" alt="">
                    <div class="banner-overlay dark text-center">
                        <h2>VOICE</h2>
                        <h4>Say what you want and let search do the rest</h4>
                        <a href="#product-grid" class="btn btn-light">Try It</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="ai-section" style="width:100%; margin:0;">
                <div class="ai-section__header">
                    <div>
                        <h3>Why this upgrade matters</h3>
                        <p>Customers can search naturally, ask for help instantly, and receive faster post-order updates.</p>
                    </div>
                </div>
                <div class="ai-mini-grid">
                    <div class="ai-mini-card">
                        <h4>WhatsApp alerts</h4>
                        <p>Orders trigger backend messages automatically after successful save.</p>
                    </div>
                    <div class="ai-mini-card">
                        <h4>Intent-driven discovery</h4>
                        <p>Search understands both category intent and price constraints.</p>
                    </div>
                    <div class="ai-mini-card">
                        <h4>Always-on support</h4>
                        <p>The floating assistant helps with products and order tracking.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <form method="POST" class="ai-section" style="width:100%; margin:0;">
                <div class="ai-section__header">
                    <div>
                        <h3>Subscribe</h3>
                        <p>Get product drops and offers.</p>
                    </div>
                </div>
                <input type="email" name="email" class="form-control mb-2" placeholder="Enter email" required>
                <button type="submit" name="subscribe" class="btn btn-warning w-100">Subscribe</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
<script>
$(document).ready(function () {
    $(".header-carousel").owlCarousel({
        items: 1,
        loop: true,
        autoplay: true,
        autoplayTimeout: 3000,
        smartSpeed: 1000,
        dots: true,
        nav: true
    });

    new WOW().init();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Success',
    text: '<?= $success ?>'
});
<?php endif; ?>

<?php if ($error): ?>
Swal.fire({
    icon: 'error',
    title: 'Oops...',
    text: '<?= $error ?>'
});
<?php endif; ?>
</script>

</body>
</html>
