<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Menangani Unggah Logo Baru
if(isset($_POST['upload_logo'])){
    if(isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === 0) {
        $nama_file = $_FILES['logo_file']['name'];
        $ukuran_file = $_FILES['logo_file']['size'];
        $tmp_name = $_FILES['logo_file']['tmp_name'];

        $ekstensiValid = ['jpg', 'jpeg', 'png', 'webp'];
        $ekstensi = explode('.', $nama_file);
        $ekstensi = strtolower(end($ekstensi));

        if (!in_array($ekstensi, $ekstensiValid)) {
            echo "
            <script>
                alert('Format logo tidak valid! Harus JPG, JPEG, PNG, atau WEBP.');
                window.history.back();
            </script>
            ";
            exit;
        }

        if ($ukuran_file > 2097152) { // 2MB
            echo "
            <script>
                alert('Ukuran file logo terlalu besar! Maksimal 2MB.');
                window.history.back();
            </script>
            ";
            exit;
        }

        // Hapus logo yang lama jika ada
        $old_logos = glob('uploads/logo_app.*');
        foreach($old_logos as $old) {
            if(file_exists($old)) {
                unlink($old);
            }
        }

        // Simpan logo baru
        $namaFileBaru = 'logo_app.' . $ekstensi;
        $tujuan = 'uploads/' . $namaFileBaru;

        if (move_uploaded_file($tmp_name, $tujuan)) {
            echo "
            <script>
                alert('Logo website berhasil diperbarui!');
                window.location='logo.php';
            </script>
            ";
            exit;
        } else {
            echo "
            <script>
                alert('Gagal mengunggah logo ke server.');
                window.history.back();
            </script>
            ";
            exit;
        }
    } else {
        echo "
        <script>
            alert('Silakan pilih file logo terlebih dahulu.');
            window.history.back();
        </script>
        ";
        exit;
    }
}

// Menangani Hapus Logo (Reset ke Teks)
if(isset($_POST['delete_logo'])) {
    $old_logos = glob('uploads/logo_app.*');
    foreach($old_logos as $old) {
        if(file_exists($old)) {
            unlink($old);
        }
    }
    echo "
    <script>
        alert('Logo website berhasil dihapus, kembali ke logo teks default.');
        window.location='logo.php';
    </script>
    ";
    exit;
}

// Mengambil logo saat ini
$logo_files = glob('uploads/logo_app.*');
$logo_exists = !empty($logo_files);
$logo_path = $logo_exists ? $logo_files[0] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan Logo - MYEGO</title>

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
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo img {
    max-width: 100%;
    max-height: 60px;
    object-fit: contain;
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

.menu a.active{
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

.content-container {
    margin-top: 30px;
    max-width: 600px;
}

.card{
    background:white;
    padding:35px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.1);
}

.card h3 {
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #f5f7fb;
    padding-bottom: 10px;
}

.preview-box {
    background: #f8f9fa;
    border: 2px dashed #ddd;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    margin-bottom: 25px;
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.preview-box img {
    max-width: 100%;
    max-height: 120px;
    object-fit: contain;
    border-radius: 8px;
}

.preview-placeholder {
    font-size: 24px;
    font-weight: bold;
    color: gold;
    background: #222;
    padding: 15px 30px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color: #444;
}

input[type="file"]{
    width:100%;
    padding:10px;
    border:1px solid #ddd;
    border-radius:8px;
    background: #fff;
    cursor: pointer;
}

.btn-group {
    display: flex;
    gap: 10px;
}

.btn{
    flex: 1;
    padding:12px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:15px;
    font-weight:600;
    transition: 0.2s;
    text-align: center;
}

.btn-upload{
    background:gold;
    color:#000;
}

.btn-upload:hover{
    background:#e6c200;
}

.btn-delete{
    background:#dc3545;
    color:white;
}

.btn-delete:hover{
    background:#bd2130;
}

</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <?php if ($logo_exists): ?>
            <img src="<?php echo $logo_path; ?>?v=<?php echo filemtime($logo_path); ?>" alt="Logo">
        <?php else: ?>
            MYEGO
        <?php endif; ?>
    </div>
    
    <div class="menu">
        <a href="index.php">🏠 Dashboard</a>
        <a href="katalog.php">📦 Katalog Parfum</a>
        <a href="laporan.php">📊 Laporan Pemasukan</a>
        <a href="logo.php" class="active">🖼️ Pengaturan Logo</a>
    </div>
</div>

<div class="main">
    <div class="header">
        <div>
            <h2>Pengaturan Logo Website</h2>
            <p>Kelola logo toko/website MYEGO</p>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="content-container">
        <div class="card">
            <h3>Logo Saat Ini</h3>
            
            <div class="preview-box">
                <?php if ($logo_exists): ?>
                    <img src="<?php echo $logo_path; ?>?v=<?php echo filemtime($logo_path); ?>" alt="Logo Toko">
                    <p style="margin-top: 15px; font-size: 13px; color: #666; font-weight: 500;">Format kustom aktif</p>
                <?php else: ?>
                    <div class="preview-placeholder">MYEGO</div>
                    <p style="margin-top: 15px; font-size: 13px; color: #666; font-weight: 500;">Menggunakan teks default</p>
                <?php endif; ?>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Pilih File Logo Baru</label>
                    <input type="file" name="logo_file" accept="image/*" required>
                    <small style="color:#666; font-size: 12px; display: block; margin-top: 5px;">Format yang didukung: JPG, JPEG, PNG, WEBP. Ukuran file maksimal 2MB.</small>
                </div>

                <div class="btn-group">
                    <button type="submit" name="upload_logo" class="btn btn-upload">
                        Simpan Logo Baru
                    </button>
                    
                    <?php if ($logo_exists): ?>
                        <button type="submit" name="delete_logo" class="btn btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus logo kustom dan kembali menggunakan teks default?')">
                            Hapus & Reset
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
