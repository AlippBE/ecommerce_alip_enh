<?php
session_start();
include '../koneksi.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

// Ambil nomor order dari GET
$order_number = isset($_GET['order']) ? htmlspecialchars($_GET['order']) : '';

// Redirect otomatis ke dashboard setelah 5 detik
$redirect_seconds = 5;
header("refresh:$redirect_seconds;url=dashboard.php");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Berhasil - LIPTHRIFT</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f5f5f5, #eaeaea);
    margin: 0;
}
.header {
    position: fixed;
    top: 0;
    width: 100%;
    height: 80px;
    background: white;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 100;
}
.page-title {
    font-size: 22px;
    font-weight: 700;
}
.logo {
    position: absolute;
    right: 30px;
    width: 60px;
    height: 60px;
    background: url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/g1uL78YCQy.png') center/cover;
}
.main {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding-top: 80px;
}
.card {
    background: white;
    padding: 50px 30px;
    border-radius: 15px;
    text-align: center;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    animation: fadeIn 0.5s ease;
}
.icon {
    width: 80px;
    height: 80px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    font-size: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.title {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 10px;
}
.desc {
    color: #666;
    margin-bottom: 25px;
}
.order-box {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
}
.order-box small { color:#777; }
.order-id {
    font-size: 20px;
    font-weight: bold;
    color: #af3131;
}
.footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    text-align: center;
    padding: 15px;
    background: #111;
    color: white;
    font-size: 14px;
}
@keyframes fadeIn {
    from {opacity:0; transform: translateY(20px);}
    to {opacity:1; transform: translateY(0);}
}
</style>
</head>
<body>
<div class="header">
    <div class="page-title">Pesanan Berhasil</div>
    <div class="logo"></div>
</div>

<div class="main">
    <div class="card">
        <div class="icon">✓</div>
        <div class="title">Pesanan Berhasil</div>
        <div class="desc">
            Terima kasih! Pesanan kamu sudah diproses.<br>
            Kamu akan diarahkan ke dashboard dalam <strong><?= $redirect_seconds ?> detik</strong>.
        </div>

        <?php if($order_number): ?>
        <div class="order-box">
            <small>NOMOR SERI</small><br>
            <div class="order-id"><?= $order_number ?></div>
        </div>
        <?php endif; ?>

        <a href="dashboard.php" class="btn" style="display:inline-block;padding:12px 25px;background:#10b981;color:white;border-radius:8px;text-decoration:none;font-weight:600;">Kembali ke Dashboard</a>
    </div>
</div>

<div class="footer">
    © 2026 LIPTHRIFT
</div>
</body>
</html>