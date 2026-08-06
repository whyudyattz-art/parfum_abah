<?php
$koneksi = mysqli_connect("localhost","root","","parfum_abah");

if(!$koneksi){
    die("Koneksi Gagal : ".mysqli_connect_error());
}

// Buat tabel transaksi secara otomatis jika belum ada
$create_transaksi = "CREATE TABLE IF NOT EXISTS transaksi (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATETIME NOT NULL,
    nama_pelanggan VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    id_parfum INT(11) NOT NULL,
    jumlah INT(11) NOT NULL,
    total_harga INT(11) NOT NULL,
    metode_pembayaran VARCHAR(50) NOT NULL,
    bukti_pembayaran VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending'
)";
mysqli_query($koneksi, $create_transaksi);
?>