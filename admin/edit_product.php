<?php
include('../includes/db.php');

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$id = $_GET['id'];

// Fetch product
$query = $conn->prepare("SELECT * FROM products WHERE id=?");
$query->execute([$id]);
$product = $query->fetch(PDO::FETCH_ASSOC);

// Update
if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // OLD image
    $old_image = $product['image'];

    // Check if new image uploaded
    if (!empty($_FILES['image']['name'])) {

        // ✅ unique image name (important)
        $image = time() . "_" . $_FILES['image']['name'];
        $tmp = $_FILES['image']['tmp_name'];

        // 🔥 Delete old image
        if (!empty($old_image) && file_exists("../uploads/" . $old_image)) {
            unlink("../uploads/" . $old_image);
        }

        // Upload new image
        move_uploaded_file($tmp, "../uploads/" . $image);

    } else {
        $image = $old_image; // keep old image
    }

    // Update DB
    $update = $conn->prepare("UPDATE products SET name=?, price=?, description=?, image=? WHERE id=?");
    $update->execute([$name, $price, $description, $image, $id]);

    header("Location: manage_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>

    <style>
        body {
            font-family: Arial;
            background: linear-gradient(135deg, #74ebd5, #ACB6E5);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            background: #fff;
            padding: 30px;
            width: 420px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
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
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        input:focus, textarea:focus {
            border-color: #007bff;
        }

        input[type="file"] {
            border: none;
        }

        .preview {
            text-align: center;
            margin-top: 10px;
        }

        .preview img {
            width: 100px;
            border-radius: 8px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #555;
        }

        .back:hover {
            color: #000;
        }
    </style>
</head>

<body>

<div class="card">
    <h2>Edit Product</h2>

    <form method="POST" enctype="multipart/form-data">

        <!-- Hidden ID -->
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

        <label>Product Name</label>
        <input type="text" name="name" value="<?php echo $product['name']; ?>" required>

        <label>Price</label>
        <input type="number" name="price" value="<?php echo $product['price']; ?>" required>

        <label>Description</label>
        <textarea name="description"><?php echo $product['description']; ?></textarea>

        <label>Current Image</label>
        <div class="preview">
            <img src="../uploads/<?php echo $product['image']; ?>">
        </div>

        <label>Upload New Image</label>
        <input type="file" name="image">

        <button type="submit" name="update">Update Product</button>
    </form>

    <a href="manage_products.php" class="back">⬅ Back to Products</a>
</div>

</body>
</html>