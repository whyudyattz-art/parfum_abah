<?php
session_start();
include 'koneksi.php';

// AJAX Order Tracking Handler
if (isset($_GET['ajax_track'])) {
    header('Content-Type: application/json');
    $telepon = mysqli_real_escape_string($koneksi, $_GET['ajax_track']);
    $query = "SELECT t.*, p.nama_parfum, p.merek FROM transaksi t 
              JOIN parfum p ON t.id_parfum = p.id 
              WHERE t.telepon = '$telepon' 
              ORDER BY t.tanggal DESC";
    $result = mysqli_query($koneksi, $query);
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['formatted_price'] = 'Rp ' . number_format($row['total_harga'], 0, ',', '.');
        $row['formatted_date'] = date('d-m-Y H:i', strtotime($row['tanggal']));
        $orders[] = $row;
    }
    echo json_encode($orders);
    exit;
}

// Rekening Pembayaran Toko
$rekening_toko = [
    'BCA' => ['norek' => '1234-5678-90', 'atas_nama' => 'Parfum Abah MYEGO'],
    'Mandiri' => ['norek' => '987-65-43210', 'atas_nama' => 'Parfum Abah MYEGO'],
    'DANA' => ['norek' => '0812-3456-7890', 'atas_nama' => 'MYEGO E-Wallet']
];

$alert_message = "";
$alert_type = "";
$success_trx = null;

// Proses Form Checkout Transaksi
if (isset($_POST['submit_checkout'])) {
    $id_parfum = intval($_POST['id_parfum']);
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $jumlah = intval($_POST['jumlah']);
    $metode_pembayaran = mysqli_real_escape_string($koneksi, $_POST['metode_pembayaran']);
    
    // Ambil info produk
    $res_p = mysqli_query($koneksi, "SELECT * FROM parfum WHERE id='$id_parfum'");
    $prod = mysqli_fetch_assoc($res_p);
    
    if (!$prod) {
        $alert_message = "Produk tidak ditemukan!";
        $alert_type = "error";
    } elseif ($prod['stok'] < $jumlah) {
        $alert_message = "Stok tidak mencukupi! Stok saat ini: " . $prod['stok'] . " pcs.";
        $alert_type = "error";
    } else {
        // Cek file bukti transfer
        if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === 0) {
            $nama_file = $_FILES['bukti_pembayaran']['name'];
            $ukuran_file = $_FILES['bukti_pembayaran']['size'];
            $tmp_name = $_FILES['bukti_pembayaran']['tmp_name'];
            
            $ekstensiValid = ['jpg', 'jpeg', 'png', 'webp'];
            $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
            
            if (!in_array($ekstensi, $ekstensiValid)) {
                $alert_message = "Format bukti transfer tidak valid! Harus JPG, JPEG, PNG, atau WEBP.";
                $alert_type = "error";
            } elseif ($ukuran_file > 2097152) { // 2MB
                $alert_message = "Ukuran file terlalu besar! Maksimal 2MB.";
                $alert_type = "error";
            } else {
                // Simpan bukti pembayaran ke uploads/bukti/
                $namaFileBaru = uniqid() . '.' . $ekstensi;
                $tujuan = 'uploads/bukti/' . $namaFileBaru;
                
                if (move_uploaded_file($tmp_name, $tujuan)) {
                    $total_harga = $prod['harga'] * $jumlah;
                    $kode_transaksi = "TRX-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -5));
                    $tanggal = date('Y-m-d H:i:s');
                    
                    // Insert ke tabel transaksi
                    $query_ins = "INSERT INTO transaksi 
                        (kode_transaksi, tanggal, nama_pelanggan, telepon, alamat, id_parfum, jumlah, total_harga, metode_pembayaran, bukti_pembayaran, status)
                        VALUES 
                        ('$kode_transaksi', '$tanggal', '$nama_pelanggan', '$telepon', '$alamat', '$id_parfum', '$jumlah', '$total_harga', '$metode_pembayaran', '$namaFileBaru', 'pending')";
                    
                    if (mysqli_query($koneksi, $query_ins)) {
                        // Kurangi stok parfum
                        $stok_baru = $prod['stok'] - $jumlah;
                        mysqli_query($koneksi, "UPDATE parfum SET stok='$stok_baru' WHERE id='$id_parfum'");
                        
                        $alert_message = "Pesanan berhasil dibuat! Kode Transaksi Anda: " . $kode_transaksi;
                        $alert_type = "success";
                        $success_trx = [
                            'kode' => $kode_transaksi,
                            'produk' => $prod['nama_parfum'],
                            'qty' => $jumlah,
                            'total' => $total_harga,
                            'metode' => $metode_pembayaran
                        ];
                    } else {
                        $alert_message = "Gagal memproses transaksi: " . mysqli_error($koneksi);
                        $alert_type = "error";
                    }
                } else {
                    $alert_message = "Gagal mengunggah bukti transfer.";
                    $alert_type = "error";
                }
            }
        } else {
            $alert_message = "Silakan unggah bukti transfer.";
            $alert_type = "error";
        }
    }
}

// Ambil data logo jika ada
$logo_files = glob('uploads/logo_app.*');
$logo_exists = !empty($logo_files);
$logo_path = $logo_exists ? $logo_files[0] : '';

// Ambil daftar merek unik untuk filter
$query_merek = mysqli_query($koneksi, "SELECT DISTINCT merek FROM parfum ORDER BY merek ASC");
$daftar_merek = [];
while($row = mysqli_fetch_assoc($query_merek)) {
    if(!empty($row['merek'])) {
        $daftar_merek[] = $row['merek'];
    }
}

// Ambil semua data parfum untuk katalog
$query_parfum = mysqli_query($koneksi, "SELECT * FROM parfum ORDER BY id DESC");
$parfums = [];
while($row = mysqli_fetch_assoc($query_parfum)) {
    $parfums[] = $row;
}

// WhatsApp Config
$whatsapp_number = "6281234567890";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MYEGO Perfume Store | Eksklusif & Mewah</title>
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0b0b;
            --bg-card: #141414;
            --primary: #d4af37; /* Gold */
            --primary-hover: #b89329;
            --text-color: #f5f5f5;
            --text-muted: #a0a0a0;
            --border-color: rgba(212, 175, 55, 0.2);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #000;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-hover);
        }

        /* Header / Navbar */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(11, 11, 11, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        header.scrolled {
            padding: 10px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .navbar {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 2px;
            transition: var(--transition);
        }

        .logo img {
            max-height: 45px;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 20px;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: var(--transition);
            position: relative;
            padding: 5px 0;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .btn-track {
            border: 1px solid var(--primary);
            color: var(--primary) !important;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 600 !important;
            background: transparent;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-track:hover {
            background: rgba(212, 175, 55, 0.1);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.2);
        }

        .btn-admin {
            background: var(--primary);
            color: #000 !important;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 700 !important;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-admin:hover {
            background: var(--primary-hover);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
        }

        /* Hero Section */
        .hero {
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 120px 20px 80px;
            background: radial-gradient(circle at center, rgba(30, 25, 10, 0.7) 0%, rgba(11, 11, 11, 1) 70%);
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&q=80&w=1920') no-repeat center center/cover;
            opacity: 0.12;
            pointer-events: none;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            text-align: center;
        }

        .hero-tagline {
            color: var(--primary);
            text-transform: uppercase;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 4px;
            margin-bottom: 20px;
            display: inline-block;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 5px;
        }

        .hero-content h1 {
            font-size: 56px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 25px;
            background: linear-gradient(135deg, #fff 30%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }

        .hero-content p {
            font-size: 17px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 40px;
            font-weight: 300;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            padding: 14px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-color);
            padding: 14px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Catalog / Shop Section */
        .shop-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .section-header h2 span {
            color: var(--primary);
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
            font-weight: 300;
        }

        /* Filter Controls */
        .filter-controls {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 40px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 12px 20px 12px 45px;
            border-radius: 30px;
            color: #fff;
            font-size: 15px;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 18px;
        }

        .filter-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: #fff;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            outline: none;
        }

        .filter-select:focus {
            border-color: var(--primary);
        }

        .filter-select option {
            background: #141414;
            color: #fff;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            border: 1px solid transparent;
            background: linear-gradient(135deg, var(--primary), transparent) border-box;
            -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: destination-out;
            mask-composite: exclude;
            opacity: 0;
            transition: var(--transition);
            pointer-events: none;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(212, 175, 55, 0.15);
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-img-container {
            width: 100%;
            height: 250px;
            background: #090909;
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.02);
        }

        .product-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .product-card:hover .product-img-container img {
            transform: scale(1.1);
        }

        .product-placeholder {
            font-size: 70px;
            background: linear-gradient(135deg, #1f1f1f 0%, #0d0d0d 100%);
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .badge-stok {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 11px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 30px;
            z-index: 5;
            letter-spacing: 0.5px;
        }

        .badge-stok.ready {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.4);
        }

        .badge-stok.empty {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.4);
        }

        .product-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-brand {
            font-size: 11px;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 19px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 21px;
            font-weight: 800;
            color: #fff;
        }

        .btn-card-buy {
            background: var(--primary);
            color: #000;
            width: 100%;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-card-buy:hover {
            background: var(--primary-hover);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }

        .btn-card-detail {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-color);
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: var(--transition);
        }

        .btn-card-detail:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Detail Modal (Glassmorphism & Gold Theme) */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s ease;
            padding: 20px;
        }

        .modal.open {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
        }

        .modal-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            max-width: 800px;
            width: 100%;
            border-radius: 24px;
            position: relative;
            z-index: 2005;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            transform: scale(0.85);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .modal.open .modal-container {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2010;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--primary);
            color: #000;
            border-color: var(--primary);
            transform: rotate(90deg);
        }

        .modal-img {
            background: #090909;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            min-height: 400px;
        }

        .modal-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-img .product-placeholder {
            min-height: 400px;
        }

        .modal-content {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .modal-brand {
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .modal-title {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .modal-price {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 25px;
        }

        .modal-desc {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .modal-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px 0;
        }

        .meta-item {
            font-size: 13px;
            color: var(--text-muted);
        }

        .meta-item span {
            color: #fff;
            font-weight: 600;
        }

        .btn-modal-buy {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .btn-modal-buy:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
        }

        /* Form Controls inside Modals */
        .form-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-height: 75vh;
            overflow-y: auto;
            padding-right: 5px;
        }

        .form-group-modal {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group-modal label {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            letter-spacing: 0.5px;
        }

        .form-group-modal input, .form-group-modal select, .form-group-modal textarea {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(212, 175, 55, 0.2);
            color: #fff;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: var(--transition);
        }

        .form-group-modal input:focus, .form-group-modal select:focus, .form-group-modal textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.2);
        }

        .payment-info-box {
            background: rgba(212, 175, 55, 0.05);
            border: 1px dashed var(--primary);
            padding: 15px;
            border-radius: 10px;
            font-size: 13px;
            color: #fff;
            line-height: 1.6;
        }

        .payment-info-box strong {
            color: var(--primary);
            font-size: 14px;
        }

        .trx-summary {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .trx-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .trx-summary-row:last-child {
            margin-bottom: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 8px;
            font-weight: 700;
        }

        .trx-summary-row span:last-child {
            color: var(--primary);
        }

        /* Order status styles */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-badge.lunas {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-badge.dibatalkan {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Alert / Alert Popups */
        .custom-alert {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--bg-card);
            border-left: 5px solid var(--primary);
            border-top: 1px solid rgba(212, 175, 55, 0.1);
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            border-right: 1px solid rgba(212, 175, 55, 0.1);
            padding: 20px 25px;
            border-radius: 10px;
            z-index: 3000;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            max-width: 450px;
        }

        .custom-alert.error {
            border-left-color: #dc3545;
        }

        .custom-alert.success {
            border-left-color: #28a745;
        }

        .custom-alert-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            margin-left: auto;
            transition: var(--transition);
        }

        .custom-alert-close:hover {
            color: #fff;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Order Tracking Table styling */
        .tracking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tracking-table th {
            text-align: left;
            background: rgba(212, 175, 55, 0.1);
            color: var(--primary);
            padding: 10px;
            font-size: 13px;
            border-bottom: 1px solid var(--border-color);
        }

        .tracking-table td {
            padding: 12px 10px;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .tracking-table tr:hover {
            background: rgba(255, 255, 255, 0.01);
        }

        /* Contact & CTA Section */
        .cta-section {
            background: linear-gradient(180deg, var(--bg-color) 0%, #110f0a 100%);
            border-top: 1px solid var(--border-color);
            padding: 80px 20px;
            text-align: center;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-container h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .cta-container p {
            color: var(--text-muted);
            margin-bottom: 35px;
            line-height: 1.8;
            font-weight: 300;
        }

        /* Footer */
        footer {
            background: #050505;
            padding: 50px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-about h3, .footer-links h3, .footer-contact h3 {
            color: var(--primary);
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .footer-about p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.7;
            font-weight: 300;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary);
            padding-left: 5px;
        }

        .footer-contact p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 300;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom p {
            color: var(--text-muted);
            font-size: 13px;
        }

        .admin-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: var(--transition);
        }

        .admin-link:hover {
            color: var(--primary);
        }

        /* Responsive styling */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 20px;
            }
            .hero-content h1 {
                font-size: 38px;
            }
            .modal-container {
                grid-template-columns: 1fr;
            }
            .modal-img {
                min-height: 250px;
                height: 250px;
            }
            .modal-img img {
                height: 100%;
            }
            .modal-content {
                padding: 25px;
            }
            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-options {
                flex-direction: column;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header id="header">
        <div class="navbar">
            <a href="#home" class="logo">
                <?php if ($logo_exists): ?>
                    <img src="<?php echo $logo_path; ?>?v=<?php echo filemtime($logo_path); ?>" alt="MYEGO Logo">
                <?php else: ?>
                    MYEGO
                <?php endif; ?>
            </a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#catalog">Katalog</a></li>
                <li><a href="#contact">Hubungi Kami</a></li>
                <li><button onclick="openTrackModal()" class="btn-track">🔍 Lacak Pesanan</button></li>
                <li><a href="login.php" class="btn-admin">Area Admin</a></li>
            </ul>
        </div>
    </header>

    <!-- Custom Alert Popups -->
    <?php if(!empty($alert_message)): ?>
        <div class="custom-alert <?php echo $alert_type; ?>" id="customAlert">
            <div>
                <strong style="display: block; font-size: 15px; margin-bottom: 2px;">
                    <?php echo $alert_type === 'success' ? '✅ Berhasil' : '❌ Gagal'; ?>
                </strong>
                <span style="font-size: 13px; color: var(--text-muted);"><?php echo $alert_message; ?></span>
            </div>
            <button class="custom-alert-close" onclick="document.getElementById('customAlert').remove()">×</button>
        </div>
        <script>
            setTimeout(() => {
                const el = document.getElementById('customAlert');
                if(el) el.remove();
            }, 8000);
        </script>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <span class="hero-tagline">Signature Scent Collection</span>
            <h1>The Essence of Luxury & Personality</h1>
            <p>Temukan aroma eksklusif yang mewakili kepribadian unik Anda. Diramu secara presisi menggunakan sari parfum premium berkualitas tinggi untuk keharuman elegan dan memikat yang bertahan sepanjang hari.</p>
            <div class="hero-buttons">
                <a href="#catalog" class="btn-primary">Jelajahi Koleksi</a>
                <a href="#contact" class="btn-secondary">Konsultasi Aroma</a>
            </div>
        </div>
    </section>

    <!-- Catalog Section -->
    <section class="shop-section" id="catalog">
        <div class="section-header">
            <h2>Koleksi <span>Parfum Terbaik</span></h2>
            <p>Pilih dan temukan aroma favorit Anda dari jajaran mahakarya parfum eksklusif MYEGO.</p>
        </div>

        <!-- Filter & Search Controls -->
        <div class="filter-controls">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Cari parfum impian Anda..." onkeyup="filterProducts()">
            </div>
            <div class="filter-options">
                <select id="brandFilter" class="filter-select" onchange="filterProducts()">
                    <option value="">Semua Merek</option>
                    <?php foreach($daftar_merek as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="stockFilter" class="filter-select" onchange="filterProducts()">
                    <option value="">Semua Stok</option>
                    <option value="ready">Ready Stock</option>
                    <option value="empty">Stok Habis</option>
                </select>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid" id="productGrid">
            <?php if(count($parfums) > 0): ?>
                <?php foreach($parfums as $p): 
                    $is_ready = $p['stok'] > 0;
                    $formatted_price = number_format($p['harga'], 0, ',', '.');
                ?>
                    <div class="product-card" 
                         data-name="<?php echo strtolower(htmlspecialchars($p['nama_parfum'])); ?>" 
                         data-brand="<?php echo strtolower(htmlspecialchars($p['merek'])); ?>"
                         data-stock="<?php echo $is_ready ? 'ready' : 'empty'; ?>">
                        
                        <span class="badge-stok <?php echo $is_ready ? 'ready' : 'empty'; ?>">
                            <?php echo $is_ready ? 'Ready Stock' : 'Habis'; ?>
                        </span>
                        
                        <div class="product-img-container">
                            <?php if(!empty($p['gambar']) && file_exists('uploads/' . $p['gambar'])): ?>
                                <img src="uploads/<?php echo $p['gambar']; ?>" alt="<?php echo htmlspecialchars($p['nama_parfum']); ?>">
                            <?php else: ?>
                                <div class="product-placeholder">🧴</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div>
                                <div class="product-brand"><?php echo htmlspecialchars($p['merek']); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($p['nama_parfum']); ?></h3>
                            </div>
                            
                            <div>
                                <div class="price-row">
                                    <div class="product-price">Rp <?php echo $formatted_price; ?></div>
                                </div>
                                
                                <button class="btn-card-buy" onclick="openDetailModal(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                                    🛍️ Detail & Beli
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: 20px; border: 1px dashed var(--border-color);">
                    <h3 style="font-size: 20px; color: var(--primary); margin-bottom: 10px;">Belum Ada Produk</h3>
                    <p style="color: var(--text-muted);">Silakan kembali beberapa saat lagi. Kami sedang menyiapkan koleksi parfum terbaik untuk Anda.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-overlay" onclick="closeDetailModal()"></div>
        <div class="modal-container">
            <button class="modal-close" onclick="closeDetailModal()">×</button>
            <div class="modal-img" id="modalImgContainer">
                <!-- Image injected here by JS -->
            </div>
            <div class="modal-content">
                <div class="modal-brand" id="modalBrand">BRAND</div>
                <h2 class="modal-title" id="modalTitle">Nama Parfum</h2>
                <div class="modal-price" id="modalPrice">Rp 0</div>
                <p class="modal-desc">
                    Nikmati sensasi keharuman berkelas dari varian istimewa ini. Dirancang dengan komposisi aroma yang seimbang dan elegan, parfum ini memberikan daya tahan yang maksimal untuk menemani aktivitas harian Anda dengan penuh rasa percaya diri.
                </p>
                <div class="modal-meta">
                    <div class="meta-item">Kategori: <span>Eau De Parfum</span></div>
                    <div class="meta-item">Stok: <span id="modalStock">0 pcs</span></div>
                </div>
                
                <div id="modalActionContainer">
                    <!-- Checkout button injected here by JS based on stock -->
                </div>
                
                <a href="#" class="btn-card-detail" id="modalBuyBtn" target="_blank" style="text-align: center; display: block; margin-top: 10px;">
                    💬 Tanya Admin via WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal" id="checkoutModal">
        <div class="modal-overlay" onclick="closeCheckoutModal()"></div>
        <div class="modal-container" style="max-width: 600px; grid-template-columns: 1fr;">
            <button class="modal-close" onclick="closeCheckoutModal()">×</button>
            <div class="modal-content" style="padding: 30px;">
                <div class="modal-brand" style="margin-bottom: 5px;">FORMULIR PEMBELIAN</div>
                <h2 class="modal-title" id="checkoutTitle" style="font-size: 22px; margin-bottom: 20px;">Nama Produk</h2>
                
                <form action="" method="POST" enctype="multipart/form-data" class="form-container">
                    <input type="hidden" name="id_parfum" id="checkoutIdParfum">
                    
                    <div class="trx-summary">
                        <div class="trx-summary-row">
                            <span>Harga Satuan:</span>
                            <span id="checkoutPriceLabel">Rp 0</span>
                        </div>
                        <div class="trx-summary-row">
                            <span>Jumlah Pesanan:</span>
                            <span><span id="checkoutQtyLabel">1</span> pcs</span>
                        </div>
                        <div class="trx-summary-row">
                            <span>Total Pembayaran:</span>
                            <span id="checkoutTotalLabel">Rp 0</span>
                        </div>
                    </div>

                    <div class="form-group-modal">
                        <label>Nama Lengkap Anda</label>
                        <input type="text" name="nama_pelanggan" required placeholder="Contoh: Budi Santoso">
                    </div>
                    
                    <div class="form-group-modal">
                        <label>Nomor WhatsApp (Aktif)</label>
                        <input type="tel" name="telepon" required placeholder="Contoh: 081234567890">
                    </div>
                    
                    <div class="form-group-modal">
                        <label>Alamat Pengiriman Lengkap</label>
                        <textarea name="alamat" rows="2" required placeholder="Contoh: Jl. Ahmad Yani No. 12, RT 03, Banjarmasin"></textarea>
                    </div>

                    <div class="form-group-modal">
                        <label>Jumlah Botol (Qty)</label>
                        <input type="number" name="jumlah" id="checkoutQtyInput" value="1" min="1" required oninput="updateCheckoutCalculation()">
                    </div>

                    <div class="form-group-modal">
                        <label>Metode Pembayaran Transfer</label>
                        <select name="metode_pembayaran" id="checkoutPaymentSelect" required onchange="updatePaymentDetails()">
                            <option value="BCA">Transfer Bank BCA</option>
                            <option value="Mandiri">Transfer Bank Mandiri</option>
                            <option value="DANA">E-Wallet DANA</option>
                        </select>
                    </div>

                    <div class="payment-info-box" id="paymentDetailsBox">
                        <!-- Bank details injected here by JS -->
                    </div>

                    <div class="form-group-modal">
                        <label>Unggah Bukti Transfer / Pembayaran</label>
                        <input type="file" name="bukti_pembayaran" accept="image/*" required>
                        <small style="color: var(--text-muted); font-size: 11px;">Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</small>
                    </div>

                    <button type="submit" name="submit_checkout" class="btn-modal-buy" style="margin-top: 10px; width: 100%;">
                        🚀 Kirim Transaksi & Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Tracking Modal -->
    <div class="modal" id="trackModal">
        <div class="modal-overlay" onclick="closeTrackModal()"></div>
        <div class="modal-container" style="max-width: 750px; grid-template-columns: 1fr;">
            <button class="modal-close" onclick="closeTrackModal()">×</button>
            <div class="modal-content" style="padding: 30px; display: block;">
                <div class="modal-brand" style="margin-bottom: 5px;">PELACAKAN PESANAN</div>
                <h2 class="modal-title" style="font-size: 22px; margin-bottom: 20px;">Lacak Status Pembelian & Pembayaran</h2>
                
                <div class="filter-controls" style="padding: 15px; margin-bottom: 20px; box-shadow: none;">
                    <div class="search-box" style="flex: 1;">
                        <span class="search-icon">📞</span>
                        <input type="text" id="trackPhoneInput" placeholder="Masukkan nomor WhatsApp pesanan Anda (contoh: 081234567890)">
                    </div>
                    <button class="btn-primary" onclick="searchOrders()" style="padding: 10px 25px; font-size: 14px; border-radius: 30px;">
                        Cari Pesanan
                    </button>
                </div>

                <div id="trackingResults" style="max-height: 40vh; overflow-y: auto;">
                    <p style="text-align: center; color: var(--text-muted); font-size: 14px; padding: 20px 0;">
                        Silakan masukkan nomor WhatsApp Anda di atas untuk melihat riwayat pesanan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Checkout Info Modal -->
    <?php if($success_trx): ?>
        <div class="modal open" id="successTrxModal">
            <div class="modal-overlay" onclick="document.getElementById('successTrxModal').classList.remove('open')"></div>
            <div class="modal-container" style="max-width: 500px; grid-template-columns: 1fr;">
                <button class="modal-close" onclick="document.getElementById('successTrxModal').classList.remove('open')">×</button>
                <div class="modal-content" style="padding: 30px; text-align: center;">
                    <div style="font-size: 60px; margin-bottom: 15px;">🎉</div>
                    <h2 class="modal-title" style="font-size: 22px; margin-bottom: 10px;">Transaksi Berhasil Dikirim!</h2>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px; line-height: 1.6;">
                        Terima kasih telah berbelanja di MYEGO. Transaksi Anda telah tercatat dengan kode <strong><?php echo $success_trx['kode']; ?></strong> dan sedang menunggu verifikasi pembayaran oleh admin.
                    </p>
                    
                    <div class="trx-summary" style="text-align: left;">
                        <div class="trx-summary-row">
                            <span>Kode:</span>
                            <span><?php echo $success_trx['kode']; ?></span>
                        </div>
                        <div class="trx-summary-row">
                            <span>Produk:</span>
                            <span><?php echo htmlspecialchars($success_trx['produk']); ?></span>
                        </div>
                        <div class="trx-summary-row">
                            <span>Jumlah:</span>
                            <span><?php echo $success_trx['qty']; ?> pcs</span>
                        </div>
                        <div class="trx-summary-row">
                            <span>Total Harga:</span>
                            <span>Rp <?php echo number_format($success_trx['total'], 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <?php
                    // WhatsApp message content for confirmation
                    $confirm_text = "Halo Admin MYEGO, saya telah melakukan checkout pemesanan dengan Kode Transaksi *" . $success_trx['kode'] . "* untuk produk *" . $success_trx['produk'] . "* (" . $success_trx['qty'] . " pcs) seharga *Rp " . number_format($success_trx['total'], 0, ',', '.') . "*. Mohon konfirmasi pembayaran saya. Terima kasih!";
                    $wa_confirm_url = "https://api.whatsapp.com/send?phone=" . $whatsapp_number . "&text=" . urlencode($confirm_text);
                    ?>
                    
                    <a href="<?php echo $wa_confirm_url; ?>" class="btn-modal-buy" target="_blank" style="width: 100%;">
                        💬 Konfirmasi via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contact & CTA Section -->
    <section class="cta-section" id="contact">
        <div class="cta-container">
            <span class="hero-tagline">Konsultasi Gratis</span>
            <h2>Temukan Aroma Karakter Anda</h2>
            <p>Masih bingung memilih parfum yang cocok untuk aktivitas harian, kencan, atau acara formal? Hubungi aromaterapis profesional kami untuk mendapatkan rekomendasi parfum yang paling sesuai dengan kepribadian Anda.</p>
            <a href="https://api.whatsapp.com/send?phone=<?php echo $whatsapp_number; ?>&text=Halo%20MYEGO%2C%20saya%20ingin%20konsultasi%20mengenai%20aroma%20parfum%20yang%20cocok%20untuk%20saya." class="btn-primary" target="_blank">
                Tanya Aromaterapis (WhatsApp)
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-about">
                <h3>MYEGO PERFUME</h3>
                <p>MYEGO menyediakan parfum berkualitas premium dengan wangi mewah yang tahan lama, dikonsep secara matang untuk menunjang daya tarik dan persona sejati Anda.</p>
            </div>
            <div class="footer-links">
                <h3>Tautan Cepat</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#catalog">Katalog Produk</a></li>
                    <li><a href="#contact">Konsultasi Aroma</a></li>
                    <li><a href="login.php">Portal Admin</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Hubungi Kami</h3>
                <p>📍 Ruko MYEGO City Mall, Lantai 1, Banjarmasin</p>
                <p>📞 +62 812-3456-7890</p>
                <p>✉️ info@myego-perfume.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> MYEGO Perfume. All Rights Reserved.</p>
            <div>
                <a href="login.php" class="admin-link">Kelola Toko (Admin)</a>
            </div>
        </div>
    </footer>

    <!-- JavaScript Actions -->
    <script>
        // JS configuration for bank accounts
        const bankAccounts = <?php echo json_encode($rekening_toko); ?>;
        let selectedProduct = null;

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Search and Filter Products
        function filterProducts() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const brandFilter = document.getElementById('brandFilter').value.toLowerCase();
            const stockFilter = document.getElementById('stockFilter').value;
            const cards = document.querySelectorAll('.product-card');

            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const brand = card.getAttribute('data-brand');
                const stock = card.getAttribute('data-stock');

                const matchesSearch = name.includes(searchInput) || brand.includes(searchInput);
                const matchesBrand = brandFilter === "" || brand === brandFilter;
                const matchesStock = stockFilter === "" || stock === stockFilter;

                if (matchesSearch && matchesBrand && matchesStock) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // WhatsApp Order Link Generator
        function orderWhatsApp(name, brand, price) {
            const waNumber = "<?php echo $whatsapp_number; ?>";
            const text = `Halo MYEGO, saya tertarik untuk membeli parfum berikut:\n\n- *Nama Parfum:* ${name}\n- *Merek:* ${brand}\n- *Harga:* Rp ${price}\n\nMohon informasi ketersediaan stok dan cara pembayarannya. Terima kasih!`;
            const waUrl = `https://api.whatsapp.com/send?phone=${waNumber}&text=${encodeURIComponent(text)}`;
            window.open(waUrl, '_blank');
        }

        // Detail Modal Controller
        function openDetailModal(product) {
            selectedProduct = product;
            const modal = document.getElementById('detailModal');
            const imgContainer = document.getElementById('modalImgContainer');
            const brand = document.getElementById('modalBrand');
            const title = document.getElementById('modalTitle');
            const price = document.getElementById('modalPrice');
            const stock = document.getElementById('modalStock');
            const actionContainer = document.getElementById('modalActionContainer');

            // Format price
            const formattedPrice = new Intl.NumberFormat('id-ID').format(product.harga);

            // Set content
            brand.textContent = product.merek;
            title.textContent = product.nama_parfum;
            price.textContent = 'Rp ' + formattedPrice;
            stock.textContent = product.stok + ' pcs';
            
            // Image handling
            if (product.gambar && product.gambar !== '') {
                imgContainer.innerHTML = `<img src="uploads/${product.gambar}" alt="${product.nama_parfum}">`;
            } else {
                imgContainer.innerHTML = `<div class="product-placeholder" style="min-height: 400px;">🧴</div>`;
            }

            // Action button injection based on stock
            if (parseInt(product.stok) > 0) {
                actionContainer.innerHTML = `
                    <button class="btn-modal-buy" style="width: 100%; border: none;" onclick="openCheckoutFromDetail()">
                        🛍️ Beli & Bayar Sekarang
                    </button>
                `;
            } else {
                actionContainer.innerHTML = `
                    <button class="btn-modal-buy" style="width: 100%; border: none; background: #555; cursor: not-allowed; box-shadow: none;" disabled>
                        ❌ Stok Habis
                    </button>
                `;
            }

            // WhatsApp link setting
            const buyBtn = document.getElementById('modalBuyBtn');
            const waNumber = "<?php echo $whatsapp_number; ?>";
            const text = `Halo MYEGO, saya ingin bertanya mengenai ketersediaan dan detail parfum *${product.nama_parfum}* (*${product.merek}*).`;
            buyBtn.href = `https://api.whatsapp.com/send?phone=${waNumber}&text=${encodeURIComponent(text)}`;

            // Open modal
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            modal.classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        // Checkout Modal Controller
        function openCheckoutFromDetail() {
            closeDetailModal();
            setTimeout(() => {
                openCheckoutModal(selectedProduct);
            }, 300);
        }

        function openCheckoutModal(product) {
            if (!product) return;
            
            const modal = document.getElementById('checkoutModal');
            document.getElementById('checkoutTitle').textContent = product.nama_parfum;
            document.getElementById('checkoutIdParfum').value = product.id;
            
            const formattedPrice = new Intl.NumberFormat('id-ID').format(product.harga);
            document.getElementById('checkoutPriceLabel').textContent = 'Rp ' + formattedPrice;
            
            // Set max quantity to stock
            const qtyInput = document.getElementById('checkoutQtyInput');
            qtyInput.value = 1;
            qtyInput.max = product.stok;

            updateCheckoutCalculation();
            updatePaymentDetails();

            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeCheckoutModal() {
            const modal = document.getElementById('checkoutModal');
            modal.classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        function updateCheckoutCalculation() {
            if (!selectedProduct) return;
            
            const qtyInput = document.getElementById('checkoutQtyInput');
            let qty = parseInt(qtyInput.value);
            const max = parseInt(qtyInput.max);
            
            // Cap qty bounds
            if (qty < 1 || isNaN(qty)) {
                qty = 1;
                qtyInput.value = 1;
            } else if (qty > max) {
                qty = max;
                qtyInput.value = max;
                alert(`Maaf, Anda hanya dapat memesan maksimal ${max} botol (sesuai stok yang tersedia).`);
            }

            document.getElementById('checkoutQtyLabel').textContent = qty;
            const total = selectedProduct.harga * qty;
            document.getElementById('checkoutTotalLabel').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }

        function updatePaymentDetails() {
            const bank = document.getElementById('checkoutPaymentSelect').value;
            const detailsBox = document.getElementById('paymentDetailsBox');
            
            if (bankAccounts[bank]) {
                const info = bankAccounts[bank];
                detailsBox.innerHTML = `
                    <strong>Instruksi Transfer ${bank}:</strong><br>
                    Silakan transfer nominal total belanja Anda ke rekening berikut:<br>
                    <span style="font-size: 16px; font-weight: 700; color: var(--primary); display: block; margin: 5px 0;">
                        ${info.norek}
                    </span>
                    Atas Nama: <strong>${info.atas_nama}</strong>
                `;
            }
        }

        // Order Status Tracking
        function openTrackModal() {
            const modal = document.getElementById('trackModal');
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeTrackModal() {
            const modal = document.getElementById('trackModal');
            modal.classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        function searchOrders() {
            const phone = document.getElementById('trackPhoneInput').value.trim();
            const resultsBox = document.getElementById('trackingResults');
            
            if (phone === '') {
                alert('Silakan masukkan nomor WhatsApp Anda.');
                return;
            }

            resultsBox.innerHTML = '<p style="text-align: center; color: var(--primary); font-size: 14px; padding: 20px 0;">Searching...</p>';

            fetch('index.php?ajax_track=' + encodeURIComponent(phone))
                .then(response => response.json())
                .then(orders => {
                    if (orders.length === 0) {
                        resultsBox.innerHTML = `
                            <div style="text-align: center; padding: 30px 10px;">
                                <span style="font-size: 40px; display: block; margin-bottom: 10px;">🔍</span>
                                <h3 style="font-size: 16px; color: #fff; margin-bottom: 5px;">Tidak Ada Pesanan Ditemukan</h3>
                                <p style="color: var(--text-muted); font-size: 13px;">Tidak ditemukan riwayat pesanan dengan nomor telepon "${phone}".</p>
                            </div>
                        `;
                    } else {
                        let html = `
                            <table class="tracking-table">
                                <thead>
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Parfum</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        orders.forEach(order => {
                            let statusClass = 'pending';
                            let statusText = 'Verifikasi';
                            
                            if (order.status === 'lunas') {
                                statusClass = 'lunas';
                                statusText = 'Lunas';
                            } else if (order.status === 'dibatalkan') {
                                statusClass = 'dibatalkan';
                                statusText = 'Batal';
                            }

                            html += `
                                <tr>
                                    <td><strong>${order.kode_transaksi}</strong></td>
                                    <td>${order.formatted_date}</td>
                                    <td>${order.nama_parfum}<br><small style="color: var(--primary);">${order.merek}</small></td>
                                    <td>${order.jumlah} pcs</td>
                                    <td>${order.formatted_price}</td>
                                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                </tr>
                            `;
                        });

                        html += '</tbody></table>';
                        resultsBox.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error fetching tracking:', error);
                    resultsBox.innerHTML = '<p style="text-align: center; color: red; font-size: 14px; padding: 20px 0;">Terjadi kesalahan sistem saat melacak pesanan.</p>';
                });
        }
    </script>
</body>
</html>