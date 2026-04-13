<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../Auth/login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../Auth/login.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id = '$id'");
    header("Location: kelola_data_user.php?success=deleted");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $edit_id = $_POST['edit_id'];
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET username='$username', email='$email', role='$role', password='$hashed_password' WHERE id='$edit_id'";
        } else {
            $query = "UPDATE users SET username='$username', email='$email', role='$role' WHERE id='$edit_id'";
        }
        mysqli_query($koneksi, $query);
        header("Location: kelola_data_user.php?success=updated");
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";
        mysqli_query($koneksi, $query);
        header("Location: kelola_data_user.php?success=added");
    }
    exit();
}

$query = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id DESC");
$users = mysqli_fetch_all($query, MYSQLI_ASSOC);

$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$edit_id'");
    $edit_user = mysqli_fetch_assoc($edit_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Data User - Petugas</title>
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
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-admin { background-color: #fed7aa; color: #c2410c; }
        .badge-petugas { background-color: #bbf7d0; color: #15803d; }
        .badge-user { background-color: #f0abfc; color: #a21caf; }
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
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #e91e63; }
        .btn-submit { background-color: #e91e63; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-submit:hover { background-color: #c2185b; }
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background-color: #d1fae5; color: #065f46; }
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
            <a href="dashboard.php" class="menu-item">Dashboard</a>
            <div style="margin-top: 20px;">
                <div onclick="toggleMenu('main-menu', 'arrow-main')" style="cursor: pointer; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; color: #110606; font-weight: 600;">
                    <span>Menu</span>
                    <span id="arrow-main" class="arrow">▼</span>
                </div>
                <div id="main-menu" class="submenu show">
                    <a href="kelola_data_user.php" class="active-menu">Kelola Data User</a>
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
            <h1>Kelola Data User</h1>
            <button class="btn-add" onclick="openModal()">+ Tambah User</button>
        </div>
        <div class="dashboard-container">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">User berhasil disimpan!</div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if($user['role'] == 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php elseif($user['role'] == 'petugas'): ?>
                                    <span class="badge badge-petugas">Petugas</span>
                                <?php else: ?>
                                    <span class="badge badge-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?edit=<?php echo $user['id']; ?>" class="btn-action btn-edit" onclick="openModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</a>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah User</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="edit_id" id="edit_id" value="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" required>
                    <option value="user">User</option>
                    <option value="petugas">Petugas</option>
                    <option value="admin">Admin</option>
                </select>
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
    function openModal(userData = null) {
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const editId = document.getElementById('edit_id');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const role = document.getElementById('role');
        const btnSubmit = document.getElementById('btnSubmit');
        if (userData) {
            modalTitle.textContent = 'Edit User';
            editId.value = userData.id;
            username.value = userData.username;
            email.value = userData.email;
            role.value = userData.role;
            password.value = '';
            btnSubmit.textContent = 'Update';
        } else {
            modalTitle.textContent = 'Tambah User';
            editId.value = '';
            username.value = '';
            email.value = '';
            password.value = '';
            role.value = 'user';
            btnSubmit.textContent = 'Simpan';
        }
        modal.classList.add('show');
    }
    function closeModal() {
        document.getElementById('userModal').classList.remove('show');
    }
    window.onclick = function(event) {
        const modal = document.getElementById('userModal');
        if (event.target == modal) { closeModal(); }
    }
    <?php if($edit_user): ?>
        openModal(<?php echo htmlspecialchars(json_encode($edit_user)); ?>);
    <?php endif; ?>
</script>
</body>
</html>