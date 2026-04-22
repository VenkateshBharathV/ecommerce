<!DOCTYPE html>
<html>
<head>
<title>Fashion</title>

<link rel="stylesheet" href="css/style.css">

<style>

/* 🌈 GLOBAL */
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
    transition: 1s ease;
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

/* 🔥 TITLE */
.page-title {
    text-align: center;
    font-size: 30px;
    font-weight: bold;
    margin: 25px 0;
    color: #333;
}

/* 🔥 GRID */
.products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 25px;
    padding: 20px;
}

/* 🔥 CARD */
.card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    transition: 0.4s;
    position: relative;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 25px rgba(0,0,0,0.2);
}

/* BADGE */
.badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: linear-gradient(45deg, red, orange);
    color: white;
    padding: 5px 10px;
    font-size: 12px;
    border-radius: 6px;
}

/* IMAGE */
.img-box {
    height: 200px;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.img-box img {
    max-width: 100%;
    max-height: 100%;
    transition: 0.4s;
}

.card:hover img {
    transform: scale(1.1);
}

/* CONTENT */
.card-body {
    padding: 15px;
}

.card-body h3 {
    font-size: 16px;
    margin-bottom: 5px;
}

/* DESC */
.desc {
    font-size: 13px;
    color: #666;
    height: 40px;
    overflow: hidden;
}

/* PRICE */
.price {
    color: green;
    font-weight: bold;
    margin: 10px 0;
}

/* BUTTON */
.btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(45deg,#ff512f,#dd2476);
    border: none;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

.btn:hover {
    transform: scale(1.05);
}

/* FADE */
.fade {
    animation: fadeIn 1s ease;
}

@keyframes fadeIn {
    from {opacity:0; transform: translateY(20px);}
    to {opacity:1; transform: translateY(0);}
}

</style>
</head>

<body>

<?php include 'includes/header.php'; ?>

<!-- 🔥 CAROUSEL -->
<div class="carousel fade">
    <img id="banner" src="../images/c3.jpg">

    <div class="dots">
        <span class="dot active-dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</div>

    <!-- 🔥 TITLE -->
    <div class="page-title">👕 Fashion Collection</div>

<?php
$category_name = "Fashion   ";
include "category_template.php";
?>

<?php include 'includes/footer.php'; ?>

<!-- 🔥 SCRIPT -->
<script>
const images = [
    "../images/c1.jpg",
    "../images/c2.jpg",
    "../images/c3.jpg"
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