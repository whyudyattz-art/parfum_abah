<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Mengambil semua data parfum
$data_parfum = mysqli_query($koneksi, "SELECT * FROM parfum ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Katalog Parfum MYEGO</title>
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

/* Grid Katalog */
.katalog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.katalog-card {
    background: white;
    border-radius: 15px;
    padding: 25px 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    text-align: center;
    transition: all 0.3s ease;
    border-top: 5px solid gold;
}

.katalog-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.katalog-img-container {
    width: 100%;
    height: 200px;
    background: #fdfbfb;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.katalog-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.katalog-card:hover .katalog-img-container img {
    transform: scale(1.1);
}

.katalog-placeholder {
    font-size: 60px;
    line-height: 200px;
    background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.katalog-title {
    font-size: 18px;
    font-weight: 700;
    color: #222;
    margin-bottom: 5px;
    line-height: 1.3;
}

.katalog-brand {
    font-size: 14px;
    color: #888;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.katalog-price {
    font-size: 20px;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 15px;
}

.katalog-stock {
    font-size: 13px;
    font-weight: 600;
    background: #f8f9fa;
    color: #495057;
    padding: 8px 15px;
    border-radius: 20px;
    display: inline-block;
    border: 1px solid #dee2e6;
}
.katalog-stock span {
    color: #007bff;
}

</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">MYEGO</div>
    <div class="menu">
        <a href="index.php">🏠 Dashboard</a>
        <a href="katalog.php" class="active">📦 Katalog Parfum</a>
        <a href="laporan.php">📊 Laporan Pemasukan</a>
    </div>
</div>

<div class="main">
    <div class="header">
        <div>
            <h2>Tampilan Katalog Parfum</h2>
            <p>Daftar semua produk parfum yang tersedia</p>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="katalog-grid">
        <?php
        if(mysqli_num_rows($data_parfum) > 0){
            while($d = mysqli_fetch_array($data_parfum)){
        ?>
        <div class="katalog-card">
            <div class="katalog-img-container">
                <?php if(!empty($d['gambar']) && file_exists('uploads/' . $d['gambar'])): ?>
                    <img src="uploads/<?php echo $d['gambar']; ?>" alt="<?php echo htmlspecialchars($d['nama_parfum']); ?>">
                <?php else: ?>
                    <div class="katalog-placeholder">🧴</div>
                <?php endif; ?>
            </div>
            <div class="katalog-title"><?php echo htmlspecialchars($d['nama_parfum']); ?></div>
            <div class="katalog-brand"><?php echo htmlspecialchars($d['merek']); ?></div>
            <div class="katalog-price">Rp <?php echo number_format($d['harga'], 0, ',', '.'); ?></div>
            <div class="katalog-stock">
                Sisa Stok: <span><?php echo $d['stok']; ?> pcs</span>
            </div>
        </div>
        <?php 
            }
        } else {
            echo "<div style='grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 15px;'><h3>Belum ada produk parfum.</h3></div>";
        }
        ?>
    </div>
</div>

</body>
</html>
