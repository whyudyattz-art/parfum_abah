<?php
session_start();
include 'koneksi.php';

$username = $_POST['username'];
$password = $_POST['password'];

// Menggunakan tabel admin untuk mengecek login
$data = mysqli_query($koneksi,"SELECT * FROM admin WHERE username='$username' AND password='$password'");
$cek = mysqli_num_rows($data);

if($cek > 0){
    $_SESSION['admin'] = $username;
    $_SESSION['status'] = "login";
    header("location:index.php");
}else{
    header("location:login.php");
}
?>
