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

// Handle Update Status via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    $allowed_statuses = ['pending', 'confirmed', 'paid', 'shipped', 'delivered', 'cancelled'];
    
    if ($transaction_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt = mysqli_prepare($koneksi, "UPDATE transactions SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $transaction_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    }
    exit();
}

// Buat tabel jika belum ada (opsional, sesuai dump SQL Anda tabel sudah ada)
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    product_id INT(11) DEFAULT NULL,
    quantity INT(11) DEFAULT NULL,
    total_price DECIMAL(10,2) DEFAULT NULL,
    payment_method VARCHAR(50) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_items INT(11) DEFAULT 1
)");

// Query transactions dengan JOIN
$query = mysqli_query($koneksi, "SELECT t.*, u.username, p.name as product_name 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN products p ON t.product_id = p.id
    ORDER BY t.created_at DESC");
$transactions = mysqli_fetch_all($query, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kelola Transaksi - Admin</title>
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
        .submenu { display: none; background-color: #a86622; padding-left: 20px; }
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
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #e5e7eb; }
        th { padding: 15px 20px; text-align: left; font-size: 14px; font-weight: 600; color: #374151; }
        td { padding: 15px 20px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #1f2937; }
        tr:hover { background-color: #f9fafb; }
        .status-select { padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; font-weight: 600; background: white; cursor: pointer; transition: all 0.2s; min-width: 130px; }
        .status-select:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); border-color: #3b82f6; }
        .status-select.loading { opacity: 0.6; pointer-events: none; background-color: #f3f4f6; }
        
        /* Warna Border & Teks Dropdown per Status */
        .status-pending { border-color: #f59e0b; color: #92400e; }
        .status-confirmed { border-color: #3b82f6; color: #1d4ed8; }
        .status-paid { border-color: #10b981; color: #065f46; }
        .status-shipped { border-color: #8b5cf6; color: #5b21b6; }
        .status-delivered { border-color: #059669; color: #064e3b; }
        .status-cancelled { border-color: #ef4444; color: #991b1b; }

        .toast { position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 1000; animation: slideIn 0.3s ease; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .toast.success { background-color: #10b981; }
        .toast.error { background-color: #ef4444; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
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
                        <div id="submenu-produk" class="submenu" style="background: transparent; padding-left: 15px; display: none;">
                            <a href="tambah_produk.php">+ Tambah Produk</a>
                            <a href="kelola_produk.php">Kelola Produk</a>
                            <a href="kelola_kategori.php">Kelola Kategori</a>
                        </div>
                    </div>
                    <a href="kelola_data_transaksi.php" class="active-menu">Kelola Data Transaksi</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1>Kelola Data Transaksi</h1>
        </div>
        <div class="dashboard-container">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order Number</th>
                            <th>User</th>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status Pengiriman</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($t['id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($t['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($t['username'] ?? 'Guest'); ?></td>
                            <td><?php echo htmlspecialchars($t['product_name'] ?? 'Multi-item'); ?></td>
                            <td><?php echo htmlspecialchars($t['quantity'] ?? $t['total_items'] ?? '-'); ?></td>
                            <td>Rp <?php echo number_format($t['total_price'], 0, ',', '.'); ?></td>
                            <td>
                                <select class="status-select status-<?php echo $t['status']; ?>" 
                                        data-id="<?php echo $t['id']; ?>" 
                                        onchange="updateStatus(this)">
                                    <option value="pending" <?php echo $t['status'] == 'pending' ? 'selected' : ''; ?>>🟡 Pending</option>
                                    <option value="confirmed" <?php echo $t['status'] == 'confirmed' ? 'selected' : ''; ?>>🔵 Confirmed</option>
                                    <option value="paid" <?php echo $t['status'] == 'paid' ? 'selected' : ''; ?>>🟢 Paid</option>
                                    <option value="shipped" <?php echo $t['status'] == 'shipped' ? 'selected' : ''; ?>>🟣 Shipped</option>
                                    <option value="delivered" <?php echo $t['status'] == 'delivered' ? 'selected' : ''; ?>>✅ Delivered</option>
                                    <option value="cancelled" <?php echo $t['status'] == 'cancelled' ? 'selected' : ''; ?>>🔴 Cancelled</option>
                                </select>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
function toggleMenu(menuId, arrowId) {
    const menu = document.getElementById(menuId);
    const arrow = document.getElementById(arrowId);
    if (menu.style.display === "block") {
        menu.style.display = "none";
        if(arrow) arrow.classList.remove("rotate");
    } else {
        menu.style.display = "block";
        if(arrow) arrow.classList.add("rotate");
    }
}

function updateStatus(selectElement) {
    const transactionId = selectElement.dataset.id;
    const newStatus = selectElement.value;
    const originalClass = selectElement.className;
    
    selectElement.classList.add('loading');
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('transaction_id', transactionId);
    formData.append('status', newStatus);
    
    fetch('kelola_data_transaksi.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        selectElement.classList.remove('loading');
        if (data.success) {
            // Update class warna sesuai status baru
            selectElement.className = 'status-select status-' + newStatus;
            showToast('Status berhasil diubah ke ' + newStatus.toUpperCase(), 'success');
        } else {
            showToast('Gagal: ' + data.message, 'error');
        }
    })
    .catch(err => {
        selectElement.classList.remove('loading');
        console.error(err);
        showToast('Terjadi kesalahan jaringan', 'error');
    });
}

function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => {
            toast.style.display = 'none';
            toast.style.animation = '';
        }, 300);
    }, 3000);
}
</script>
</body>
</html>