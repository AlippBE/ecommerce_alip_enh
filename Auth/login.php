<?php
session_start();
include '../koneksi.php';

// Jika sudah login, cek role dan arahkan
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../Admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'petugas') {
        header("Location: ../Petugas/dashboard.php");
    } elseif ($_SESSION['role'] == 'user') {
        header("Location: ../User/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Query cek username
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Set Session
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['id'] = $row['id'];

            // Redirect berdasarkan Role
            if ($row['role'] == 'admin') {
                header("Location: ../Admin/dashboard.php");
            } elseif ($row['role'] == 'petugas') {
                header("Location: ../Petugas/dashboard.php");
            } elseif ($row['role'] == 'user') {
                header("Location: ../User/dashboard.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Ecommerce Alip</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" />
    <link rel="stylesheet" href="../index.css" />
    <style>
        /* Style khusus agar input menyatu dengan desain box */
        .input-field {
            background: transparent;
            border: none;
            outline: none;
            font-family: Inter, var(--default-font-family);
            font-size: 20px; /* Ukuran font lebih proporsional */
            font-weight: 400;
            color: #000;
            width: 100%;
            height: 100%;
            padding-left: 27px;
            box-sizing: border-box;
        }
        .input-field::placeholder {
            color: rgba(0, 0, 0, 0.6);
        }
        .error-msg {
            color: red;
            text-align: center;
            font-family: Inter;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 16px;
        }
        /* Tombol login agar bisa diklik seluruh area */
        .btn-login {
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .btn-login span {
            position: static !important; /* Reset posisi absolute span agar tengah */
            transform: none !important;
        }
    </style>
  </head>
  <body>
    <!-- Ukuran container diubah dari 1440px menjadi 1200px agar pas di layar laptop -->
    <div
      style="width:1200px;height:1024px;background-color:#ffffff;position:relative;overflow:hidden;margin:0 auto"
      class="main-container"
    >
      <div
        style="width:1200px;height:726px;font-size:0px;position:absolute;top:-15px;left:0;z-index:9"
      >
        <!-- Font size judul diperkecil dari 40px menjadi 32px -->
        <span
          style="display:block;height:48px;font-family:Inter, var(--default-font-family);font-size:32px;font-weight:600;line-height:48px;color:#111827;position:relative;text-align:left;white-space:nowrap;z-index:1;margin:197px 0 0 485px"
          >Login Admin</span
        >
        
        <form action="" method="POST">
            <?php if($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Input Username: Width diperkecil dari 831px menjadi 700px -->
            <div
            style="width:700px;height:60px;background-color:#ffffff;border-radius:50px;border:2px solid rgba(17, 24, 39, 0.4);position:relative;z-index:2;margin:54px 0 0 250px"
            >
            <input type="text" name="username" class="input-field" placeholder="Username/Email" required />
            </div>

            <!-- Input Password -->
            <div
            style="width:700px;height:60px;background-color:#ffffff;border-radius:50px;border:2px solid rgba(17, 24, 39, 0.4);position:relative;z-index:3;margin:20px 0 0 250px"
            >
            <input type="password" name="password" class="input-field" placeholder="Password" required />
            </div>

            <!-- Tombol Login: Width diperkecil, height disesuaikan -->
            <button type="submit" class="btn-login" style="width:700px;height:60px;background-color:#cc7e25;border-radius:8px;position:relative;z-index:7;margin:40px 0 0 250px;">
            <span
                style="display:flex;height:45px;justify-content:center;align-items:center;font-family:Inter, var(--default-font-family);font-size:28px;font-weight:600;line-height:43.568px;color:#ffffff;text-align:center;white-space:nowrap;z-index:8;"
                >Login</span
            >
            </button>
        </form>

        <div
          style="width:139px;height:139px;background-position:center;background-image:url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/vuy0jSxOvJ.png);background-size:cover;background-repeat:no-repeat;position:absolute;top:0;left:530px;z-index:9"
        ></div>
        <div
          style="width:1200px;height:111px;background-color:#ffffff;position:absolute;top:15px;left:0;box-shadow:0 1px 3px 0 rgba(0, 0, 0, 0.3)"
        ></div>
      </div>
      
      <!-- Link ke Register -->
      <div style="position:absolute; bottom: 50px; left: 50%; transform: translateX(-50%); font-family: Inter; font-size: 16px;">
          Belum punya akun? <a href="register.php" style="color: #cc7e25; text-decoration: none; font-weight: bold;">Daftar disini</a>
      </div>

      <div
        style="width:40px;height:40px;background-position:center;background-image:url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/enyoYMb8s3.png);background-size:cover;background-repeat:no-repeat;position:absolute;top:367px;left:1300px;overflow:hidden;z-index:6"
      ></div>
    </div>
  </body>
</html>