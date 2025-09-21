<?php
// public/detail_laporan.php

require_once '../app/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Error: ID Laporan tidak ditemukan.");
}

$laporan_id = (int)$_GET['id'];
$role_id = $_SESSION['role_id'];

// Query SQL yang disederhanakan untuk mengambil data dari tabel 'formulir' yang baru
$sql_utama = "
    SELECT 
        l.*, 
        f.perusahaan, f.alamat, f.tanggal, f.jenis_kegiatan, f.pengambil_sampel, f.sub_kontrak_nama,
        u_ppc.nama_lengkap as nama_ppc,
        u_penyelia.nama_lengkap as nama_penyelia,
        u_mt.nama_lengkap as nama_mt,
        u_penerima.nama_lengkap as nama_penerima
    FROM laporan l
    LEFT JOIN formulir f ON l.form_id = f.id
    LEFT JOIN users u_ppc ON l.ppc_id = u_ppc.id
    LEFT JOIN users u_penyelia ON l.penyelia_id = u_penyelia.id
    LEFT JOIN users u_mt ON l.mt_id = u_mt.id
    LEFT JOIN users u_penerima ON l.penerima_id = u_penerima.id
    WHERE l.id = ?
";

$stmt_utama = $conn->prepare($sql_utama);
$stmt_utama->bind_param("i", $laporan_id);
$stmt_utama->execute();
$result_utama = $stmt_utama->get_result();
$data_laporan = $result_utama->fetch_assoc();

if (!$data_laporan) {
    die("Data laporan tidak ditemukan.");
}

// Ambil data detail contoh dari tabel 'contoh' yang baru
$sql_contoh = "SELECT * FROM contoh WHERE formulir_id = ?";
$stmt_contoh = $conn->prepare($sql_contoh);
$stmt_contoh->bind_param("i", $data_laporan['form_id']);
$stmt_contoh->execute();
$result_contoh = $stmt_contoh->get_result();
$data_contoh = [];
while($row = $result_contoh->fetch_assoc()){
    $data_contoh[] = $row;
}

// --- MULAI BLOK BARU ---
// Ambil data riwayat revisi dari tabel baru
$sql_riwayat = "
    SELECT 
        rr.*,
        u.nama_lengkap as nama_perevisi,
        r.nama_role as role_perevisi
    FROM riwayat_revisi rr
    JOIN users u ON rr.revisi_oleh_id = u.id
    JOIN roles r ON u.role_id = r.id
    WHERE rr.laporan_id = ?
    ORDER BY rr.tanggal_revisi_diminta ASC
";
$stmt_riwayat = $conn->prepare($sql_riwayat);
$stmt_riwayat->bind_param("i", $laporan_id);
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();
$riwayat_revisi = [];
while($row = $result_riwayat->fetch_assoc()){
    $riwayat_revisi[] = $row;
}
// --- AKHIR BLOK BARU ---

$page_title = 'Detail Laporan #' . $laporan_id;
require_once '../templates/header.php';
?>

<div class="container-dashboard">
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="back-to-dashboard">« Kembali ke Dashboard</a>

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
        <div class="card-header"><h2>Informasi Kegiatan Sampling</h2></div>
        <div class="card-body">
            <table class="table-meta">
                <tr><td><strong>Jenis Kegiatan</strong></td><td><?php echo htmlspecialchars($data_laporan['jenis_kegiatan'] ?? '-'); ?></td></tr>
                <tr><td><strong>Perusahaan</strong></td><td><?php echo htmlspecialchars($data_laporan['perusahaan'] ?? '-'); ?></td></tr>
                <tr><td><strong>Alamat</strong></td><td><?php echo htmlspecialchars($data_laporan['alamat'] ?? '-'); ?></td></tr>
                <tr><td><strong>Tanggal Pelaksanaan</strong></td><td><?php echo date('d F Y', strtotime($data_laporan['tanggal'])); ?></td></tr>
                <tr><td><strong>Pengambil Sampel</strong></td><td><?php echo htmlspecialchars($data_laporan['pengambil_sampel'] ?? '-'); ?></td></tr>
                <?php if($data_laporan['sub_kontrak_nama']): ?>
                <tr><td><strong>Nama Sub Kontrak</strong></td><td><?php echo htmlspecialchars($data_laporan['sub_kontrak_nama']); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

     <div class="card mt-4">
        <div class="card-header"><h2>Riwayat Persetujuan</h2></div>
        <div class="card-body">
            <table class="table-meta">
                <tr><td><strong>Dibuat oleh (PPC)</strong></td><td><?php echo htmlspecialchars($data_laporan['nama_ppc'] ?? 'N/A'); ?></td></tr>
                <tr><td><strong>Diverifikasi oleh (Penyelia)</strong></td><td><?php echo htmlspecialchars($data_laporan['nama_penyelia'] ?? '-'); ?></td></tr>
                <tr><td><strong>Disetujui oleh (Manajer Teknis)</strong></td><td><?php echo htmlspecialchars($data_laporan['nama_mt'] ?? '-'); ?></td></tr>
                <tr><td><strong>Diselesaikan oleh (Penerima Contoh)</strong></td><td><?php echo htmlspecialchars($data_laporan['nama_penerima'] ?? '-'); ?></td></tr>
            </table>
        </div>
    </div>

    <?php if (!empty($riwayat_revisi)): ?>
    <div class="card mt-4">
        <div class="card-header"><h2>Riwayat Revisi Laporan</h2></div>
        <div class="card-body">
            <?php foreach ($riwayat_revisi as $index => $revisi): ?>
                <div class="revisi-item <?php echo $index > 0 ? 'mt-3' : ''; ?>">
                    <strong>Revisi ke-<?php echo $index + 1; ?></strong> - 
                    Diminta oleh: <strong><?php echo htmlspecialchars($revisi['nama_perevisi']); ?></strong> (<?php echo htmlspecialchars($revisi['role_perevisi']); ?>)
                    <small class="text-muted d-block">
                        Pada: <?php echo date('d F Y, H:i', strtotime($revisi['tanggal_revisi_diminta'])); ?>
                    </small>
                    <div class="card card-body bg-light mt-2">
                        <p class="mb-0"><strong>Catatan:</strong> <?php echo nl2br(htmlspecialchars($revisi['catatan_revisi'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!empty($data_laporan['catatan_revisi'])): ?>
    <div class="card card-revisi mt-4">
         <div class="card-header"><h3>Catatan Revisi</h3></div>
         <div class="card-body"><p><?php echo nl2br(htmlspecialchars($data_laporan['catatan_revisi'])); ?></p></div>
    </div>
    <?php endif; ?>

    <h3 class="mt-4">Data Contoh Uji</h3>
    <?php foreach ($data_contoh as $index => $contoh): ?>
    <div class="card mb-3">
        <div class="card-header"><h4>Contoh Uji #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($contoh['nama_contoh']); ?></h4></div>
        <div class="card-body">
            <table class="table-detail">
                <tr><td width="30%">Jenis Contoh</td><td><?php echo htmlspecialchars($contoh['jenis_contoh']); ?></td></tr>
                <tr><td>Etiket / Merek</td><td><?php echo htmlspecialchars($contoh['merek']); ?></td></tr>
                <tr><td>Kode</td><td><?php echo htmlspecialchars($contoh['kode']); ?></td></tr>
                <tr><td>Prosedur</td><td><?php echo htmlspecialchars($contoh['prosedur']); ?></td></tr>
                <tr><td>Parameter</td><td><?php echo htmlspecialchars($contoh['parameter']); ?></td></tr>
                <tr><td>Baku Mutu</td><td><?php echo htmlspecialchars($contoh['baku_mutu']); ?></td></tr>
                <tr><td>Catatan Tambahan</td><td><?php echo nl2br(htmlspecialchars($contoh['catatan'])); ?></td></tr>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php 
    $status = trim($data_laporan['status']);

    // BLOK AKSI UNTUK PENYELIA
    if ($role_id == 2 && $status == 'Menunggu Verifikasi'): ?>
    <div class="card action-box mt-4">
        <div class="card-header"><h3>Tindakan Verifikasi</h3></div>
        <div class="card-body">
            <form action="../actions/proses_verifikasi.php" method="POST">
                <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                <div class="form-group"><label for="catatan_revisi">Catatan (wajib diisi jika dikembalikan):</label><textarea name="catatan_revisi" id="catatan_revisi" class="form-control" rows="4"></textarea></div>
                <div class="action-buttons"><button type="submit" name="aksi" value="setuju" class="btn btn-success">Setujui & Teruskan</button><button type="submit" name="aksi" value="revisi" class="btn btn-danger">Kembalikan untuk Revisi</button></div>
            </form>
        </div>
    </div>
    
    <?php // BLOK AKSI UNTUK MANAJER TEKNIS
    elseif ($role_id == 3 && $status == 'Menunggu Persetujuan MT'): ?>
    <div class="card action-box mt-4">
        <div class="card-header"><h3>Tindakan Persetujuan Akhir (ACC)</h3></div>
        <div class="card-body">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            <form action="../actions/proses_aksi_mt.php" method="POST">
                <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                <div class="form-group"><label for="catatan_revisi_mt">Catatan (wajib diisi jika dikembalikan):</label><textarea name="catatan_revisi_mt" id="catatan_revisi_mt" class="form-control" rows="4"></textarea></div>
                <div class="action-buttons"><button type="submit" name="aksi" value="setuju" class="btn btn-success">Setujui Laporan (ACC)</button><button type="submit" name="aksi" value="revisi" class="btn btn-warning">Kembalikan ke Penyelia</button></div>
            </form>
        </div>
    </div>

    <?php // BLOK AKSI UNTUK PENERIMA CONTOH
    elseif ($role_id == 4 && $status == 'Disetujui, Siap Dicetak'): ?>
    <div class="card action-box mt-4">
        <div class="card-header"><h3>Tindakan Pencetakan</h3></div>
        <div class="card-body">
            <p>Laporan ini sudah final. Setelah dicetak, ubah statusnya menjadi "Selesai".</p>
            <div class="action-buttons">
                <a href="cetak_laporan.php?id=<?php echo $data_laporan['id']; ?>" target="_blank" class="btn btn-info">Buka Halaman Cetak</a>
                <form action="../actions/proses_aksi_penerima.php" method="POST" onsubmit="return confirm('Anda yakin ingin menyelesaikan laporan ini?');">
                    <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                    <button type="submit" class="btn btn-success">Konfirmasi Selesai</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
// TAMBAHKAN BARIS INI DI AKHIR
require_once '../templates/footer.php';
?>

</body>
</html>