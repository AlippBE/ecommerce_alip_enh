<?php
session_start();
include '../koneksi.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// ================== SEARCH ==================
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $query = mysqli_query($koneksi, "SELECT * FROM products WHERE name LIKE '%$search%' ORDER BY id DESC");
} else {
    $query = mysqli_query($koneksi, "SELECT * FROM products ORDER BY id DESC LIMIT 12");
}
$products = mysqli_fetch_all($query, MYSQLI_ASSOC);

// ================== CART COUNT ==================
$cart = mysqli_query($koneksi, "SELECT SUM(quantity) as total FROM cart WHERE user_id='$user_id'");
$cart_data = mysqli_fetch_assoc($cart);
$cart_count = $cart_data['total'] ?? 0;

// ================== LOGOUT ==================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}

// ================== ADD TO CART ==================
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $qty = $_POST['qty'] ?? 1;

    $check = mysqli_query($koneksi, "SELECT * FROM cart WHERE user_id='$user_id' AND product_id='$product_id'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($koneksi, "UPDATE cart SET quantity = quantity + $qty WHERE user_id='$user_id' AND product_id='$product_id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id','$product_id','$qty')");
    }

    header("Location: dashboard.php?success=added");
    exit();
}

// ================== BUY NOW ==================
if (isset($_POST['buy_now'])) {
    $product_id = $_POST['product_id'];
    $qty = $_POST['qty'] ?? 1;

    $check = mysqli_query($koneksi, "SELECT * FROM cart WHERE user_id='$user_id' AND product_id='$product_id'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($koneksi, "UPDATE cart SET quantity = quantity + $qty WHERE user_id='$user_id' AND product_id='$product_id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id','$product_id','$qty')");
    }

    header("Location: keranjang.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LIPTHRIFT | Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
/* RESET & BODY */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { background:#f7f8fc; color:#111; }

/* HEADER */
.header { display:flex; justify-content:space-between; align-items:center; padding:15px 50px; background:#fff; box-shadow:0 4px 15px rgba(0,0,0,0.05); position:sticky; top:0; z-index:1000; }
.logo { width:60px; height:60px; background:url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/xgeewG0abB.png') center/cover; border-radius:50%; }
.nav { display:flex; align-items:center; gap:15px; }
.nav .btn { padding:10px 18px; border-radius:8px; font-weight:600; text-decoration:none; transition:0.3s; }
.btn-history { background:#f3f4f6; color:#111; }
.btn-history:hover { background:#e5e7eb; }
.btn-logout { background:#ef4444; color:#fff; }
.btn-logout:hover { background:#dc2626; }

/* CART */
.cart { position:relative; margin-right:10px; }
.cart img { width:30px; }
.cart-count { position:absolute; top:-8px; right:-8px; background:red; color:#fff; font-size:12px; width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; }

/* HERO SCROLLER */
.hero-scroller {
  position: relative;
    width: 100%;
    height: 300px;
    background-size: cover;
    background-position: center;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 30px;
}
.scroller-track {
  display: flex;
  width: calc(400%);
  animation: scroll 15s linear infinite;
}
.scroller-item {
  flex: 0 0 25%;
  height: 300px;
  background-size: cover;
  background-position: center;
  position: relative;
}
.scroller-content {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: rgba(0,0,0,0.4);
  padding: 15px 20px;
  border-radius: 10px;
  color: #fff;
}
.scroller-content h2 { font-size: 22px; font-weight: 700; margin-bottom: 5px;}
.scroller-content p { font-size: 14px; }

@keyframes scroll {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* SEARCH & PROMO */
.container { max-width:1200px; margin:20px auto; padding:0 20px; }
.search input { width:100%; padding:12px 15px; border-radius:10px; border:1px solid #ddd; margin-bottom:25px; }
.promo-box { background:linear-gradient(90deg,#ff7e5f,#feb47b); padding:20px; border-radius:12px; color:#fff; margin-bottom:30px; text-align:center; box-shadow:0 6px 20px rgba(0,0,0,0.08); }

/* GRID PRODUCTS */
.grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:25px; }
.card { background:#fff; border-radius:15px; padding:15px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.05); transition:0.3s; }
.card:hover { transform:translateY(-6px); box-shadow:0 10px 30px rgba(0,0,0,0.12); }
.card img { width:140px; height:140px; object-fit:contain; margin-bottom:10px; }
.card h3 { font-size:18px; font-weight:600; margin-bottom:5px; }
.price { color:#e11d48; font-weight:bold; margin-bottom:10px; }
.card form { display:flex; gap:8px; justify-content:center; }
.card input[type=number] { width:50px; padding:5px; border-radius:6px; border:1px solid #ccc; text-align:center; }

/* BUTTONS */
.btn-cart { background:#6366f1; color:#fff; }
.btn-buy { background:#f43f5e; color:#fff; }
button:hover { opacity:0.9; }

/* TOAST */
.toast { position:fixed; top:20px; right:20px; background:#22c55e; color:#fff; padding:12px 20px; border-radius:8px; z-index:2000; }

/* FOOTER */
.footer { text-align:center; padding:20px; background:#111; color:#fff; margin-top:40px; }

/* RESPONSIVE */
@media(max-width:768px){ .header{padding:15px 20px;} .hero-scroller{width:95%;} }
@media(max-width:500px){ .grid{grid-template-columns:1fr;} }
</style>
</head>
<body>

<!-- TOAST -->
<?php if(isset($_GET['success'])): ?>
<div class="toast">✅ Produk ditambahkan ke keranjang</div>
<script>
setTimeout(()=>{document.querySelector('.toast').style.display='none';},2000);
</script>
<?php endif; ?>

<!-- HEADER -->
<div class="header">
    <div class="logo"></div>
    <div class="nav">
        <a href="riwayat_transaksi.php" class="btn btn-history">Riwayat</a>
        <div class="cart">
            <a href="keranjang.php">
                <img src="https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/7QjtEQ4H7J.png">
                <?php if($cart_count>0): ?><div class="cart-count"><?= $cart_count ?></div><?php endif; ?>
            </a>
        </div>
        <a href="?logout=true" class="btn btn-logout">Logout</a>
    </div>
</div>

<!-- HERO SCROLLER -->
<div class="hero-scroller" style="background-image:url('gambarhero.jpeg');">
  <div class="scroller-track">
    <div class="scroller-item" style="background-image:url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-04-09/pro_banner_jaket1.jpg');">
      <div class="scroller-content">
        <h2>🔥 Jaket Premium Musim Ini!</h2>
        <p>Diskon hingga 50%</p>
      </div>
    </div>
    <div class="scroller-item" style="background-image:url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-04-09/pro_banner_jaket2.jpg');">
      <div class="scroller-content">
        <h2>🧥 Koleksi Jaket Stylish</h2>
        <p>Tampil trendi setiap hari</p>
      </div>
    </div>
    <div class="scroller-item" style="background-image:url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-04-09/pro_banner_jaket3.jpg');">
      <div class="scroller-content">
        <h2>✨ Promo Spesial Jaket</h2>
        <p>Diskon terbatas!</p>
      </div>
    </div>
    <div class="scroller-item" style="background-image:url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-04-09/pro_banner_jaket1.jpg');">
      <div class="scroller-content">
        <h2>🔥 Jaket Premium Musim Ini!</h2>
        <p>Diskon hingga 50%</p>
      </div>
    </div>
  </div>
</div>

<!-- PROMO -->
<div class="container">
    <div class="promo-box">
        <h2>🔥 Promo Spesial Hari Ini!</h2>
        <p>Dapatkan diskon hingga 50% untuk produk pilihan.</p>
    </div>

    <h2>👋 Halo, <?= htmlspecialchars($username) ?></h2>

    <!-- SEARCH -->
    <div class="search">
        <form method="GET">
            <input type="text" name="search" placeholder="🔍 Cari produk..." value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <h3>Rekomendasi Produk</h3>
    <div class="grid">
        <?php foreach($products as $p):
        $img = $p['image'] ? "../uploads/products/".$p['image'] : "https://via.placeholder.com/140";
        ?>
        <div class="card">
            <img src="<?= $img ?>">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <div class="price">Rp <?= number_format($p['price'],0,',','.') ?></div>
            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <input type="number" name="qty" value="1" min="1">
                <button class="btn-cart" name="add_to_cart">+ Keranjang</button>
                <button class="btn-buy" name="buy_now">Beli</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="footer">© 2026 LIPTHRIFT</div>
</body>
</html>