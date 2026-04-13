<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../Auth/login.php");
    exit();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}

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

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM products WHERE id = '$id'");
    header("Location: kelola_produk.php?success=deleted");
    exit();
}

// Handle Edit Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['edit_id']);
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $description = mysqli_real_escape_string($koneksi, $_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = mysqli_real_escape_string($koneksi, $_POST['category']);
    
    // Handle image upload (optional - keep old image if not uploading new one)
    $image_query = mysqli_query($koneksi, "SELECT image FROM products WHERE id = '$id'");
    $old_image = mysqli_fetch_assoc($image_query)['image'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $image = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        // Delete old image
        if ($old_image && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
        }
    } else {
        $image = $old_image;
    }
    
    $query = "UPDATE products SET name='$name', description='$description', price='$price', stock='$stock', category='$category', image='$image' WHERE id='$id'";
    mysqli_query($koneksi, $query);
    header("Location: kelola_produk.php?success=updated");
    exit();
}

$query = mysqli_query($koneksi, "SELECT * FROM products ORDER BY id DESC");
$products = mysqli_fetch_all($query, MYSQLI_ASSOC);

// Get product data for edit modal
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($koneksi, "SELECT * FROM products WHERE id = '$edit_id'");
    $edit_product = mysqli_fetch_assoc($edit_query);
}

// Get categories for dropdown
$categories_query = mysqli_query($koneksi, "SELECT * FROM categories ORDER BY name ASC");
$categories_list = mysqli_fetch_all($categories_query, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Produk - Admin</title>
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
        .submenu { display: block; background-color: #c2185b; padding-left: 20px; }
        .submenu a { display: block; color: #fff; text-decoration: none; font-size: 14px; padding: 8px 0; font-weight: 500; }
        .submenu a:hover { color: #3ddf86; padding-left: 5px; transition: 0.2s; }
        .logout-btn { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-link { display: flex; align-items: center; justify-content: space-between; color: #ff4d4d; text-decoration: none; font-weight: 800; font-size: 18px; }
        .logout-link:hover { color: #ff0000; }
        .main-content { margin-left: 280px; flex: 1; padding: 0; width: calc(100% - 280px); }
        .top-bar { background: white; height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 99; }
        .top-bar h1 { font-size: 24px; color: #0f172a; font-weight: 700; }
        .btn-add { background-color: #dc2626; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; cursor: pointer; border: none; }
        .btn-add:hover { background-color: #b91c1c; }
        .dashboard-container { padding: 40px; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #e5e7eb; }
        th { padding: 15px 20px; text-align: left; font-size: 14px; font-weight: 600; color: #374151; }
        td { padding: 15px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #1f2937; }
        tr:hover { background-color: #f9fafb; }
        .btn-action { padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: 600; margin-right: 5px; cursor: pointer; }
        .btn-edit { background-color: #e5e7eb; color: #374151; }
        .btn-edit:hover { background-color: #d1d5db; }
        .btn-delete { background-color: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background-color: #fecaca; }
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .arrow { font-size: 12px; margin-left: 10px; transition: 0.3s; }
        .arrow.rotate { transform: rotate(180deg); }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 550px; max-width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 20px; color: #1f2937; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 600; color: #374151; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        .form-group textarea { height: 80px; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #c3792a; }
        .btn-submit { background-color: #c3792a; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-submit:hover { background-color: #a86622; }
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
                            <a href="tambah_produk.php">+ Tambah Produk</a>
                            <a href="kelola_produk.php" class="active-menu">Kelola Produk</a>
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
            <h1>Kelola Produk</h1>
            <a href="tambah_produk.php" class="btn-add">+ Tambah Produk</a>
        </div>
        <div class="dashboard-container">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Data berhasil disimpan!</div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>ID</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $p): ?>
                        <tr>
                            <td>
                                <?php if($p['image']): ?>
                                    <img src="../uploads/products/<?php echo $p['image']; ?>" class="product-img" alt="Product">
                                <?php else: ?>
                                    <div class="product-img" style="background: #e5e7eb; display: flex; align-items: center; justify-content: center;">No Img</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo htmlspecialchars($p['category']); ?></td>
                            <td>Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $p['stock']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $p['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="?delete=<?php echo $p['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Product -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Edit Produk</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="edit_product" value="1">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="name" id="name" required>
            </div>
            
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="description"></textarea>
            </div>
            
            <div class="form-group">
                <label>Harga (Rp)</label>
                <input type="number" name="price" id="price" required>
            </div>
            
            <div class="form-group">
                <label>Stok</label>
                <input type="number" name="stock" id="stock" required>
            </div>
            
            <div class="form-group">
                <label>Kategori</label>
                <select name="category" id="category">
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
                <input type="file" name="image" id="image" accept="image/*">
                <small style="color: #6b7280; font-size: 12px;">Kosongkan jika tidak ingin mengubah gambar</small>
            </div>
            
            <button type="submit" class="btn-submit">Update Produk</button>
        </form>
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

    function openModal(productData) {
        const modal = document.getElementById('editModal');
        const editId = document.getElementById('edit_id');
        const name = document.getElementById('name');
        const description = document.getElementById('description');
        const price = document.getElementById('price');
        const stock = document.getElementById('stock');
        const category = document.getElementById('category');

        editId.value = productData.id;
        name.value = productData.name;
        description.value = productData.description || '';
        price.value = productData.price;
        stock.value = productData.stock;
        category.value = productData.category;

        modal.classList.add('show');
    }

    function closeModal() {
        document.getElementById('editModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // Open modal when edit parameter exists in URL
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($edit_product): ?>
            openModal(<?php echo json_encode($edit_product); ?>);
        <?php endif; ?>
    });
</script>
</body>
</html>