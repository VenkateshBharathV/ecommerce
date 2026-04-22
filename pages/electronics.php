<!DOCTYPE html>
<html>
<head>
    <title>Electronics</title>
    <link rel="stylesheet" href="css/style.css">

<style>

/* 🌈 BACKGROUND */
body {
    margin: 0;
    font-family: 'Segoe UI', Arial;
    background: linear-gradient(to right, #eef2f3, #dfe9f3);
}

/* 🔥 CAROUSEL */
.carousel {
    width: 95%;
    margin: 20px auto;
    border-radius: 15px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.carousel img {
    width: 100%;
    height: 320px;
    object-fit: cover;
}

/* DOTS */
.dots {
    position: absolute;
    bottom: 10px;
    width: 100%;
    text-align: center;
}

.dot {
    height: 10px;
    width: 10px;
    margin: 3px;
    background: white;
    border-radius: 50%;
    display: inline-block;
    opacity: 0.5;
}

.active-dot {
    opacity: 1;
    background: #2874f0;
}

/* TITLE */
.page-title {
    text-align: center;
    font-size: 28px;
    font-weight: bold;
    margin: 25px 0;
}

/* GRID */
.products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    padding: 20px;
}

/* CARD */
.card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.card:hover {
    transform: translateY(-8px);
}

/* IMAGE */
.img-box {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fafafa;
}

.img-box img {
    max-width: 100%;
    max-height: 100%;
    transition: 0.4s;
}

.card:hover img {
    transform: scale(1.1);
}

/* BUTTON */
.btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(45deg,#2874f0,#1c5fd4);
    color: white;
    border: none;
    border-radius: 6px;
}

</style>
</head>

<body>

<?php include 'includes/header.php'; ?>

<!-- 🔥 CAROUSEL -->
<div class="carousel">
    <img id="banner" src="../images/e6.avif">

    <div class="dots">
        <span class="dot active-dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</div>

<!-- 🔥 TITLE -->
<div class="page-title">💻 Electronics Collection</div>

<?php
$category_name = "Electronics";
include "category_template.php";
?>

<?php include 'includes/footer.php'; ?>

<!-- 🔥 SCRIPT -->
<script>
const images = [
    "../images/e5.jpg",
    "../images/e6.avif",
    "../images/e7.jpg"
];

let i = 0;
const dots = document.querySelectorAll(".dot");

setInterval(() => {
    i = (i + 1) % images.length;
    document.getElementById("banner").src = images[i];

    dots.forEach(dot => dot.classList.remove("active-dot"));
    dots[i].classList.add("active-dot");

}, 3000);
</script>

</body>
</html>