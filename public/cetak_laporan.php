<?php
// public/cetak_laporan.php

require_once '../app/config.php';

// Keamanan: Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, bisa diarahkan ke login atau tampilkan pesan error
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

if (!isset($_GET['id'])) {
    die("Error: ID Laporan tidak ditemukan.");
}

$laporan_id = (int)$_GET['id'];

// Ambil data laporan utama untuk mengetahui jenisnya (air/udara) dan form_id
$laporan_info = $conn->query("SELECT jenis_laporan, form_id FROM laporan WHERE id = $laporan_id")->fetch_assoc();
if (!$laporan_info) {
    die("Laporan tidak ditemukan.");
}

$jenis_laporan = $laporan_info['jenis_laporan'];
$form_id = $laporan_info['form_id'];
$judul_laporan = ($jenis_laporan == 'air') ? "FORMULIR PENGAMBILAN CONTOH AIR" : "FORMULIR PENGAMBILAN CONTOH UDARA";
$tabel_formulir = ($jenis_laporan == 'air') ? "formulir_air" : "formulir_udara";
$tabel_contoh = ($jenis_laporan == 'air') ? "contoh_air" : "contoh_udara";

// Ambil data dari tabel formulir yang sesuai
$formulir = $conn->query("SELECT * FROM $tabel_formulir WHERE id = $form_id")->fetch_assoc();
// Ambil data dari tabel contoh yang sesuai
$contoh_result = $conn->query("SELECT * FROM $tabel_contoh WHERE formulir_id = $form_id");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan #<?php echo $laporan_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .meta-info { margin-bottom: 5px; }
    </style>
</head>
<body>
    <h2 class="center"><?php echo $judul_laporan; ?></h2>
    
    <div class="meta-info"><b>Perusahaan:</b> <?php echo htmlspecialchars($formulir['perusahaan']); ?></div>
    <div class="meta-info"><b>Alamat:</b> <?php echo htmlspecialchars($formulir['alamat']); ?></div>
    <div class="meta-info"><b>Tanggal Pelaksanaan:</b> <?php echo date('d F Y', strtotime($formulir['tanggal'])); ?></div>

    <h3>Data Contoh Uji</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Contoh</th>
                <th>Jenis Contoh</th>
                <th>Etiket/Merek</th>
                <th>Kode</th>
                <th>Prosedur</th>
                <th>Parameter</th>
                <th>Baku Mutu</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $contoh_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['nama_contoh']); ?></td>
                <td><?php echo htmlspecialchars($row['jenis_contoh']); ?></td>
                <td><?php echo htmlspecialchars($row['merek']); ?></td>
                <td><?php echo htmlspecialchars($row['kode']); ?></td>
                <td><?php echo htmlspecialchars($row['prosedur']); ?></td>
                <td><?php echo htmlspecialchars($row['parameter']); ?></td>
                <td><?php echo htmlspecialchars($row['baku_mutu']); ?></td>
                <td><?php echo htmlspecialchars($row['catatan']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        // Otomatis membuka dialog print saat halaman dimuat
        window.print();
    </script>
</body>
</html>

<?php
$conn->close();
exit;
// Akhir file cetak_laporan.php
// public/dashboard.php
while ($row = $result_riwayat->fetch_assoc()) {
        $laporan_riwayat[] = $row;
    }
    $stmt_riwayat->close();

$page_title = 'Dashboard';
require_once '../templates/header.php';
?>
<div class="container-dashboard">
    <?php if ($role_id == 1): ?>
        <div class="card card-cta">
             </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($pesan_dashboard); ?></h2>
        </div>
        <div class="card-body">
            <?php if (empty($daftar_laporan)): ?>
                <p>Belum ada laporan yang tersedia untuk Anda saat ini.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-laporan">
                        <thead>
                            <tr>
                                <th>ID Laporan</th>
                                <th>Jenis</th>
                                <th>Perusahaan</th>
                                <th>Tanggal Sampling</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar_laporan as $laporan): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($laporan['id']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($laporan['jenis_laporan'])); ?></td>
                                <td><?php echo htmlspecialchars($laporan['perusahaan']); ?></td>
                                <td><?php echo date('d M Y', strtotime($laporan['tanggal'])); ?></td>
                                <td>
                                    <span class="status <?php echo 'status-' . strtolower(str_replace(' ', '-', $laporan['status'])); ?>">
                                        <?php echo htmlspecialchars($laporan['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detail_laporan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary-dashboard">Lihat Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php
require_once '../templates/footer.php';
?>