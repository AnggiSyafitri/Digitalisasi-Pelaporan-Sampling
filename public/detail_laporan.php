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

if (isset($_GET['notif_id'])) {
    $notif_id_to_read = (int)$_GET['notif_id'];
    $current_user_id = $_SESSION['user_id'];
    
    $sql_mark_read = "UPDATE notifikasi SET sudah_dibaca = 1 WHERE id = ? AND user_id = ?";
    $stmt_mark_read = $conn->prepare($sql_mark_read);
    $stmt_mark_read->bind_param("ii", $notif_id_to_read, $current_user_id);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();
}

// Query SQL yang disederhanakan untuk mengambil data dari tabel 'formulir' yang baru
$sql_utama = "
    SELECT 
        l.*,
        f.perusahaan, f.alamat, f.tanggal_mulai, f.tanggal_selesai, f.jenis_kegiatan, f.pengambil_sampel, f.sub_kontrak_nama, f.tujuan_pemeriksaan, f.tujuan_pemeriksaan_lainnya, f.file_berita_acara, f.file_sppc,
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
                <tr>
                    <td><strong>Tanggal Pelaksanaan</strong></td>
                    <td>
                        <?php
                        $mulai = new DateTime($data_laporan['tanggal_mulai']);
                        $selesai = new DateTime($data_laporan['tanggal_selesai']);
                        $formatter = new IntlDateFormatter('id_ID', IntlDateFormatter::LONG, IntlDateFormatter::NONE);

                        if ($mulai->format('Y-m-d') == $selesai->format('Y-m-d')) {
                            echo $formatter->format($mulai);
                        } else {
                            echo $formatter->format($mulai) . " s/d " . $formatter->format($selesai);
                        }
                        ?>
                    </td>
                </tr>
                <tr><td><strong>Pengambil Sampel</strong></td><td><?php echo htmlspecialchars($data_laporan['pengambil_sampel'] ?? '-'); ?></td></tr>
                <?php if($data_laporan['sub_kontrak_nama']): ?>
                <tr><td><strong>Nama Sub Kontrak</strong></td><td><?php echo htmlspecialchars($data_laporan['sub_kontrak_nama']); ?></td></tr>
                <?php endif; ?>

                <tr>
                    <td><strong>Tujuan Pemeriksaan</strong></td>
                    <td>
                        <?php 
                        $tujuan = htmlspecialchars($data_laporan['tujuan_pemeriksaan'] ?? '-');
                        if ($tujuan === 'Lainnya' && !empty($data_laporan['tujuan_pemeriksaan_lainnya'])) {
                            echo htmlspecialchars($data_laporan['tujuan_pemeriksaan_lainnya']);
                        } else {
                            echo $tujuan;
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td><strong>Dokumen Berita Acara</strong></td>
                    <td>
                        <?php if (!empty($data_laporan['file_berita_acara'])): ?>
                            <a href="<?php echo BASE_URL . '/uploads/' . htmlspecialchars($data_laporan['file_berita_acara']); ?>" target="_blank" class="btn btn-info btn-sm">Lihat File</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Dokumen SPPC</strong></td>
                    <td>
                        <?php if (!empty($data_laporan['file_sppc'])): ?>
                            <a href="<?php echo BASE_URL . '/uploads/' . htmlspecialchars($data_laporan['file_sppc']); ?>" target="_blank" class="btn btn-info btn-sm">Lihat File</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h2>Riwayat Persetujuan</h2></div>
        <div class="card-body">
            <?php
            // Helper untuk format waktu, sekarang hanya mengembalikan string waktu
            function formatWaktu($waktu) {
                if (empty($waktu)) return null; // Kembalikan null jika waktu kosong

                // Buat objek DateTime dari string waktu database
                $dt = new DateTime($waktu);
                // Atur zona waktu ke WIB
                $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
                // Kembalikan string yang sudah diformat
                return $dt->format('d M Y, H:i') . ' WIB';
            }
            ?>
            <table class="table-meta">
                <tr>
                    <td><strong>Dibuat oleh (PPC)</strong></td>
                    <td>
                        <?php echo htmlspecialchars($data_laporan['nama_ppc'] ?? 'N/A'); ?>
                        <?php if ($waktu = formatWaktu($data_laporan['created_at'])): ?>
                            - <small class="text-muted"><?php echo $waktu; ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Diverifikasi oleh (Penyelia)</strong></td>
                    <td>
                        <?php echo htmlspecialchars($data_laporan['nama_penyelia'] ?? '-'); ?>
                        <?php if ($waktu = formatWaktu($data_laporan['waktu_verifikasi_penyelia'])): ?>
                            - <small class="text-muted"><?php echo $waktu; ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Disetujui oleh (Manajer Teknis)</strong></td>
                    <td>
                        <?php echo htmlspecialchars($data_laporan['nama_mt'] ?? '-'); ?>
                        <?php if ($waktu = formatWaktu($data_laporan['waktu_persetujuan_mt'])): ?>
                            - <small class="text-muted"><?php echo $waktu; ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Diselesaikan oleh (Penerima Contoh)</strong></td>
                    <td>
                        <?php echo htmlspecialchars($data_laporan['nama_penerima'] ?? '-'); ?>
                        <?php if ($waktu = formatWaktu($data_laporan['waktu_penyelesaian_penerima'])): ?>
                            - <small class="text-muted"><?php echo $waktu; ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
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
                <tr><td>Kode Contoh</td><td><?php echo htmlspecialchars($contoh['kode']); ?></td></tr>
                <tr><td>Prosedur</td><td><?php echo htmlspecialchars(is_array($contoh['prosedur']) ? implode(', ', $contoh['prosedur']) : $contoh['prosedur']); ?></td></tr>
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

            <a href="cetak_laporan.php?id=<?php echo $data_laporan['id']; ?>&mode=draft" target="_blank" class="btn btn-info mb-3">
                Lihat Pratinjau Laporan (Draft)
            </a>
            <form action="../actions/proses_aksi_mt.php" method="POST">
                <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                <div class="form-group"><label for="catatan_revisi_mt">Catatan (wajib diisi jika dikembalikan):</label><textarea name="catatan_revisi_mt" id="catatan_revisi_mt" class="form-control" rows="4"></textarea></div>
                <div class="action-buttons"><button type="submit" name="aksi" value="setuju" class="btn btn-success">Setujui Laporan (ACC)</button><button type="submit" name="aksi" value="revisi" class="btn btn-warning">Kembalikan ke PPC</button></div>
            </form>
        </div>
    </div>

    <?php // BLOK AKSI UNTUK PENERIMA CONTOH
    elseif ($role_id == 4 && $status == 'Disetujui, Siap Dicetak'): ?>
    <div class="card action-box mt-4">
        <div class="card-header"><h3>Tindakan Pencetakan</h3></div>
        <div class="card-body">
            <p>Laporan ini sudah final. Jika tindakan "Konfirmasi Selesai" dilakukan, maka laporan tidak dapat di edit kembali.</p>
            <p>~~ Harap dipastikan lagi laporannya sebelum di konfirmasi yaa😉 ~~</p>
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

        <?php // BLOK TOMBOL CETAK UNTUK SEMUA ROLE KETIKA STATUS = "Selesai"
        if ($status == 'Selesai'): ?>
        <div class="card action-box mt-4">
            <div class="card-header"><h3>Laporan Telah Selesai</h3></div>
            <div class="card-body">
                <p>Laporan ini telah diselesaikan dan dapat dicetak/didownload.</p>
                <div class="action-buttons">
                    <a href="cetak_laporan.php?id=<?php echo $data_laporan['id']; ?>" target="_blank" class="btn btn-info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                        </svg>
                        Cetak/Download PDF
                    </a>
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