<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Menangani Aksi Konfirmasi Pembayaran
if (isset($_GET['konfirmasi'])) {
    $id_trx = intval($_GET['konfirmasi']);
    
    // Ambil info transaksi
    $query_trx = mysqli_query($koneksi, "SELECT t.*, p.nama_parfum FROM transaksi t JOIN parfum p ON t.id_parfum = p.id WHERE t.id = '$id_trx'");
    $trx = mysqli_fetch_assoc($query_trx);
    
    if ($trx && $trx['status'] === 'pending') {
        // Update status transaksi menjadi lunas
        $update_status = mysqli_query($koneksi, "UPDATE transaksi SET status = 'lunas' WHERE id = '$id_trx'");
        
        if ($update_status) {
            // Catat otomatis ke tabel pemasukan
            $tanggal_sekarang = date('Y-m-d');
            $keterangan = "Penjualan " . $trx['nama_parfum'] . " - " . $trx['nama_pelanggan'] . " (Kode: " . $trx['kode_transaksi'] . ")";
            $jumlah = $trx['total_harga'];
            
            $query_pemasukan = "INSERT INTO pemasukan (tanggal, keterangan, jumlah) VALUES ('$tanggal_sekarang', '$keterangan', '$jumlah')";
            mysqli_query($koneksi, $query_pemasukan);
            
            echo "<script>alert('Pembayaran berhasil dikonfirmasi! Penjualan telah tercatat dalam laporan pemasukan.'); window.location='transaksi.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui status transaksi!'); window.location='transaksi.php';</script>";
        }
    } else {
        echo "<script>alert('Transaksi tidak valid atau sudah dikonfirmasi/dibatalkan.'); window.location='transaksi.php';</script>";
    }
}

// Menangani Aksi Pembatalan Pesanan
if (isset($_GET['batalkan'])) {
    $id_trx = intval($_GET['batalkan']);
    
    // Ambil info transaksi
    $query_trx = mysqli_query($koneksi, "SELECT * FROM transaksi WHERE id = '$id_trx'");
    $trx = mysqli_fetch_assoc($query_trx);
    
    if ($trx && $trx['status'] === 'pending') {
        // Batalkan transaksi
        $update_status = mysqli_query($koneksi, "UPDATE transaksi SET status = 'dibatalkan' WHERE id = '$id_trx'");
        
        if ($update_status) {
            // Restore stok parfum
            $id_parfum = $trx['id_parfum'];
            $qty = $trx['jumlah'];
            mysqli_query($koneksi, "UPDATE parfum SET stok = stok + '$qty' WHERE id = '$id_parfum'");
            
            echo "<script>alert('Transaksi telah dibatalkan. Stok parfum berhasil dikembalikan.'); window.location='transaksi.php';</script>";
        } else {
            echo "<script>alert('Gagal membatalkan transaksi!'); window.location='transaksi.php';</script>";
        }
    } else {
        echo "<script>alert('Transaksi tidak valid atau sudah diproses.'); window.location='transaksi.php';</script>";
    }
}

// Mengambil semua data transaksi
$data_transaksi = mysqli_query($koneksi, "SELECT t.*, p.nama_parfum, p.merek FROM transaksi t JOIN parfum p ON t.id_parfum = p.id ORDER BY t.tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Transaksi - MYEGO</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body{ background:#f5f7fb; }

.sidebar{ position:fixed; width:250px; height:100vh; background:linear-gradient(180deg,#111,#222); color:white; padding:20px; }
.logo{ text-align:center; font-size:28px; font-weight:bold; color:gold; margin-bottom:40px; min-height: 60px; display: flex; align-items: center; justify-content: center; }
.logo img { max-width: 100%; max-height: 60px; object-fit: contain; }

.menu a{ display:block; color:white; text-decoration:none; padding:12px; margin-bottom:10px; border-radius:8px; transition:.3s; }
.menu a:hover{ background:gold; color:black; }
.menu a.active{ background:gold; color:black; }

.main{ margin-left:250px; padding:30px; }
.header{ background:white; padding:20px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,.1); display:flex; justify-content:space-between; align-items:center; }
.logout{ background:red; color:white; padding:10px 20px; text-decoration:none; border-radius:8px; }

.table-box{ background:white; margin-top:30px; padding:25px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,.1); }
.table-box h3 { margin-bottom: 20px; color: #333; font-size: 18px; border-bottom: 2px solid #f5f7fb; padding-bottom: 10px; }

table{ width:100%; border-collapse:collapse; margin-top:10px; }
table th{ background:gold; padding:12px; text-align: left; font-size: 14px; font-weight: 600; color: #111; }
table td{ padding:12px; border-bottom:1px solid #ddd; font-size: 13.5px; color: #333; vertical-align: middle; }
table tr:hover { background: #f8f9fa; }

/* Status Badges */
.status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; text-transform: uppercase; }
.status-badge.pending { background: #ffeeba; color: #856404; border: 1px solid #ffe8a1; }
.status-badge.lunas { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.status-badge.dibatalkan { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* Action Buttons */
.btn-action { text-decoration: none; padding: 6px 12px; border-radius: 5px; font-size: 12.5px; font-weight: 600; display: inline-block; margin-right: 5px; transition: .2s; }
.btn-confirm { background: #28a745; color: white; }
.btn-confirm:hover { background: #218838; }
.btn-cancel { background: #dc3545; color: white; }
.btn-cancel:hover { background: #c82333; }

/* Bukti Transfer Image */
.bukti-link { color: #007bff; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
.bukti-link:hover { text-decoration: underline; color: #0056b3; }
.bukti-thumbnail { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; display: block; margin-top: 5px; }

/* Modal Image View */
.image-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1500; display: none; align-items: center; justify-content: center; }
.image-modal.active { display: flex; }
.image-modal-content { max-width: 90%; max-height: 85%; border-radius: 10px; border: 3px solid white; box-shadow: 0 5px 25px rgba(0,0,0,0.5); }
.image-modal-close { position: absolute; top: 20px; right: 30px; font-size: 40px; color: white; cursor: pointer; font-weight: bold; }
.image-modal-close:hover { color: gold; }

</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <?php
        $logo_files = glob('uploads/logo_app.*');
        $logo_exists = !empty($logo_files);
        $logo_path = $logo_exists ? $logo_files[0] : '';
        if ($logo_exists):
        ?>
            <img src="<?php echo $logo_path; ?>?v=<?php echo filemtime($logo_path); ?>" alt="Logo">
        <?php else: ?>
            MYEGO
        <?php endif; ?>
    </div>
    <div class="menu">
        <a href="admin.php">🏠 Dashboard</a>
        <a href="katalog.php">📦 Katalog Parfum</a>
        <a href="laporan.php">📊 Laporan Pemasukan</a>
        <a href="logo.php">🖼️ Pengaturan Logo</a>
        <a href="transaksi.php" class="active">🛒 Transaksi Masuk</a>
    </div>
</div>

<div class="main">
    <div class="header">
        <div>
            <h2>Kelola Transaksi Masuk</h2>
            <p>Konfirmasi pembayaran dan verifikasi bukti transfer pesanan pelanggan</p>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="table-box">
        <h3>Daftar Transaksi Pembelian</h3>
        <div style="overflow-x: auto;">
            <table>
                <tr>
                    <th width="3%">No</th>
                    <th width="10%">Invoice</th>
                    <th width="12%">Tanggal</th>
                    <th width="18%">Pelanggan</th>
                    <th width="20%">Pesanan</th>
                    <th width="10%">Total Bayar</th>
                    <th width="12%">Bukti Transfer</th>
                    <th width="7%">Status</th>
                    <th width="10%">Aksi</th>
                </tr>
                <?php
                $no = 1;
                if(mysqli_num_rows($data_transaksi) > 0){
                    while($d = mysqli_fetch_array($data_transaksi)){
                        $status_class = 'pending';
                        $status_text = 'Verifikasi';
                        
                        if ($d['status'] === 'lunas') {
                            $status_class = 'lunas';
                            $status_text = 'Lunas';
                        } else if ($d['status'] === 'dibatalkan') {
                            $status_class = 'dibatalkan';
                            $status_text = 'Batal';
                        }
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><strong><?php echo $d['kode_transaksi']; ?></strong></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($d['tanggal'])); ?></td>
                    <td>
                        <b><?php echo htmlspecialchars($d['nama_pelanggan']); ?></b><br>
                        <small style="color: #666;">📞 <?php echo htmlspecialchars($d['telepon']); ?></small><br>
                        <small style="color: #888;">📍 <?php echo htmlspecialchars($d['alamat']); ?></small>
                    </td>
                    <td>
                        <b><?php echo htmlspecialchars($d['nama_parfum']); ?></b><br>
                        <small style="color: #555;"><?php echo htmlspecialchars($d['merek']); ?></small><br>
                        <small style="color: #28a745;"><?php echo $d['jumlah']; ?> botol</small>
                    </td>
                    <td><b>Rp <?php echo number_format($d['total_harga'], 0, ',', '.'); ?></b><br><small style="color:#666; font-size:11px;"><?php echo $d['metode_pembayaran']; ?></small></td>
                    <td>
                        <?php if(!empty($d['bukti_pembayaran']) && file_exists('uploads/bukti/' . $d['bukti_pembayaran'])): ?>
                            <span class="bukti-link" onclick="viewImage('uploads/bukti/<?php echo $d['bukti_pembayaran']; ?>')">
                                🔍 Lihat Bukti
                            </span>
                            <img src="uploads/bukti/<?php echo $d['bukti_pembayaran']; ?>" class="bukti-thumbnail" onclick="viewImage('uploads/bukti/<?php echo $d['bukti_pembayaran']; ?>')">
                        <?php else: ?>
                            <span style="color:#dc3545; font-style:italic;">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                    <td>
                        <?php if($d['status'] === 'pending'): ?>
                            <a href="?konfirmasi=<?php echo $d['id']; ?>" class="btn-action btn-confirm" onclick="return confirm('Apakah Anda yakin pembayaran ini valid dan ingin mengonfirmasi pesanan ini?')">Konfirmasi</a>
                            <a href="?batalkan=<?php echo $d['id']; ?>" class="btn-action btn-cancel" style="margin-top: 5px;" onclick="return confirm('Apakah Anda yakin ingin membatalkan transaksi ini dan mengembalikan stok produk?')">Batalkan</a>
                        <?php else: ?>
                            <span style="color:#999; font-size:12px;">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='9' style='text-align:center; padding: 30px;'>Belum ada data transaksi pembelian</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>

<!-- Modal Bukti Transfer -->
<div class="image-modal" id="imageModal">
    <span class="image-modal-close" onclick="closeImage()">&times;</span>
    <img class="image-modal-content" id="modalImg">
</div>

<script>
function viewImage(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImg');
    modal.classList.add('active');
    modalImg.src = src;
}

function closeImage() {
    const modal = document.getElementById('imageModal');
    modal.classList.remove('active');
}

// Close modal if clicked outside image
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImage();
    }
});
</script>

</body>
</html>
