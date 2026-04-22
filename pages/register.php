<?php
include('../includes/db.php');
session_start();

if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<script>alert('Email is already registered!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$email, $password, $role]);

        $_SESSION['user_id'] = $conn->lastInsertId();
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>

<style>
/* 🌈 BACKGROUND */
body {
    margin: 0;
    height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #ff7a00, #ffffff);
}

/* 💎 GLASS CARD */
.register-container {
    backdrop-filter: blur(15px);
    background: rgba(255, 255, 255, 0.2);
    padding: 40px;
    border-radius: 15px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    animation: fadeIn 0.6s ease;
}

/* ✨ TITLE */
h2 {
    text-align: center;
    color: #333;
    margin-bottom: 25px;
}

/* 🧾 LABEL */
label {
    font-size: 14px;
    color: #333;
}

/* 🧠 INPUT STYLE */
input {
    width: 100%;
    padding: 12px;
    margin: 8px 0 15px;
    border-radius: 8px;
    border: none;
    outline: none;
    background: rgba(255,255,255,0.8);
    transition: 0.3s;
}

/* 🔥 INPUT FOCUS */
input:focus {
    box-shadow: 0 0 8px #ff7a00;
}

/* 🚀 BUTTON */
button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(90deg, #ff7a00, #ff9a3c);
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

/* 🔥 HOVER */
button:hover {
    transform: scale(1.03);
    background: linear-gradient(90deg, #e96b00, #ff7a00);
}

/* ❗ ERROR */
.error-message {
    color: red;
    text-align: center;
}

/* 🎬 ANIMATION */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 📱 MOBILE */
@media (max-width: 500px) {
    .register-container {
        margin: 20px;
        padding: 25px;
    }
}
</style>

</head>

<body>

<div class="register-container">
    <h2>Create Account</h2>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" placeholder="Enter your email" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>

        <button type="submit" name="register">Register</button>
    </form>

    <?php if (isset($error_message)): ?>
        <p class="error-message"><?= htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

</div>

</body>
</html>