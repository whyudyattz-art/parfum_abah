<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

if(isset($_POST['simpan'])){

    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $merek = mysqli_real_escape_string($koneksi, $_POST['merek']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok  = mysqli_real_escape_string($koneksi, $_POST['stok']);

    $query = mysqli_query($koneksi,"
        INSERT INTO parfum
        (nama_parfum, merek, harga, stok)
        VALUES
        ('$nama','$merek','$harga','$stok')
    ");

    if($query){
        echo "
        <script>
            alert('Produk parfum berhasil ditambahkan');
            window.location='index.php';
        </script>
        ";
    }else{
        echo "
        <script>
            alert('Gagal menambahkan produk');
        </script>
        ";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Produk - MYEGO</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f5f7fb;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.container{
    width:100%;
    max-width:600px;
    padding:20px;
}

.card{
    background:#fff;
    border-radius:20px;
    padding:35px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}

.card h2{
    text-align:center;
    margin-bottom:25px;
    color:#222;
}

.form-group{
    margin-bottom:18px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:500;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:10px;
    font-size:15px;
}

input:focus{
    outline:none;
    border-color:gold;
}

.btn{
    width:100%;
    padding:14px;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}

.btn-simpan{
    background:gold;
    color:#000;
}

.btn-simpan:hover{
    opacity:0.9;
}

.btn-kembali{
    display:block;
    text-align:center;
    margin-top:15px;
    text-decoration:none;
    background:#222;
    color:white;
    padding:12px;
    border-radius:10px;
}

.btn-kembali:hover{
    background:#000;
}

</style>
</head>
<body>

<div class="container">

    <div class="card">

        <h2>Tambah Produk Parfum</h2>

        <form method="POST">

            <div class="form-group">
                <label>Nama Parfum</label>
                <input type="text" name="nama" required>
            </div>

            <div class="form-group">
                <label>Merek</label>
                <input type="text" name="merek" required>
            </div>

            <div class="form-group">
                <label>Harga (Rp)</label>
                <input type="number" name="harga" required>
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input type="number" name="stok" required>
            </div>

            <button type="submit" name="simpan" class="btn btn-simpan">
                Simpan Produk
            </button>

        </form>

        <a href="index.php" class="btn-kembali">
            ← Kembali ke Dashboard
        </a>

    </div>

</div>

</body>
</html>