<?php
include '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

/* ✅ FIX: category safe */
$category = $category_name ?? 'Unknown';

$success = "";

if (isset($_POST['add_product'])) {

    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';

    // IMAGE
    $image = "";

    if (!empty($_FILES['image']['name'])) {

        $image = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['image']['name']);
        $uploadPath = "../uploads/" . $image;

        if (!is_dir("../uploads")) {
            mkdir("../uploads", 0777, true);
        }

        move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);
    }

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO products (name, price, description, category, image)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([$name, $price, $description, $category, $image]);

    $success = "$category product added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add <?= htmlspecialchars($category) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* 🌈 BACKGROUND */
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #ff7a00, #ffffff);
}

/* 💎 FORM CARD */
.form-box {
    max-width: 520px;
    margin: 60px auto;
    padding: 35px;
    border-radius: 18px;
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(15px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    animation: fadeIn 0.6s ease;
}

/* 🧾 TITLE */
h2 {
    text-align: center;
    margin-bottom: 25px;
}

/* INPUTS */
input, textarea {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 10px;
    border: none;
    outline: none;
    background: rgba(255,255,255,0.85);
    transition: 0.3s;
}

/* FOCUS EFFECT */
input:focus, textarea:focus {
    box-shadow: 0 0 8px #ff7a00;
}

/* BUTTON */
button {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(90deg, #ff7a00, #ff3c00);
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    transform: scale(1.05);
}

/* IMAGE PREVIEW */
.preview {
    text-align: center;
}

.preview img {
    width: 130px;
    border-radius: 10px;
    margin-top: 10px;
    display: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* SUCCESS MESSAGE */
.success {
    text-align: center;
    color: green;
    margin-top: 15px;
    font-weight: bold;
}

/* BACK BUTTON */
.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    text-decoration: none;
    font-weight: bold;
    color: #333;
}

/* ANIMATION */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* MOBILE */
@media(max-width:500px){
    .form-box {
        margin: 20px;
        padding: 25px;
    }
}

</style>
</head>

<body>

<div class="form-box">

    <a href="dashboard.php" class="back-btn">← Back</a>

    <h2>⚡ Add <?= htmlspecialchars($category) ?> Product</h2>

    <form method="POST" enctype="multipart/form-data">

        <input type="text" name="name" placeholder="Product Name" required>

        <input type="number" name="price" placeholder="Price" required>

        <textarea name="description" placeholder="Description"></textarea>

        <input type="file" name="image" id="image" required>

        <!-- IMAGE PREVIEW -->
        <div class="preview">
            <img id="previewImg">
        </div>

        <button type="submit" name="add_product">
            Add <?= htmlspecialchars($category) ?>
        </button>

    </form>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

</div>

<script>
document.getElementById("image").onchange = function (e) {
    const file = e.target.files[0];
    const preview = document.getElementById("previewImg");

    if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";
    }
};
</script>

</body>
</html>