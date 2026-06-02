<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$data = mysqli_query($koneksi,"SELECT * FROM parfum");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin MYEGO</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f5f7fb;
}

.sidebar{
    position:fixed;
    width:250px;
    height:100vh;
    background:linear-gradient(180deg,#111,#222);
    color:white;
    padding:20px;
}

.logo{
    text-align:center;
    font-size:28px;
    font-weight:bold;
    color:gold;
    margin-bottom:40px;
}

.menu a{
    display:block;
    color:white;
    text-decoration:none;
    padding:12px;
    margin-bottom:10px;
    border-radius:8px;
    transition:.3s;
}

.menu a:hover{
    background:gold;
    color:black;
}

.main{
    margin-left:250px;
    padding:30px;
}

.header{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.1);
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logout{
    background:red;
    color:white;
    padding:10px 20px;
    text-decoration:none;
    border-radius:8px;
}

.cards{
    margin-top:25px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
}

.card{
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.1);
}

.card h3{
    color:#666;
    margin-bottom:10px;
}

.card p{
    font-size:30px;
    font-weight:bold;
    color:#111;
}

.table-box{
    background:white;
    margin-top:30px;
    padding:20px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.1);
}

.btn-tambah{
    background:#28a745;
    color:white;
    text-decoration:none;
    padding:10px 20px;
    border-radius:8px;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table th{
    background:gold;
    padding:12px;
}

table td{
    padding:12px;
    border-bottom:1px solid #ddd;
}

.edit{
    background:orange;
    color:white;
    text-decoration:none;
    padding:8px 12px;
    border-radius:5px;
}

.hapus{
    background:red;
    color:white;
    text-decoration:none;
    padding:8px 12px;
    border-radius:5px;
}

</style>

</head>
<body>

<div class="sidebar">

    <div class="logo">
        MYEGO
    </div>

    <div class="menu">
        <a href="#">🏠 Dashboard</a>
        <a href="#">🧴 Data Parfum</a>
        <a href="#">📦 Stok Produk</a>
        <a href="#">📊 Laporan</a>
    </div>

</div>

<div class="main">

    <div class="header">
        <div>
            <h2>Dashboard Admin MYEGO</h2>
            <p>Selamat Datang, <?php echo $_SESSION['admin']; ?></p>
        </div>

        <a href="logout.php" class="logout">
            Logout
        </a>
    </div>

    <?php
    $total_produk = mysqli_num_rows(mysqli_query($koneksi,"SELECT * FROM parfum"));

    $stok = mysqli_fetch_assoc(
    mysqli_query($koneksi,"SELECT SUM(stok) as total FROM parfum")
    );
    ?>

    <div class="cards">

        <div class="card">
            <h3>Total Produk</h3>
            <p><?php echo $total_produk; ?></p>
        </div>

        <div class="card">
            <h3>Total Stok</h3>
            <p><?php echo $stok['total']; ?></p>
        </div>

        <div class="card">
            <h3>Admin Aktif</h3>
            <p>1</p>
        </div>

    </div>

    <div class="table-box">

        <a href="tambah.php" class="btn-tambah">
            + Tambah Parfum
        </a>

        <table>

            <tr>
                <th>No</th>
                <th>Nama Parfum</th>
                <th>Merek</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>

            <?php
            $no=1;
            while($d=mysqli_fetch_array($data)){
            ?>

            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $d['nama_parfum']; ?></td>
                <td><?php echo $d['merek']; ?></td>
                <td>Rp <?php echo number_format($d['harga']); ?></td>
                <td><?php echo $d['stok']; ?></td>

                <td>
                    <a href="edit.php?id=<?php echo $d['id']; ?>" class="edit">
                        Edit
                    </a>

                    <a href="hapus.php?id=<?php echo $d['id']; ?>"
                    class="hapus"
                    onclick="return confirm('Yakin ingin menghapus data ini?')">
                        Hapus
                    </a>
                </td>
            </tr>

            <?php } ?>

        </table>

    </div>

</div>

</body>
</html>