<?php
include 'koneksi.php';

echo "<h2>Proses Input Data Dummy...</h2>";

// 1. Input Data Admin
$username = 'admin';
$password = 'admin123';

// Cek apakah admin sudah ada agar tidak duplikat
$cek_admin = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='$username'");
if (mysqli_num_rows($cek_admin) == 0) {
    $query_admin = "INSERT INTO admin (username, password) VALUES ('$username', '$password')";
    if(mysqli_query($koneksi, $query_admin)){
        echo "✅ Data admin (username: admin) berhasil ditambahkan.<br>";
    } else {
        echo "❌ Gagal menambahkan data admin: " . mysqli_error($koneksi) . "<br>";
    }
} else {
    echo "⚠️ Data admin sudah ada.<br>";
}

echo "<br>";

// 2. Input Data Parfum
$data_parfum = [
    ['Baccarat Rouge 540', 'Maison Francis Kurkdjian', 5000000, 10],
    ['Black Opium', 'Yves Saint Laurent', 2500000, 15],
    ['Sauvage', 'Dior', 3000000, 20],
    ['Bleu de Chanel', 'Chanel', 2800000, 12],
    ['Aventus', 'Creed', 6000000, 5],
    ['English Pear & Freesia', 'Jo Malone', 2200000, 18],
    ['Cloud', 'Ariana Grande', 1500000, 25],
    ['Santal 33', 'Le Labo', 4500000, 8],
    ['Tobacco Vanille', 'Tom Ford', 5500000, 7],
    ['Daisy', 'Marc Jacobs', 1800000, 22]
];

$inserted_count = 0;

foreach ($data_parfum as $parfum) {
    $nama_parfum = $parfum[0];
    $merek = $parfum[1];
    $harga = $parfum[2];
    $stok = $parfum[3];

    // Cek apakah parfum dengan nama yang sama sudah ada di database
    $cek_parfum = mysqli_query($koneksi, "SELECT * FROM parfum WHERE nama_parfum='$nama_parfum'");
    if (mysqli_num_rows($cek_parfum) == 0) {
        $query_parfum = "INSERT INTO parfum (nama_parfum, merek, harga, stok) VALUES ('$nama_parfum', '$merek', '$harga', '$stok')";
        if(mysqli_query($koneksi, $query_parfum)){
            $inserted_count++;
        } else {
            echo "❌ Gagal menambahkan parfum $nama_parfum: " . mysqli_error($koneksi) . "<br>";
        }
    }
}

if ($inserted_count > 0) {
    echo "✅ Sebanyak <b>$inserted_count</b> data parfum baru berhasil ditambahkan.<br>";
} else {
    echo "⚠️ Semua data parfum tersebut sudah ada di database (tidak ada penambahan data baru).<br>";
}

echo "<br><br><a href='index.php' style='padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Kembali ke Dashboard</a>";
?>
