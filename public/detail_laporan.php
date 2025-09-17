<?php
session_start();
include 'koneksi.php';

// Cek sesi login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil ID Laporan dari URL
if (!isset($_GET['id'])) {
    die("Error: ID Laporan tidak ditemukan.");
}
$laporan_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$role_name = $_SESSION['role_name'];


// Ambil data Laporan & Formulir Utama, serta nama-nama terkait
$sql_utama = "SELECT l.*, 
                     f.perusahaan, f.alamat, f.tanggal, 
                     u_ppc.nama_lengkap as nama_ppc,
                     u_penyelia.nama_lengkap as nama_penyelia
              FROM laporan l
              JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
              JOIN users u_ppc ON l.ppc_id = u_ppc.id
              LEFT JOIN users u_penyelia ON l.penyelia_id = u_penyelia.id
              WHERE l.id = ?";
$stmt_utama = $conn->prepare($sql_utama);
$stmt_utama->bind_param("i", $laporan_id);
$stmt_utama->execute();
$result_utama = $stmt_utama->get_result();
$data_laporan = $result_utama->fetch_assoc();

if (!$data_laporan) {
    die("Data laporan tidak ditemukan.");
}

// Ambil data detail contoh air
$sql_contoh = "SELECT * FROM contoh_air WHERE formulir_id = ?";
$stmt_contoh = $conn->prepare($sql_contoh);
$stmt_contoh->bind_param("i", $data_laporan['form_id']);
$stmt_contoh->execute();
$result_contoh = $stmt_contoh->get_result();
$data_contoh = [];
while($row = $result_contoh->fetch_assoc()){
    $data_contoh[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Laporan #<?php echo $laporan_id; ?></title>
    <link href="css/styles.css" rel="stylesheet" />
</head>
<body>

<header class="header-dashboard">
    <div class="header-title">
        <img src="https://yt3.googleusercontent.com/7uw0pH3SFyMHSYFo0OqwrLmv9LE28VF3TCK2dotW-Ruee1A6VVDYI8fiB0HEcYDb7WQYWcqU5w=s900-c-k-c0x00ffffff-no-rj" alt="Logo BSPJI" class="header-logo">
        <h1>Sistem Pelaporan Sampling</h1>
    </div>
    <div class="user-info">
        <span>Selamat Datang, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong></span>
        <small>Peran: <?php echo htmlspecialchars($role_name); ?></small>
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container-dashboard">
    <a href="dashboard.php" class="back-to-dashboard">« Kembali ke Dashboard</a>

    <div class="card report-header-card">
        <div class="card-body">
            <h1>Detail Laporan #<?php echo htmlspecialchars($data_laporan['id']); ?></h1>
            <p>Status Saat Ini: 
                <span class="status <?php echo 'status-' . strtolower(str_replace(' ', '-', $data_laporan['status'])); ?>">
                    <?php echo htmlspecialchars($data_laporan['status']); ?>
                </span>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Informasi Umum</h2></div>
        <div class="card-body">
            <table class="table-meta">
                <tr>
                    <td><strong>Perusahaan</strong></td>
                    <td><?php echo htmlspecialchars($data_laporan['perusahaan']); ?></td>
                </tr>
                <tr>
                    <td><strong>Alamat</strong></td>
                    <td><?php echo htmlspecialchars($data_laporan['alamat']); ?></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Sampling</strong></td>
                    <td><?php echo date('d F Y', strtotime($data_laporan['tanggal'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Dibuat oleh (PPC)</strong></td>
                    <td><?php echo htmlspecialchars($data_laporan['nama_ppc']); ?></td>
                </tr>
                 <?php if(!empty($data_laporan['nama_penyelia'])): ?>
                <tr>
                    <td><strong>Diverifikasi oleh (Penyelia)</strong></td>
                    <td><?php echo htmlspecialchars($data_laporan['nama_penyelia']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <?php if(!empty($data_laporan['catatan_revisi'])): ?>
    <div class="card card-revisi">
         <div class="card-header"><h3>Catatan Revisi</h3></div>
         <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($data_laporan['catatan_revisi'])); ?></p>
         </div>
    </div>
    <?php endif; ?>


    <h3>Data Contoh Uji</h3>
    <?php foreach ($data_contoh as $index => $contoh): ?>
    <div class="card">
        <div class="card-header"><h4>Contoh Uji #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($contoh['nama_contoh']); ?></h4></div>
        <div class="card-body">
            <table class="table-detail">
                <tr><td width="30%">Jenis Contoh</td><td><?php echo htmlspecialchars($contoh['jenis_contoh']); ?></td></tr>
                <tr><td>Etiket / Merek</td><td><?php echo htmlspecialchars($contoh['merek']); ?></td></tr>
                <tr><td>Kode</td><td><?php echo htmlspecialchars($contoh['kode']); ?></td></tr>
                <tr><td>Prosedur</td><td><?php echo htmlspecialchars($contoh['prosedur']); ?></td></tr>
                <tr><td>Parameter</td><td><?php echo htmlspecialchars($contoh['parameter']); ?></td></tr>
                <tr><td>Baku Mutu</td><td><?php echo htmlspecialchars($contoh['baku_mutu']); ?></td></tr>
                <tr><td>Catatan</td><td><?php echo nl2br(htmlspecialchars($contoh['catatan'])); ?></td></tr>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php 
    // Gunakan trim() untuk membersihkan spasi tak terlihat dari status
    $status_laporan_bersih = trim($data_laporan['status']);

    // BLOK AKSI UNTUK PENYELIA
    if ($role_id == 2 && $status_laporan_bersih == 'Menunggu Verifikasi'): 
    ?>
    <div class="card action-box">
        <div class="card-header"><h3>Tindakan Verifikasi</h3></div>
        <div class="card-body">
            <p>Silakan periksa kelengkapan dan kebenaran data laporan ini. Berikan persetujuan untuk meneruskan laporan ke Manajer Teknis atau kembalikan ke PPC jika memerlukan revisi.</p>
            <form action="proses_verifikasi.php" method="POST">
                <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                <div class="form-group">
                    <label for="catatan_revisi">Catatan (wajib diisi jika dikembalikan untuk revisi):</label>
                    <textarea name="catatan_revisi" id="catatan_revisi" class="form-control" rows="4"></textarea>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="aksi" value="setuju" class="btn btn-success">
                        Setujui & Teruskan
                    </button>
                    <button type="submit" name="aksi" value="revisi" class="btn btn-danger">
                        Kembalikan untuk Revisi
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php 
    // BLOK AKSI UNTUK MANAJER TEKNIS
    elseif ($role_id == 3 && $status_laporan_bersih == 'Menunggu Persetujuan MT'): 
    ?>
    <div class="card action-box">
        <div class="card-header"><h3>Tindakan Persetujuan Akhir (ACC)</h3></div>
        <div class="card-body">
            <p>Laporan ini telah diverifikasi oleh Penyelia. Silakan berikan persetujuan akhir (ACC) atau kembalikan ke Penyelia jika ada yang perlu ditinjau ulang.</p>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="proses_aksi_mt.php" method="POST">
                <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                <div class="form-group">
                    <label for="catatan_revisi_mt">Catatan (wajib diisi jika dikembalikan ke Penyelia):</label>
                    <textarea name="catatan_revisi_mt" id="catatan_revisi_mt" class="form-control" rows="4"></textarea>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="aksi" value="setuju" class="btn btn-success">
                        Setujui Laporan (ACC)
                    </button>
                    <button type="submit" name="aksi" value="revisi" class="btn btn-warning">
                        Kembalikan ke Penyelia
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php 
    // BLOK AKSI UNTUK PENERIMA CONTOH
    elseif ($role_id == 4 && $status_laporan_bersih == 'Disetujui, Siap Dicetak'): 
    ?>
    <div class="card action-box">
        <div class="card-header"><h3>Tindakan Pencetakan</h3></div>
        <div class="card-body">
            <p>Laporan ini sudah final dan disetujui. Silakan cetak laporan resmi. Setelah mencetak, status laporan akan otomatis berubah menjadi "Selesai".</p>
            
            <div class="action-buttons">
                <a href="cetak_air.php?id=<?php echo $data_laporan['form_id']; ?>" target="_blank" class="btn btn-primary-dashboard" style="background-color: #17a2b8;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill" viewBox="0 0 16 16">
                      <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                      <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    </svg>
                    Buka Halaman Cetak
                </a>

                <form action="proses_aksi_penerima.php" method="POST" onsubmit="return confirm('Apakah Anda yakin sudah mencetak dokumen ini? Status akan diubah menjadi Selesai.');">
                    <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                        Konfirmasi Selesai
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>