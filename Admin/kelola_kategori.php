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

// Create categories table if not exists
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM categories WHERE id = '$id'");
    header("Location: kelola_kategori.php?success=deleted");
    exit();
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $description = mysqli_real_escape_string($koneksi, $_POST['description']);
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $edit_id = $_POST['edit_id'];
        $query = "UPDATE categories SET name='$name', description='$description' WHERE id='$edit_id'";
        mysqli_query($koneksi, $query);
        header("Location: kelola_kategori.php?success=updated");
    } else {
        $query = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
        mysqli_query($koneksi, $query);
        header("Location: kelola_kategori.php?success=added");
    }
    exit();
}

$query = mysqli_query($koneksi, "SELECT * FROM categories ORDER BY id DESC");
$categories = mysqli_fetch_all($query, MYSQLI_ASSOC);

$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($koneksi, "SELECT * FROM categories WHERE id = '$edit_id'");
    $edit_data = mysqli_fetch_assoc($edit_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Kategori - Admin</title>
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
        .btn-action { padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: 600; margin-right: 5px; }
        .btn-edit { background-color: #e5e7eb; color: #374151; }
        .btn-edit:hover { background-color: #d1d5db; }
        .btn-delete { background-color: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background-color: #fecaca; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 450px; max-width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 20px; color: #1f2937; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 600; color: #374151; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; }
        .form-group textarea { height: 80px; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #c3792a; }
        .btn-submit { background-color: #c3792a; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-submit:hover { background-color: #a86622; }
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: #d1fae5; color: #065f46; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #fef3c7; color: #92400e; }
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
                            <a href="tambah_produk.php">+ Tambah Produk</a>
                            <a href="kelola_produk.php">Kelola Produk</a>
                            <a href="kelola_kategori.php" class="active-menu">Kelola Kategori</a>
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
            <h1>Kelola Kategori Produk</h1>
            <button class="btn-add" onclick="openModal()">+ Tambah Kategori</button>
        </div>
        <div class="dashboard-container">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Kategori berhasil disimpan!</div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($cat['name']); ?></span></td>
                            <td><?php echo htmlspecialchars($cat['description']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cat['created_at'])); ?></td>
                            <td>
                                <a href="?edit=<?php echo $cat['id']; ?>" class="btn-action btn-edit" onclick="openModal(<?php echo htmlspecialchars(json_encode($cat)); ?>)">Edit</a>
                                <a href="?delete=<?php echo $cat['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Kategori</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="name" id="name" required placeholder="Contoh: Elektronik, Pakaian, dll">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="description" placeholder="Deskripsi kategori (opsional)"></textarea>
            </div>
            <button type="submit" class="btn-submit" id="btnSubmit">Simpan</button>
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

    function openModal(data = null) {
        const modal = document.getElementById('categoryModal');
        const modalTitle = document.getElementById('modalTitle');
        const editId = document.getElementById('edit_id');
        const name = document.getElementById('name');
        const description = document.getElementById('description');
        const btnSubmit = document.getElementById('btnSubmit');

        if (data) {
            modalTitle.textContent = 'Edit Kategori';
            editId.value = data.id;
            name.value = data.name;
            description.value = data.description || '';
            btnSubmit.textContent = 'Update';
        } else {
            modalTitle.textContent = 'Tambah Kategori';
            editId.value = '';
            name.value = '';
            description.value = '';
            btnSubmit.textContent = 'Simpan';
        }

        modal.classList.add('show');
    }

    function closeModal() {
        document.getElementById('categoryModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('categoryModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        <?php if($edit_data): ?>
            openModal(<?php echo json_encode($edit_data); ?>);
        <?php endif; ?>
    });
</script>
</body>
</html>