<?php
session_start();
include '../koneksi.php';

// ================== PROTEKSI LOGIN ==================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

if (!isset($_SESSION['id'])) {
    die("Session user tidak valid");
}

$user_id = (int) $_SESSION['id'];

// ================== AMBIL TRANSAKSI ==================
$stmt = $koneksi->prepare("
    SELECT * FROM transactions 
    WHERE user_id=? 
    ORDER BY created_at DESC
");

if (!$stmt) {
    die("Prepare failed: " . $koneksi->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ================== HITUNG CART ==================
$stmt = $koneksi->prepare("
    SELECT SUM(quantity) as total 
    FROM cart 
    WHERE user_id=?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result()->fetch_assoc();
$cart_count = isset($cart_result['total']) ? (int)$cart_result['total'] : 0;
$stmt->close();

// ================== LOGOUT ==================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}

// ================== HELPER STATUS ==================
function statusLabel($status) {
    $status = strtolower($status);

    switch($status){
        case 'pending': return 'Menunggu';
        case 'success':
        case 'completed':
        case 'settlement':
        case 'capture':
            return 'Berhasil';
        case 'cancel':
        case 'failed':
        case 'expire':
            return 'Dibatalkan';
        default:
            return ucfirst($status);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Transaksi - LIPTHRIFT</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body { font-family: 'Inter', sans-serif; margin:0; background:#f4f6f9; }

.header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 40px;
    background:white;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}

.logo {
    width:60px;
    height:60px;
    background:url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/xgeewG0abB.png') center/cover;
    border-radius:50%;
}

.nav { display:flex; align-items:center; gap:15px; }

.btn {
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
}

.btn-white { background:#eee; color:black; }
.btn-red { background:#af3131; color:white; }

.cart { position:relative; }
.cart img { width:28px; }

.cart-count {
    position:absolute;
    top:-8px;
    right:-8px;
    background:#af3131;
    color:white;
    font-size:11px;
    width:20px;
    height:20px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
}

.container { padding:40px; }

.title {
    font-size:26px;
    font-weight:700;
    margin-bottom:25px;
}

.card {
    background:white;
    border-radius:12px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
    transition:0.3s;
}

.card:hover { transform:translateY(-3px); }

.top {
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
}

.order { font-weight:600; }

.date {
    font-size:13px;
    color:#777;
}

.status {
    padding:5px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.pending { background:#fef3c7; color:#92400e; }
.success, .completed, .settlement, .capture {
    background:#d1fae5;
    color:#065f46;
}
.cancel, .failed, .expire {
    background:#fee2e2;
    color:#991b1b;
}

.bottom {
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.total {
    font-weight:bold;
    color:#af3131;
}

.empty {
    text-align:center;
    padding:60px;
    color:#777;
}

.empty a {
    display:inline-block;
    margin-top:15px;
    padding:10px 20px;
    background:#af3131;
    color:white;
    border-radius:6px;
    text-decoration:none;
}

.footer {
    text-align:center;
    padding:20px;
    background:#111;
    color:white;
    margin-top:40px;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div class="logo"></div>

    <div class="nav">
        <a href="dashboard.php" class="btn btn-white">Dashboard</a>

        <div class="cart">
            <a href="keranjang.php">
                <img src="https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/7QjtEQ4H7J.png">
                <?php if($cart_count > 0): ?>
                    <div class="cart-count"><?= $cart_count ?></div>
                <?php endif; ?>
            </a>
        </div>

        <a href="?logout=true" class="btn btn-red">Logout</a>
    </div>
</div>

<!-- CONTENT -->
<div class="container">
    <div class="title">Riwayat Transaksi</div>

    <?php if(count($transactions) > 0): ?>
        <?php foreach($transactions as $trx): 
            $statusClass = strtolower($trx['status']);
        ?>
        <div class="card">
            <div class="top">
                <div>
                    <div class="order">
                        #<?= htmlspecialchars($trx['order_number'] ?? 'NO-ID') ?>
                    </div>
                    <div class="date">
                        <?= date('d M Y H:i', strtotime($trx['created_at'])) ?>
                    </div>
                </div>

                <div class="status <?= htmlspecialchars($statusClass) ?>">
                    <?= statusLabel($trx['status']) ?>
                </div>
            </div>

            <div class="bottom">
                <div>
                    <?= isset($trx['total_items']) ? (int)$trx['total_items'] : 1 ?> item
                </div>

                <div class="total">
                    Rp <?= number_format((int)$trx['total_price'],0,',','.') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="empty">
            📦 Belum ada transaksi<br>
            <a href="dashboard.php">Mulai Belanja</a>
        </div>
    <?php endif; ?>
</div>

<div class="footer">
    © 2026 LIPTHRIFT
</div>

</body>
</html>