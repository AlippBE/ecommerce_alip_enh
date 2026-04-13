<?php
session_start();
include 'koneksi.php';

// ambil data user / cart
$user_id = $_SESSION['user_id'];
$total = $_POST['total'];

// simpan ke database
$query = mysqli_query($conn, "INSERT INTO orders (user_id, total) VALUES ('$user_id', '$total')");

// hapus cart setelah checkout
mysqli_query($conn, "DELETE FROM cart WHERE user_id='$user_id'");

// redirect ke halaman sukses
header("Location: checkout_success.php");
exit;
?>