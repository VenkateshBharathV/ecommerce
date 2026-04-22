<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
require_once '../includes/product_helpers.php';

$user_id = $_SESSION['user_id'];

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
<title>Checkout</title>

<style>
body {
    font-family: 'Segoe UI';
    background: #f1f3f6;
    margin: 0;
}

.container {
    width: 90%;
    max-width: 1000px;
    margin: 30px auto;
}

h2 {
    text-align: center;
}

/* PRODUCT */
.checkout-item {
    display: flex;
    background: white;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 10px;
    align-items: center;
}

.checkout-item img {
    width: 100px;
    height: 100px;
    object-fit: contain;
}

.details {
    flex: 1;
    padding: 0 20px;
}

.price {
    color: green;
}

/* SUMMARY */
.summary {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.total {
    font-size: 20px;
    font-weight: bold;
}

/* FORM */
.form-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

input, textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
}

button {
    width: 100%;
    padding: 12px;
    background: #fb641b;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}

.location-btn {
    background: #2874f0;
    margin-bottom: 10px;
}

.empty {
    text-align: center;
    padding: 40px;
}

.hint {
    font-size: 13px;
    color: #666;
    margin-top: -2px;
}

.error-text {
    color: #d32f2f;
    font-size: 13px;
    margin-top: 4px;
}
</style>

</head>
<body>

<div class="container">

<h2>🧾 Checkout</h2>

<?php if (empty($items)): ?>
    <div class="empty">Your cart is empty 😢</div>
<?php else: ?>

<!-- PRODUCTS -->
<?php foreach ($items as $item): 

    $image = $item['image'] ?? 'default.png';

    $imgPath = resolveProductImagePath($image);

    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
?>

<div class="checkout-item">
    <img src="<?= $imgPath ?>">

    <div class="details">
        <h3><?= htmlspecialchars($item['name']) ?></h3>
        <p class="price">
            ₹<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?>
        </p>
        <p>Subtotal: ₹<?= number_format($subtotal, 2) ?></p>
    </div>
</div>

<?php endforeach; ?>

<!-- TOTAL -->
<div class="summary">
    <div class="total">Total Amount: ₹<?= number_format($total, 2) ?></div>
</div>

<!-- ADDRESS -->
<div class="form-box">
    <h3>📦 Delivery Address</h3>

    <form method="POST" action="payment.php">

        <input type="text" name="name" placeholder="Full Name" required>

        <input
            type="tel"
            name="phone"
            id="phone"
            placeholder="Phone Number"
            inputmode="numeric"
            maxlength="13"
            pattern="^(\+91)?[6-9][0-9]{9}$"
            required
        >
        <div class="hint">Enter a valid Indian mobile number like 6303918486 or +916303918486. It will be saved as +91XXXXXXXXXX.</div>
        <div class="error-text" id="phone-error"></div>

        <button type="button" class="location-btn" onclick="getLocation()">
            📍 Use Current Location
        </button>

        <textarea name="address" id="address" placeholder="House No, Area, Street" required></textarea>

        <input type="text" name="city" id="city" placeholder="City" required>

        <input type="text" name="state" id="state" placeholder="State" required>

        <input type="text" name="pincode" id="pincode" placeholder="Pincode" required>

        <input type="hidden" name="total" value="<?= $total ?>">

        <button type="submit">Continue to Payment</button>
    </form>
</div>

<?php endif; ?>

</div>

<!-- LOCATION SCRIPT -->
<script>
function formatPhoneNumber(number) {
    let digits = (number || '').replace(/\D/g, '');

    // Accept +91XXXXXXXXXX, 91XXXXXXXXXX, 0XXXXXXXXXX, or raw 10 digits.
    if (digits.length === 12 && digits.startsWith('91')) {
        digits = digits.slice(2);
    } else if (digits.length === 11 && digits.startsWith('0')) {
        digits = digits.slice(1);
    }

    if (/^[6-9]\d{9}$/.test(digits)) {
        return '+91' + digits;
    }

    return '';
}

const checkoutForm = document.querySelector('form[action="payment.php"]');
const phoneInput = document.getElementById('phone');
const phoneError = document.getElementById('phone-error');

function validatePhoneField() {
    const formattedPhone = formatPhoneNumber(phoneInput.value);

    if (!formattedPhone) {
        phoneError.textContent = 'Please enter a valid 10-digit Indian mobile number.';
        return false;
    }

    phoneInput.value = formattedPhone;
    phoneError.textContent = '';
    return true;
}

phoneInput.addEventListener('input', function () {
    this.value = this.value.replace(/[^\d+]/g, '');
    phoneError.textContent = '';
});

phoneInput.addEventListener('blur', function () {
    const formattedPhone = formatPhoneNumber(this.value);
    if (formattedPhone) {
        this.value = formattedPhone;
        phoneError.textContent = '';
    }
});

checkoutForm.addEventListener('submit', function (event) {
    if (!validatePhoneField()) {
        event.preventDefault();
    }
});

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition);
    } else {
        alert("Geolocation not supported");
    }
}

function showPosition(position) {
    let lat = position.coords.latitude;
    let lon = position.coords.longitude;

    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`)
    .then(res => res.json())
    .then(data => {
        document.getElementById("address").value = data.display_name;
        document.getElementById("city").value = data.address.city || data.address.town || "";
        document.getElementById("state").value = data.address.state || "";
        document.getElementById("pincode").value = data.address.postcode || "";
    })
    .catch(() => alert("Location fetch failed"));
}
</script>

<?php include 'includes/ai_assistant.php'; ?>
</body>
</html>
