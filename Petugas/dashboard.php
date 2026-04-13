<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../Auth/login.php");
    exit();
}

$query_user = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$data_user = mysqli_fetch_assoc($query_user);
$total_user = $data_user['total'];

$total_produk = 0;
$result_produk = mysqli_query($koneksi, "SHOW TABLES LIKE 'products'");
if (mysqli_num_rows($result_produk) > 0) {
    $query_produk = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products");
    $data_produk = mysqli_fetch_assoc($query_produk);
    $total_produk = $data_produk['total'];
}

$total_payment = 0;
$result_trans = mysqli_query($koneksi, "SHOW TABLES LIKE 'transactions'");
if (mysqli_num_rows($result_trans) > 0) {
    $query_trans = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transactions");
    $data_trans = mysqli_fetch_assoc($query_trans);
    $total_payment = $data_trans['total'];
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Petugas - Ecommerce Alip</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        .sidebar { width: 280px; background-color: #e91e63; color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; z-index: 100; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 24px; font-weight: 700; }
        .logo-img { width: 50px; height: 50px; background-image: url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/X2WHqV51rA.png'); background-size: cover; border-radius: 50%; }
        .sidebar-menu { padding: 20px 0; flex: 1; overflow-y: auto; }
        .menu-item { padding: 12px 20px; color: #110606; text-decoration: none; font-weight: 600; font-size: 16px; display: block; transition: 0.3s; cursor: pointer; }
        .menu-item:hover { color: #fff; background: rgba(0,0,0,0.1); }
        .active-menu { background-color: #3ddf86; color: #14110a; border-left: 5px solid #14110a; }
        .submenu { display: none; background-color: #c2185b; padding-left: 20px; }
        .submenu.show { display: block; }
        .submenu a { display: block; color: #fff; text-decoration: none; font-size: 14px; padding: 8px 0; font-weight: 500; }
        .submenu a:hover { color: #3ddf86; padding-left: 5px; transition: 0.2s; }
        .logout-btn { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-link { display: flex; align-items: center; justify-content: space-between; color: #ff4d4d; text-decoration: none; font-weight: 800; font-size: 18px; }
        .logout-link:hover { color: #ff0000; }
        .main-content { margin-left: 280px; flex: 1; padding: 0; width: calc(100% - 280px); }
        .top-bar { background: white; height: 70px; display: flex; align-items: center; padding: 0 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 99; }
        .top-bar h1 { font-size: 24px; color: #0f172a; font-weight: 700; }
        .dashboard-container { padding: 40px; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; }
        .card { background: white; border-radius: 12px; padding: 25px; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card-icon { width: 60px; height: 60px; border-radius: 12px; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 30px; }
        .card-info h3 { font-size: 16px; color: #6b7280; margin-bottom: 5px; font-weight: 600; }
        .card-info p { font-size: 32px; color: #111827; font-weight: 800; margin: 0; }
        .text-green { color: #10b981; }
        .backup-info { background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #3ddf86; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: inline-block; }
        .backup-info span { color: #4b5563; font-size: 14px; font-weight: 500; }
        .backup-info strong { color: #111827; font-size: 16px; }
        .arrow { font-size: 12px; margin-left: 10px; transition: 0.3s; }
        .arrow.rotate { transform: rotate(180deg); }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Petugas Panel</h2>
            <div class="logo-img"></div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active-menu">Dashboard</a>
            <div style="margin-top: 20px;">
                <div onclick="toggleMenu('main-menu', 'arrow-main')" style="cursor: pointer; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; color: #110606; font-weight: 600;">
                    <span>Menu</span>
                    <span id="arrow-main" class="arrow">▼</span>
                </div>
                <div id="main-menu" class="submenu">
                    <a href="kelola_data_user.php">Kelola Data User</a>
                    <div style="padding-left: 10px; border-left: 2px solid rgba(255,255,255,0.2); margin: 5px 0;">
                        <div onclick="toggleMenu('submenu-produk', 'arrow-produk')" style="cursor: pointer; padding: 8px 0; display: flex; justify-content: space-between; color: #fff;">
                            <span style="font-size: 14px;">Kelola Data Produk</span>
                            <span id="arrow-produk" class="arrow">▼</span>
                        </div>
                        <div id="submenu-produk" class="submenu" style="background: transparent; padding-left: 15px; display: none;">
                            <a href="tambah_produk.php">+ Tambah Produk</a>
                            <a href="kelola_produk.php">Kelola Produk</a>
                            <a href="kelola_kategori.php">Kelola Kategori</a>
                        </div>
                    </div>
                    <a href="kelola_data_transaksi.php">Kelola Data Transaksi</a>
                    <div style="padding-left: 10px; border-left: 2px solid rgba(255,255,255,0.2); margin: 5px 0;">
                        <div onclick="toggleMenu('submenu-laporan', 'arrow-laporan')" style="cursor: pointer; padding: 8px 0; display: flex; justify-content: space-between; color: #fff;">
                            <span style="font-size: 14px;">Laporan</span>
                            <span id="arrow-laporan" class="arrow">▼</span>
                        </div>
                        <div id="submenu-laporan" class="submenu" style="background: transparent; padding-left: 15px; display: none;">
                            <a href="laporan_transaksi.php">Laporan Transaksi</a>
                            <a href="laporan_penjualan.php">Laporan Penjualan</a>
                            <a href="laporan_stok.php">Laporan Stok</a>
                        </div>
                    </div>
                    <a href="backup_restore.php">Backup & Restore</a>
                </div>
            </div>
        </div>
        <div class="logout-btn">
            <a href="?logout=true" class="logout-link">
                <span>Logout</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h1>Dashboard</h1>
        </div>
        <div class="dashboard-container">
            <div class="cards-grid">
                <div class="card">
                    <div class="card-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="card-info">
                        <h3>Total User</h3>
                        <p class="text-green"><?php echo number_format($total_user); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <div class="card-info">
                        <h3>Total Produk</h3>
                        <p class="text-green"><?php echo number_format($total_produk); ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    </div>
                    <div class="card-info">
                        <h3>Total Payment</h3>
                        <p class="text-green"><?php echo number_format($total_payment); ?></p>
                    </div>
                </div>
            </div>
            <div class="backup-info">
                <span>Backup Terakhir: </span>
                <strong><?php echo date('d F Y, H:i'); ?> WIB</strong>
            </div>
        </div>
    </div>
</div>
<script>
    function toggleMenu(menuId, arrowId) {
        var menu = document.getElementById(menuId);
        var arrow = document.getElementById(arrowId);
        if (menu.style.display === "block") {
            menu.style.display = "none";
            if(arrow) arrow.classList.remove("rotate");
        } else {
            menu.style.display = "block";
            if(arrow) arrow.classList.add("rotate");
        }
    }
</script>
</body>
</html>