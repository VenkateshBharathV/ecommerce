<?php
include '../includes/db.php';
session_start();

// Security check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check ID
if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = $_GET['id'];

// Get image name (to delete file also)
$stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {

    $image = $product['image'];

    // Delete image from uploads
    if (file_exists("../uploads/" . $image)) {
        unlink("../uploads/" . $image);
    }

    // Delete from DB
    $delete = $conn->prepare("DELETE FROM products WHERE id=?");
    $delete->execute([$id]);
}

// Redirect back
header("Location: manage_products.php");
exit();