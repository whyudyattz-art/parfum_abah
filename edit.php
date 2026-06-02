<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

$id = $_GET['id'];

$data = mysqli_query($koneksi,
"SELECT * FROM parfum WHERE id='$id'");

$d = mysqli_fetch_assoc($data);

if(!$d){
    echo "Data tidak ditemukan!";
    exit();
}

if(isset($_POST['update'])){

    $nama  = $_POST['nama'];
    $merek = $_POST['merek'];
    $harga = $_POST['harga'];
    $stok  = $_POST['stok'];

    $update = mysqli_query($koneksi,"
    UPDATE parfum SET
    nama_parfum='$nama',
    merek='$merek',
    harga='$harga',
    stok='$stok'
    WHERE id='$id'
    ");

    if($update){
        echo "
        <script>
            alert('Data berhasil diperbarui');
            window.location='index.php';
        </script>
        ";
    }else{
        echo "
        <script>
            alert('Gagal memperbarui data');
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
<title>Edit Produk MYEGO</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
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
    background:white;
    padding:35px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    margin-bottom:25px;
}

.form-group{
    margin-bottom:18px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
}

input:focus{
    outline:none;
    border-color:gold;
}

.btn{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
}

.btn-update{
    background:gold;
}

.btn-kembali{
    display:block;
    text-align:center;
    text-decoration:none;
    margin-top:15px;
    background:#222;
    color:white;
    padding:12px;
    border-radius:8px;
}

</style>
</head>
<body>

<div class="container">

    <div class="card">

        <h2>Edit Produk Parfum</h2>

        <form method="POST">

            <div class="form-group">
                <label>Nama Parfum</label>
                <input type="text"
                name="nama"
                value="<?php echo $d['nama_parfum']; ?>"
                required>
            </div>

            <div class="form-group">
                <label>Merek</label>
                <input type="text"
                name="merek"
                value="<?php echo $d['merek']; ?>"
                required>
            </div>

            <div class="form-group">
                <label>Harga</label>
                <input type="number"
                name="harga"
                value="<?php echo $d['harga']; ?>"
                required>
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input type="number"
                name="stok"
                value="<?php echo $d['stok']; ?>"
                required>
            </div>

            <button type="submit"
            name="update"
            class="btn btn-update">
                Update Produk
            </button>

        </form>

        <a href="index.php" class="btn-kembali">
            ← Kembali ke Dashboard
        </a>

    </div>

</div>

</body>
</html>