<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Buat tabel otomatis jika belum ada di database
$create_table = "CREATE TABLE IF NOT EXISTS pemasukan (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NOT NULL,
    jumlah INT(11) NOT NULL
)";
mysqli_query($koneksi, $create_table);

// Proses simpan data pemasukan
if(isset($_POST['simpan'])){
    $tanggal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $jumlah = $_POST['jumlah'];

    $query = "INSERT INTO pemasukan (tanggal, keterangan, jumlah) VALUES ('$tanggal', '$keterangan', '$jumlah')";
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Data pemasukan berhasil ditambahkan!'); window.location='laporan.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data!');</script>";
    }
}

// Proses hapus data pemasukan
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM pemasukan WHERE id='$id'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='laporan.php';</script>";
}

// Mengambil total pemasukan
$total_query = mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM pemasukan");
$total_data = mysqli_fetch_assoc($total_query);
$total_pemasukan = $total_data['total'] ? $total_data['total'] : 0;

// Mengambil semua data pemasukan
$data_pemasukan = mysqli_query($koneksi, "SELECT * FROM pemasukan ORDER BY tanggal DESC, id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Pemasukan MYEGO</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body{ background:#f5f7fb; }
.sidebar{ position:fixed; width:250px; height:100vh; background:linear-gradient(180deg,#111,#222); color:white; padding:20px; }
.logo{ text-align:center; font-size:28px; font-weight:bold; color:gold; margin-bottom:40px; }
.menu a{ display:block; color:white; text-decoration:none; padding:12px; margin-bottom:10px; border-radius:8px; transition:.3s; }
.menu a:hover{ background:gold; color:black; }
.menu a.active{ background:gold; color:black; }
.main{ margin-left:250px; padding:30px; }
.header{ background:white; padding:20px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,.1); display:flex; justify-content:space-between; align-items:center; }
.logout{ background:red; color:white; padding:10px 20px; text-decoration:none; border-radius:8px; }

.grid-container { display: grid; grid-template-columns: 350px 1fr; gap: 20px; margin-top: 30px; align-items: start; }
.content-box{ background:white; padding:20px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,.1); }
.content-box h3 { margin-bottom: 15px; color: #333; }

.form-group{ margin-bottom: 15px; }
.form-group label{ display: block; margin-bottom: 5px; font-weight: 500; color: #555; }
.form-group input{ width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
.btn-simpan{ background:#28a745; color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-size:16px; width: 100%; font-weight: bold; transition: .3s; }
.btn-simpan:hover{ background:#218838; }

table{ width:100%; border-collapse:collapse; margin-top:10px; }
table th{ background:gold; padding:12px; text-align: left; }
table td{ padding:12px; border-bottom:1px solid #ddd; }
.hapus{ background:red; color:white; text-decoration:none; padding:6px 12px; border-radius:5px; font-size:13px; }
.hapus:hover{ background:darkred; }
.total-box{ background:#e2f0fd; padding:15px; border-radius:10px; margin-top:20px; font-size:20px; font-weight:bold; color:#0056b3; text-align:right;}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">MYEGO</div>
    <div class="menu">
        <a href="index.php">🏠 Dashboard</a>
        <a href="katalog.php">📦 Katalog Parfum</a>
        <a href="laporan.php" class="active">📊 Laporan Pemasukan</a>
    </div>
</div>

<div class="main">
    <div class="header">
        <div>
            <h2>Laporan Pemasukan</h2>
            <p>Kelola data pemasukan / penjualan MYEGO</p>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="grid-container">
        
        <!-- Bagian Form Input Pemasukan -->
        <div class="content-box">
            <h3>+ Tambah Pemasukan</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Keterangan Penjualan</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Terjual 2 parfum Baccarat..." required>
                </div>
                <div class="form-group">
                    <label>Jumlah Pemasukan (Rp)</label>
                    <input type="number" name="jumlah" placeholder="Contoh: 10000000" min="0" required>
                </div>
                <button type="submit" name="simpan" class="btn-simpan">Simpan Pemasukan</button>
            </form>
        </div>

        <!-- Bagian Tabel Data Pemasukan -->
        <div class="content-box">
            <h3>Daftar Pemasukan Terakhir</h3>
            <div style="overflow-x: auto;">
                <table>
                    <tr>
                        <th width="5%">No</th>
                        <th width="20%">Tanggal</th>
                        <th width="45%">Keterangan</th>
                        <th width="20%">Jumlah (Rp)</th>
                        <th width="10%">Aksi</th>
                    </tr>
                    <?php
                    $no=1;
                    if(mysqli_num_rows($data_pemasukan) > 0){
                        while($d = mysqli_fetch_array($data_pemasukan)){
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($d['tanggal'])); ?></td>
                        <td><?php echo $d['keterangan']; ?></td>
                        <td>Rp <?php echo number_format($d['jumlah'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="?hapus=<?php echo $d['id']; ?>" class="hapus" onclick="return confirm('Yakin ingin menghapus data pemasukan ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding: 20px;'>Belum ada data pemasukan</td></tr>";
                    }
                    ?>
                </table>
            </div>

            <div class="total-box">
                Total Pemasukan: Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>
