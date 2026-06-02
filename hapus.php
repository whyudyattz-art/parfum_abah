<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

if(isset($_GET['id'])){

    $id = intval($_GET['id']);

    $hapus = mysqli_query($koneksi,
    "DELETE FROM parfum WHERE id='$id'");

    if($hapus){
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