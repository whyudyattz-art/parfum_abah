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

    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $merek = mysqli_real_escape_string($koneksi, $_POST['merek']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
    $stok  = mysqli_real_escape_string($koneksi, $_POST['stok']);
    
    $gambar = $d['gambar']; // Keep old image by default

    // Cek jika ada file gambar baru diunggah
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
        $nama_file = $_FILES['gambar']['name'];
        $ukuran_file = $_FILES['gambar']['size'];
        $tmp_name = $_FILES['gambar']['tmp_name'];

        $ekstensiGambarValid = ['jpg', 'jpeg', 'png', 'webp'];
        $ekstensiGambar = explode('.', $nama_file);
        $ekstensiGambar = strtolower(end($ekstensiGambar));

        if (!in_array($ekstensiGambar, $ekstensiGambarValid)) {
            echo "
            <script>
                alert('Format gambar tidak valid! Harus JPG, JPEG, PNG, atau WEBP.');
                window.history.back();
            </script>
            ";
            exit;
        }

        if ($ukuran_file > 2097152) { // 2MB limit
            echo "
            <script>
                alert('Ukuran gambar terlalu besar! Maksimal 2MB.');
                window.history.back();
            </script>
            ";
            exit;
        }

        // Buat nama file unik baru
        $namaFileBaru = uniqid() . '.' . $ekstensiGambar;
        $tujuan = 'uploads/' . $namaFileBaru;

        if (move_uploaded_file($tmp_name, $tujuan)) {
            // Hapus gambar lama jika ada dan bukan gambar bawaan/default
            $default_images = ['default.jpg', 'baccarat.jpg', 'black_opium.jpg', 'sauvage.jpg', 'aventus.jpg'];
            if (!empty($d['gambar']) && !in_array($d['gambar'], $default_images)) {
                $old_file = 'uploads/' . $d['gambar'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $gambar = $namaFileBaru;
        } else {
            echo "
            <script>
                alert('Gagal mengunggah gambar ke server.');
                window.history.back();
            </script>
            ";
            exit;
        }
    }

    $update = mysqli_query($koneksi,"
    UPDATE parfum SET
    nama_parfum='$nama',
    merek='$merek',
    harga='$harga',
    stok='$stok',
    gambar='$gambar'
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

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group" style="text-align: center; margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px;">Foto Saat Ini</label>
                <?php if(!empty($d['gambar']) && file_exists('uploads/' . $d['gambar'])): ?>
                    <img src="uploads/<?php echo $d['gambar']; ?>" alt="Foto Parfum" style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 2px solid gold; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: inline-block;">
                <?php else: ?>
                    <div style="width: 120px; height: 120px; line-height: 120px; background: #e9ecef; border-radius: 10px; display: inline-block; font-size: 50px; text-align: center;">🧴</div>
                <?php endif; ?>
            </div>

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

            <div class="form-group">
                <label>Ganti Foto Parfum</label>
                <input type="file" name="gambar" accept="image/*">
                <small style="color:#666; font-size: 12px; display: block; margin-top: 5px;">Format: JPG, JPEG, PNG, WEBP. Maks 2MB. Biarkan kosong jika tidak ingin mengubah foto.</small>
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