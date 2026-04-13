<?php
include '../koneksi.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi sederhana
    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak sama!";
    } else {
        // Cek username sudah ada atau belum
        $check = mysqli_query($koneksi, "SELECT username FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Default role adalah 'user'
            $role = 'user'; 

            $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";
            
            if (mysqli_query($koneksi, $query)) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Ecommerce Alip</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" />
    <link rel="stylesheet" href="../index.css" />
    <style>
        .input-field {
            background: transparent;
            border: none;
            outline: none;
            font-family: Inter, var(--default-font-family);
            font-size: 20px;
            font-weight: 400;
            color: #000;
            width: 100%;
            height: 100%;
            padding-left: 27px;
            box-sizing: border-box;
        }
        .input-field::placeholder { color: rgba(0, 0, 0, 0.6); }
        .msg-box {
            text-align: center;
            font-family: Inter;
            margin-bottom: 10px;
            font-weight: bold;
            width: 700px;
            margin-left: 250px; /* Sesuai dengan margin input */
            font-size: 16px;
        }
        .success { color: green; }
        .error { color: red; }
        .btn-register {
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
  </head>
  <body>
    <div
      style="width:1200px;height:1024px;background-color:#ffffff;position:relative;overflow:hidden;margin:0 auto"
      class="main-container"
    >
      <div
        style="width:1200px;height:726px;font-size:0px;position:absolute;top:-15px;left:0;z-index:9"
      >
        <span
          style="display:block;height:48px;font-family:Inter, var(--default-font-family);font-size:32px;font-weight:600;line-height:48px;color:#111827;position:relative;text-align:left;white-space:nowrap;z-index:1;margin:197px 0 0 485px"
          >Register User</span
        >
        
        <form action="" method="POST">
            <?php if($success): ?>
                <div class="msg-box success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="msg-box error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Username -->
            <div style="width:700px;height:60px;background-color:#ffffff;border-radius:50px;border:2px solid rgba(17, 24, 39, 0.4);position:relative;z-index:2;margin:20px 0 0 250px">
                <input type="text" name="username" class="input-field" placeholder="Username" required />
            </div>

            <!-- Email -->
            <div style="width:700px;height:60px;background-color:#ffffff;border-radius:50px;border:2px solid rgba(17, 24, 39, 0.4);position:relative;z-index:2;margin:20px 0 0 250px">
                <input type="email" name="email" class="input-field" placeholder="Email" required />
            </div>

            <!-- Password -->
            <div style="width:700px;height:60px;background-color:#ffffff;border-radius:50px;border:2px solid rgba(17, 24, 39, 0.4);position:relative;z-index:3;margin:20px 0 0 250px">
                <input type="password" name="password" class="input-field" placeholder="Password" required />
            </div>

            <!-- Confirm Password -->
            <div style="width:700px;height:60px;background-color:#ffffff;border-radius:50px;border:2px solid rgba(17, 24, 39, 0.4);position:relative;z-index:3;margin:20px 0 0 250px">
                <input type="password" name="confirm_password" class="input-field" placeholder="Konfirmasi Password" required />
            </div>

            <button type="submit" class="btn-register" style="width:700px;height:60px;background-color:#cc7e25;border-radius:8px;position:relative;z-index:7;margin:30px 0 0 250px;">
                <span style="display:flex;height:45px;justify-content:center;align-items:center;font-family:Inter, var(--default-font-family);font-size:28px;font-weight:600;line-height:43.568px;color:#ffffff;text-align:center;white-space:nowrap;z-index:8;">Register</span>
            </button>
        </form>

        <div style="width:139px;height:139px;background-position:center;background-image:url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/vuy0jSxOvJ.png);background-size:cover;background-repeat:no-repeat;position:absolute;top:0;left:530px;z-index:9"></div>
        <div style="width:1200px;height:111px;background-color:#ffffff;position:absolute;top:15px;left:0;box-shadow:0 1px 3px 0 rgba(0, 0, 0, 0.3)"></div>
      </div>
      
      <div style="position:absolute; bottom: 50px; left: 50%; transform: translateX(-50%); font-family: Inter; font-size: 16px;">
          Sudah punya akun? <a href="login.php" style="color: #cc7e25; text-decoration: none; font-weight: bold;">Login disini</a>
      </div>

      <div style="width:40px;height:40px;background-position:center;background-image:url(https://codia-f2c.s3.us-west-1.amazonaws.com/image/2026-02-24/enyoYMb8s3.png);background-size:cover;background-repeat:no-repeat;position:absolute;top:367px;left:1300px;overflow:hidden;z-index:6"></div>
    </div>
  </body>
</html>