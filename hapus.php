<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

if(isset($_GET['id'])){

    $id = intval($_GET['id']);

    // Ambil info gambar sebelum data dihapus
    $query_gambar = mysqli_query($koneksi, "SELECT gambar FROM parfum WHERE id='$id'");
    $data_gambar = mysqli_fetch_assoc($query_gambar);

    $hapus = mysqli_query($koneksi,
    "DELETE FROM parfum WHERE id='$id'");

    if($hapus){
        // Hapus file gambar jika ada dan bukan gambar bawaan/default
        if ($data_gambar && !empty($data_gambar['gambar'])) {
            $default_images = ['default.jpg', 'baccarat.jpg', 'black_opium.jpg', 'sauvage.jpg', 'aventus.jpg'];
            if (!in_array($data_gambar['gambar'], $default_images)) {
                $file_path = 'uploads/' . $data_gambar['gambar'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        echo "
        <script>
            alert('Data parfum berhasil dihapus');
            window.location='index.php';
        </script>
        ";
    }else{
        echo "
        <script>
            alert('Gagal menghapus data');
            window.location='index.php';
        </script>
        ";
    }

}else{

    header("Location: index.php");
    exit();

}
?>