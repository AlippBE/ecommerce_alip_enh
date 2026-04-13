<?php
session_start();
include '../koneksi.php';

// Proteksi login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../Auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];
$error = "";

// Ambil cart
$cart_query = mysqli_query($koneksi, "
    SELECT c.*, p.name, p.price 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = '$user_id'
");
$cart_items = mysqli_fetch_all($cart_query, MYSQLI_ASSOC);

// Kalau kosong
if (count($cart_items) == 0) {
    header("Location: keranjang.php");
    exit();
}

// Hitung total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// ONGKIR OTOMATIS
$ongkir = ($total >= 100000) ? 0 : 10000;

// DISKON OTOMATIS
$diskon = ($total >= 200000) ? $total * 0.1 : 0;

// GRAND TOTAL
$grand_total = $total + $ongkir - $diskon;

// Submit checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama = trim($_POST['nama']);
    $no_telp = trim($_POST['no_telp']);
    $alamat = trim($_POST['alamat']);
    $metode = $_POST['metode_pembayaran'];

    // VALIDASI
    if ($nama == "" || $no_telp == "" || $alamat == "" || $metode == "") {
        $error = "❌ Semua field wajib diisi!";
    } elseif (!preg_match('/^[0-9]+$/', $no_telp)) {
        $error = "❌ No HP harus angka!";
    } elseif ($metode == 'COD' && $grand_total > 150000) {
        $error = "❌ COD hanya untuk pesanan maksimal Rp 150.000";
    } else {

        $nama = mysqli_real_escape_string($koneksi, $nama);
        $no_telp = mysqli_real_escape_string($koneksi, $no_telp);
        $alamat = mysqli_real_escape_string($koneksi, $alamat);
        $metode = mysqli_real_escape_string($koneksi, $metode);

        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        mysqli_begin_transaction($koneksi);

        try {
            // Insert transaksi
            mysqli_query($koneksi, "
                INSERT INTO transactions 
                (user_id, order_number, total_price, payment_method, customer_name, customer_phone, customer_address, status) 
                VALUES 
                ('$user_id','$order_number','$grand_total','$metode','$nama','$no_telp','$alamat','pending')
            ");

            $trx_id = mysqli_insert_id($koneksi);

            // Insert detail transaksi
            foreach ($cart_items as $item) {
                $pid = $item['product_id'];
                $qty = $item['quantity'];
                $price = $item['price'];
                $subtotal = $price * $qty;

                mysqli_query($koneksi, "
                    INSERT INTO transaction_details 
                    (transaction_id, product_id, quantity, price, subtotal)
                    VALUES ('$trx_id','$pid','$qty','$price','$subtotal')
                ");

                // Kurangi stok produk
                mysqli_query($koneksi, "
                    UPDATE products SET stock = stock - $qty WHERE id='$pid'
                ");
            }

            // Kosongkan cart
            mysqli_query($koneksi, "DELETE FROM cart WHERE user_id='$user_id'");

            mysqli_commit($koneksi);

            // Redirect ke halaman konfirmasi
            header("Location: konfirmasi_pesanan.php?order=$order_number");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error = "❌ Gagal membuat pesanan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Checkout</title>
<style>
body { font-family: Arial; background:#f4f6f9; }
.box {
    max-width: 700px;
    margin: 50px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
}
input, textarea, select {
    width:100%;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
}
.summary {
    background:#fafafa;
    padding:15px;
    border-radius:8px;
}
button {
    padding:15px;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}
button.submit { background:#af3131; }
button.back { background:#777; }
.flex { display:flex; gap:10px; }
.flex button { width:48%; }

.error {
    background:#ffdddd;
    color:#a00;
    padding:10px;
    border-radius:6px;
    margin-bottom:15px;
}
</style>
</head>
<body>
<div class="box">
<h2>Checkout</h2>

<?php if($error): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>

<form method="POST" onsubmit="return confirm('Yakin mau buat pesanan?')">

<label>Metode Pembayaran</label>
<select name="metode_pembayaran" id="metode" required onchange="tampilkanRekening()">
    <option value="">Pilih</option>
    <option value="M-Banking">M-Banking</option>
    <option value="E-Wallet">E-Wallet</option>
    <option value="COD">COD</option>
</select>

<div id="infoRekening" style="display:none; background:#eef; padding:10px; border-radius:6px;">
    Nomor Pembayaran: <b>089512262810</b>
</div>

<label>Nama</label>
<input type="text" name="nama" required>

<label>No HP</label>
<input type="text" name="no_telp" required>

<label>Alamat</label>
<textarea name="alamat" required></textarea>

<div class="summary">
    <h3>Ringkasan</h3>

    <?php foreach($cart_items as $item): ?>
        <p>
            <?= htmlspecialchars($item['name']) ?> (<?= $item['quantity'] ?>x) 
            - Rp <?= number_format($item['price'] * $item['quantity'],0,',','.') ?>
        </p>
    <?php endforeach; ?>

    <hr>

    <p>Total: Rp <?= number_format($total,0,',','.') ?></p>

    <?php if($diskon > 0): ?>
        <p>Diskon: - Rp <?= number_format($diskon,0,',','.') ?></p>
    <?php endif; ?>

    <p>Ongkir: <?= $ongkir == 0 ? 'GRATIS 🎉' : 'Rp '.number_format($ongkir,0,',','.') ?></p>

    <h3>Total Bayar: Rp <?= number_format($grand_total,0,',','.') ?></h3>
</div>

<div class="flex">
    <button type="button" class="back" onclick="location.href='keranjang.php';">Kembali</button>
    <button type="submit" class="submit">Buat Pesanan</button>
</div>

</form>
</div>

<script>
function tampilkanRekening() {
    var metode = document.getElementById("metode").value;
    var info = document.getElementById("infoRekening");

    if (metode === "M-Banking" || metode === "E-Wallet") {
        info.style.display = "block";
    } else {
        info.style.display = "none";
    }
}
</script>

</body>
</html
