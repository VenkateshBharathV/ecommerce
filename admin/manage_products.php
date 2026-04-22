<?php
include '../includes/db.php';
require_once '../includes/product_helpers.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Products</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

/* TABLE CONTAINER */
.table-box {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: linear-gradient(90deg, #ff7a00, #ff3c00);
    color: white;
}

th, td {
    padding: 14px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f9f9f9;
}

/* IMAGE */
img {
    width: 60px;
    border-radius: 8px;
    transition: 0.3s;
}

img:hover {
    transform: scale(1.2);
}

/* ACTION BUTTONS */
.actions a {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    margin: 2px;
    transition: 0.3s;
}

.edit {
    background: linear-gradient(135deg, #00c853, #64dd17);
    color: white;
}

.delete {
    background: linear-gradient(135deg, #e53935, #ff1744);
    color: white;
}

.edit:hover {
    transform: scale(1.1);
}

.delete:hover {
    transform: scale(1.1);
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

<div class="topbar">
    <h4>Manage Products</h4>
    <span><?= date("d M Y"); ?></span>
</div>

<div class="table-box">

<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Price</th>
    <th>Description</th>
    <th>Image</th>
    <th>Actions</th>
</tr>

<?php foreach ($products as $product) : ?>
<tr>
    <td><?= $product['id']; ?></td>
    <td><?= htmlspecialchars($product['name']); ?></td>
    <td>₹<?= number_format($product['price'], 2); ?></td>
    <td><?= htmlspecialchars($product['description']); ?></td>

    <td>
        <?php
        $image = $product['image'];
        $imagePath = resolveProductImagePath($image);
        ?>
        <img src="<?= $imagePath; ?>">
    </td>

    <td class="actions">
        <a href="edit_product.php?id=<?= $product['id']; ?>" class="edit">Edit</a>
        <a href="#" onclick="deleteProduct(<?= $product['id']; ?>)" class="delete">Delete</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</div>

</div>

<!-- DELETE ALERT -->
<script>
function deleteProduct(id) {
    Swal.fire({
        title: 'Delete Product?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53935',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete_product.php?id=" + id;
        }
    });
}
</script>

</body>
</html>