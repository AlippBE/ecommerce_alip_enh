<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Auth/login.php");
    exit();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}

// Create products table if not exists
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS products (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT(11) NOT NULL DEFAULT 0,
    category VARCHAR(100),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $description = mysqli_real_escape_string($koneksi, $_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = mysqli_real_escape_string($koneksi, $_POST['category']);
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }
    
    $query = "INSERT INTO products (name, description, price, stock, category, image) VALUES ('$name', '$description', '$price', '$stock', '$category', '$image')";
    mysqli_query($koneksi, $query);
    header("Location: kelola_produk.php?success=added");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Produk - Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        .sidebar { width: 280px; background-color: #c3792a; color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; z-index: 100; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 24px; font-weight: 700; }
        .logo-img { width: 50px; height: 50px; background-image: url('https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/X2WHqV51rA.png'); background-size: cover; border-radius: 50%; }
        .sidebar-menu { padding: 20px 0; flex: 1; overflow-y: auto; }
        .menu-item { padding: 12px 20px; color: #110606; text-decoration: none; font-weight: 600; font-size: 16px; display: block; transition: 0.3s; cursor: pointer; }
        .menu-item:hover { color: #fff; background: rgba(0,0,0,0.1); }
        .active-menu { background-color: #3ddf86; color: #14110a; border-left: 5px solid #14110a; }
        .submenu { display: block; background-color: #a86622; padding-left: 20px; }
        .submenu a { display: block; color: #fff; text-decoration: none; font-size: 14px; padding: 8px 0; font-weight: 500; }
        .submenu a:hover { color: #3ddf86; padding-left: 5px; transition: 0.2s; }
        .logout-btn { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-link { display: flex; align-items: center; justify-content: space-between; color: #ff4d4d; text-decoration: none; font-weight: 800; font-size: 18px; }
        .logout-link:hover { color: #ff0000; }
        .main-content { margin-left: 280px; flex: 1; padding: 0; width: calc(100% - 280px); }
        .top-bar { background: white; height: 70px; display: flex; align-items: center; padding: 0 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 99; }
        .top-bar h1 { font-size: 24px; color: #0f172a; font-weight: 700; }
        .dashboard-container { padding: 40px; }
        .form-container { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 30px; max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #374151; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #c3792a; }
        .form-group textarea { height: 100px; resize: vertical; }
        .btn-submit { background-color: #c3792a; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-submit:hover { background-color: #a86622; }
        .arrow { font-size: 12px; margin-left: 10px; transition: 0.3s; }
        .arrow.rotate { transform: rotate(180deg); }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
            <div class="logo-img"></div>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">Dashboard</a>
            <div style="margin-top: 20px;">
                <div onclick="toggleMenu('main-menu', 'arrow-main')" style="cursor: pointer; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; color: #110606; font-weight: 600;">
                    <span>Menu</span>
                    <span id="arrow-main" class="arrow">▼</span>
                </div>
                <div id="main-menu" class="submenu show">
                    <a href="kelola_data_user.php">Kelola Data User</a>
                    <a href="kelola_data_petugas.php">Kelola Data Petugas</a>
                    <div style="padding-left: 10px; border-left: 2px solid rgba(255,255,255,0.2); margin: 5px 0;">
                        <div onclick="toggleMenu('submenu-produk', 'arrow-produk')" style="cursor: pointer; padding: 8px 0; display: flex; justify-content: space-between; color: #fff;">
                            <span style="font-size: 14px;">Kelola Data Produk</span>
                            <span id="arrow-produk" class="arrow">▼</span>
                        </div>
                        <div id="submenu-produk" class="submenu" style="background: transparent; padding-left: 15px; display: block;">
                            <a href="tambah_produk.php" class="active-menu">+ Tambah Produk</a>
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
            <h1>Tambah Produk</h1>
        </div>
        <div class="dashboard-container">
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stock" required>
                    </div>
<?php
// Ambil semua kategori dari database
$categories_query = mysqli_query($koneksi, "SELECT * FROM categories ORDER BY name ASC");
$categories_list = mysqli_fetch_all($categories_query, MYSQLI_ASSOC);
?>

<div class="form-group">
    <label>Kategori</label>
    <select name="category">
        <?php if(count($categories_list) > 0): ?>
            <?php foreach($categories_list as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="Elektronik">Elektronik</option>
            <option value="Pakaian">Pakaian</option>
            <option value="Makanan">Makanan</option>
            <option value="Lainnya">Lainnya</option>
        <?php endif; ?>
    </select>
</div>
                    <div class="form-group">
                        <label>Gambar Produk</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <button type="submit" class="btn-submit">Simpan Produk</button>
                </form>
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