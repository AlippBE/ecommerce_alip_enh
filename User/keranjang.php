<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];

if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = $_POST['quantity'];
    if ($quantity > 0) {
        mysqli_query($koneksi, "UPDATE cart SET quantity = '$quantity' WHERE id = '$cart_id' AND user_id = '$user_id'");
    }
    header("Location: keranjang.php");
    exit();
}

if (isset($_GET['delete'])) {
    $cart_id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM cart WHERE id = '$cart_id' AND user_id = '$user_id'");
    header("Location: keranjang.php");
    exit();
}

if (isset($_POST['checkout'])) {
    header("Location: checkout.php");
    exit();
}

$cart_query = mysqli_query($koneksi, "SELECT c.*, p.name, p.price, p.image FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = '$user_id'");
$cart_items = mysqli_fetch_all($cart_query, MYSQLI_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}

$cart_count_query = mysqli_query($koneksi, "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'");
$cart_count_data = mysqli_fetch_assoc($cart_count_query);
$cart_count = $cart_count_data['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Keranjang - LIPTHRIFT</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f5f5f5; min-height: 100vh; }
        .main-container { width: 100%; max-width: 1440px; margin: 0 auto; }
        
        .header {
            width: 100%;
            height: 100px;
            background-color: #ffffff;
            position: sticky;
            top: 0;
            box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.15);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }
        .logo-container { display: flex; align-items: center; flex-shrink: 0; }
        .logo {
            width: 80px;
            height: 80px;
            background-image: url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/xgeewG0abB.png');
            background-size: cover;
            border-radius: 50%;
        }
        .nav-buttons { display: flex; align-items: center; gap: 15px; margin-left: auto; }
        .btn-nav {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
            transition: background-color 0.3s;
        }
        .btn-nav-white { background-color: #ffffff; color: #000000; }
        .btn-nav-white:hover { background-color: #f5f5f5; }
        .btn-nav-red { background-color: #af3131; color: #ffffff; }
        .btn-nav-red:hover { background-color: #9a2a2a; }
        .cart-icon {
            width: 35px;
            height: 28px;
            background-image: url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/7QjtEQ4H7J.png');
            background-size: cover;
            position: relative;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #af3131;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        .user-icon {
            width: 40px;
            height: 40px;
            background-image: url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/FA1UKDtQWa.png');
            background-size: cover;
            border-radius: 50%;
        }
        
        .main-content { margin-top: 20px; padding: 40px; }
        
        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background-color: #ffffff;
            color: #000000;
            text-decoration: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .back-btn:hover { background-color: #f5f5f5; }
        .back-btn svg { width: 20px; height: 20px; }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .page-title::before {
            content: '';
            width: 6px;
            height: 31px;
            background-color: #aa6d11;
            border-radius: 15px;
        }
        
        .cart-container { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .cart-items {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        .cart-item:last-child { border-bottom: none; }
        .item-checkbox { width: 20px; height: 20px; cursor: pointer; }
        .item-image {
            width: 100px;
            height: 100px;
            background-color: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
        }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-details { flex: 1; }
        .item-name { font-size: 18px; font-weight: 600; color: #000000; margin-bottom: 5px; }
        .item-price { font-size: 16px; color: #af3131; font-weight: 600; }
        .item-quantity { display: flex; align-items: center; gap: 10px; }
        .qty-btn {
            width: 30px;
            height: 30px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn:hover { background-color: #e5e5e5; }
        .qty-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .delete-btn {
            padding: 8px 16px;
            background-color: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .delete-btn:hover { background-color: #fecaca; }
        
        .cart-summary {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        .summary-title { font-size: 24px; font-weight: 700; color: #000000; margin-bottom: 20px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 16px; color: #666; }
        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e5e5;
            font-size: 20px;
            font-weight: 700;
            color: #af3131;
        }
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background-color: #3b82f6;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .checkout-btn:hover { background-color: #2563eb; }
        .checkout-btn:disabled { background-color: #ccc; cursor: not-allowed; }
        
        .empty-cart { text-align: center; padding: 60px 20px; }
        .empty-cart-icon { font-size: 64px; margin-bottom: 20px; }
        .empty-cart-text { font-size: 18px; color: #666; margin-bottom: 20px; }
        .shop-btn {
            padding: 12px 30px;
            background-color: #af3131;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .footer {
            background-color: #1a1a1a;
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
            margin-top: 50px;
        }
        
        @media (max-width: 768px) {
            .header { padding: 0 20px; height: 80px; }
            .logo { width: 60px; height: 60px; }
            .cart-container { grid-template-columns: 1fr; }
            .btn-nav span { display: none; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="logo-container">
                <div class="logo"></div>
            </div>
            <div class="nav-buttons">
                <a href="riwayat_transaksi.php" class="btn-nav btn-nav-white">
                    <span>Riwayat Transaksi</span>
                </a>
                <div class="cart-icon">
                    <a href="keranjang.php">
                        <?php if($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="user-icon"></div>
                <a href="?logout=true" class="btn-nav btn-nav-red">
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <!-- Back to Dashboard Button -->
            <a href="dashboard.php" class="back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Kembali ke Dashboard</span>
            </a>
            
            <h1 class="page-title">Keranjang (<?php echo count($cart_items); ?> Item)</h1>
            
            <?php if(count($cart_items) > 0): ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach($cart_items as $item): ?>
                    <div class="cart-item">
                        <input type="checkbox" class="item-checkbox" checked>
                        <div class="item-image">
                            <?php if($item['image']): ?>
                                <img src="../uploads/products/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/100?text=No+Image" alt="No Image">
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-price">Rp. <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                        </div>
                        <form method="POST" action="" style="display: flex; align-items: center; gap: 10px;">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <button type="button" class="qty-btn" onclick="updateQty(this, -1)">-</button>
                            <input type="number" name="quantity" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" onchange="this.form.submit()">
                            <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                        </form>
                        <a href="?delete=<?php echo $item['id']; ?>" class="delete-btn" onclick="return confirm('Hapus dari keranjang?')">Hapus</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2 class="summary-title">Ringkasan Pesanan</h2>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rp. <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Ongkos Kirim</span>
                        <span>Rp. **</span>
                    </div>
                    <div class="summary-total">
                        <span>Total Barang</span>
                        <span>Rp. <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    <form method="POST" action="">
                        <button type="submit" name="checkout" class="checkout-btn">Checkout</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="cart-items">
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <p class="empty-cart-text">Keranjang belanja Anda masih kosong</p>
                    <a href="dashboard.php" class="shop-btn">Mulai Belanja</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 LIPTHRIFT Developed by Lip</p>
        </div>
    </div>
    
    <script>
        function updateQty(btn, change) {
            const form = btn.closest('form');
            const input = form.querySelector('.qty-input');
            let newValue = parseInt(input.value) + change;
            if (newValue < 1) newValue = 1;
            input.value = newValue;
            form.submit();
        }
    </script>
</body>
</html>