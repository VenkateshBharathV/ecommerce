<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success = "";

if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $image = "";

    if (!empty($_FILES['image']['name'])) {
        $image = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['image']['name']);
        $tmp = $_FILES['image']['tmp_name'];

        move_uploaded_file($tmp, "../uploads/" . $image);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $description, $image]);

    $success = "Product added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Product</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial;
}

body {
    display: flex;
    background: #f1f3f6;
}

/* SIDEBAR */
.sidebar {
    width: 220px;
    height: 100vh;
    background: #2874f0;
    color: white;
    padding: 20px;
    position: fixed;
}

.sidebar h2 {
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    padding: 12px;
    color: white;
    text-decoration: none;
    margin-bottom: 10px;
    border-radius: 5px;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.2);
}

/* MAIN */
.main {
    margin-left: 220px;
    width: 100%;
}

/* TOPBAR */
.topbar {
    background: white;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* FORM CARD */
.form-box {
    max-width: 500px;
    margin: 40px auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

label {
    font-weight: bold;
    margin-top: 10px;
    display: block;
}

input, textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* BUTTON */
button {
    width: 100%;
    padding: 12px;
    background: #2874f0;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background: #1c5fd4;
}

/* IMAGE PREVIEW */
.preview {
    text-align: center;
}

.preview img {
    width: 120px;
    margin-top: 10px;
    display: none;
}

/* SUCCESS */
.success {
    text-align: center;
    color: green;
    margin-top: 10px;
}

/* RESPONSIVE */
@media(max-width: 768px) {
    .sidebar {
        width: 180px;
    }
    .main {
        margin-left: 180px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>🛒 Admin</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="add_product.php">Add Product</a>
    <a href="manage_products.php">Manage Products</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <h3>Add Product</h3>
        <span><?= date("d M Y"); ?></span>
    </div>

    <!-- FORM -->
    <div class="form-box">
        <h2>Add Product</h2>

        <form method="POST" enctype="multipart/form-data">

            <label>Product Name</label>
            <input type="text" name="name" required>

            <label>Price</label>
            <input type="number" step="0.01" name="price" required>

            <label>Description</label>
            <textarea name="description" required></textarea>

            <label>Upload Image</label>
            <input type="file" name="image" id="image" required>

            <div class="preview">
                <img id="previewImg">
            </div>

            <button type="submit" name="add_product">Add Product</button>
        </form>

        <?php if ($success): ?>
            <div class="success"><?= $success; ?></div>
        <?php endif; ?>
    </div>

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