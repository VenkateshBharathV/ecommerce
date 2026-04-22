<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    display: flex;
    background: #f4f6f9;
    font-family: 'Segoe UI', sans-serif;
}

/* SIDEBAR */
.sidebar {
    width: 220px;
    height: 100vh;
    background: linear-gradient(180deg, #ff7a00, #ff3c00);
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
    border-radius: 8px;
    transition: 0.3s;
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
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* CARDS */
.card-box {
    border-radius: 15px;
    padding: 25px;
    color: white;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

/* GRADIENTS */
.bg-blue { background: linear-gradient(135deg, #36d1dc, #5b86e5); }
.bg-orange { background: linear-gradient(135deg, #ff7a00, #ff3c00); }
.bg-green { background: linear-gradient(135deg, #00c853, #64dd17); }
.bg-purple { background: linear-gradient(135deg, #7b1fa2, #e040fb); }
.bg-pink { background: linear-gradient(135deg, #ff4081, #ff80ab); }
.bg-dark { background: linear-gradient(135deg, #232526, #414345); }
.bg-yellow { background: linear-gradient(135deg, #f7971e, #ffd200); }
.bg-cyan { background: linear-gradient(135deg, #00c6ff, #0072ff); }

/* HOVER */
.card-box:hover {
    transform: scale(1.06);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

/* BUTTON */
.card-box a {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 15px;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 6px;
    text-decoration: none;
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
    <a href="admin_orders.php">Manage Orders</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
    <h4>Welcome Admin 👋</h4>
    <span><?= date("d M Y"); ?></span>
</div>

<div class="container mt-4">
<div class="row g-4">

<!-- TOP ROW -->
<div class="col-md-4">
    <div class="card-box bg-blue">
        <h4>Add Product</h4>
        <p>Create new product</p>
        <a href="add_product.php">Go</a>
    </div>
</div>

<div class="col-md-4">
    <div class="card-box bg-orange">
        <h4>Manage Products</h4>
        <p>Edit / Delete</p>
        <a href="manage_products.php">Go</a>
    </div>
</div>

<div class="col-md-4">
    <div class="card-box bg-green">
        <h4>Manage Orders</h4>
        <p>Track & update</p>
        <a href="admin_orders.php">Go</a>
    </div>
</div>

<!-- CATEGORY ROW -->
<div class="col-md-3">
    <div class="card-box bg-purple">
        <h4>👕 Fashion</h4>
        <p>Add fashion</p>
        <a href="add_fashion.php">Go</a>
    </div>
</div>

<div class="col-md-3">
    <div class="card-box bg-pink">
        <h4>📱 Mobiles</h4>
        <p>Add mobiles</p>
        <a href="add_mobiles.php">Go</a>
    </div>
</div>

<div class="col-md-3">
    <div class="card-box bg-dark">
        <h4>💻 Electronics</h4>
        <p>Add electronics</p>
        <a href="add_electronics.php">Go</a>
    </div>
</div>

<div class="col-md-3">
    <div class="card-box bg-yellow">
        <h4>⚡ Appliances</h4>
        <p>Add appliances</p>
        <a href="add_appliances.php">Go</a>
    </div>
</div>

<div class="col-md-3">
    <div class="card-box bg-cyan">
        <h4>📚 Books</h4>
        <p>Add books</p>
        <a href="add_books.php">Go</a>
    </div>
</div>

<div class="col-md-3">
    <div class="card-box bg-orange">
        <h4>🏏 Sports</h4>
        <p>Add sports items</p>
        <a href="add_sports.php">Go</a>
    </div>
</div>

</div>
</div>

</div>

<script>
document.querySelectorAll('.card-box').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = "scale(1.08)";
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = "scale(1)";
    });
});
</script>

</body>
</html>