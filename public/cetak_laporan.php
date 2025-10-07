<?php
// public/cetak_laporan.php

require_once '../app/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

if (!isset($_GET['id'])) {
    die("Error: ID Laporan tidak ditemukan.");
}

$laporan_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Proses simpan nama untuk tanda tangan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_nama_ttd'])) {
    $nama_mt = $_POST['nama_mt_select'] === 'add_new' ? $_POST['nama_mt_lainnya'] : $_POST['nama_mt_select'];
    $nama_ppc = $_POST['nama_ppc'];

    $sql_update_ttd = "UPDATE laporan SET nama_mt_tercetak = ?, nama_ppc_tercetak = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update_ttd);
    $stmt_update->bind_param("ssi", $nama_mt, $nama_ppc, $laporan_id);
    if ($stmt_update->execute()) {
        header("Location: " . BASE_URL . "/cetak_laporan.php?id=" . $laporan_id . "&status=nama_tersimpan");
        exit();
    } else {
        echo "Gagal menyimpan nama.";
    }
}

// Ambil semua data yang diperlukan
$sql = "
    SELECT 
        l.*,
        f.perusahaan, f.alamat, f.tanggal_mulai, f.tanggal_selesai, f.jenis_kegiatan, f.pengambil_sampel, f.sub_kontrak_nama, f.tujuan_pemeriksaan, f.tujuan_pemeriksaan_lainnya,
        u_ppc.nama_lengkap as nama_pembuat_laporan,
        u_penyelia.nama_lengkap as nama_penyelia
    FROM laporan l
    JOIN formulir f ON l.form_id = f.id
    LEFT JOIN users u_ppc ON l.ppc_id = u_ppc.id
    LEFT JOIN users u_penyelia ON l.penyelia_id = u_penyelia.id
    WHERE l.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $laporan_id);
$stmt->execute();
$result = $stmt->get_result();
$laporan = $result->fetch_assoc();

// Ambil TTD terbaru dari setiap user yang terlibat, langsung dari tabel 'users'
$sql_ttd = "
    SELECT 
        (SELECT tanda_tangan FROM users WHERE id = ?) as ttd_ppc,
        (SELECT tanda_tangan FROM users WHERE id = ?) as ttd_penyelia,
        (SELECT tanda_tangan FROM users WHERE id = ?) as ttd_mt
";
$stmt_ttd = $conn->prepare($sql_ttd);
$stmt_ttd->bind_param("iii", $laporan['ppc_id'], $laporan['penyelia_id'], $laporan['mt_id']);
$stmt_ttd->execute();
$ttd_terbaru = $stmt_ttd->get_result()->fetch_assoc();
$stmt_ttd->close();

if (!$laporan) {
    die("Laporan tidak ditemukan.");
}

// VALIDASI AKSES: Hanya Penerima Contoh yang bisa akses laporan "Disetujui, Siap Dicetak"
// Semua role bisa akses laporan yang sudah "Selesai"
if ($role_id == 4) {
    // Penerima Contoh bisa akses laporan "Disetujui, Siap Dicetak" dan "Selesai"
    if (!in_array($laporan['status'], ['Disetujui, Siap Dicetak', 'Selesai'])) {
        die("Anda tidak memiliki hak akses untuk mencetak laporan dengan status ini.");
    }
} else {
    // Role lain hanya bisa akses laporan yang sudah "Selesai"
    if ($laporan['status'] != 'Selesai') {
        die("Laporan ini belum dapat dicetak. Status harus 'Selesai'.");
    }
}

$sql_contoh = "SELECT * FROM contoh WHERE formulir_id = ? ORDER BY id ASC";
$stmt_contoh = $conn->prepare($sql_contoh);
$stmt_contoh->bind_param("i", $laporan['form_id']);
$stmt_contoh->execute();
$result_contoh = $stmt_contoh->get_result();
$daftar_contoh = [];
while ($row = $result_contoh->fetch_assoc()) {
    $daftar_contoh[] = $row;
}

$nama_contoh_utama = !empty($daftar_contoh) ? $daftar_contoh[0]['nama_contoh'] : '';
$jumlah_titik = count($daftar_contoh);

function terbilang($angka) {
    $angka = abs($angka);
    $bilangan = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas');
    if ($angka < 12) {
        return $bilangan[$angka];
    } else if ($angka < 20) {
        return terbilang($angka - 10) . ' Belas';
    } else {
        return $angka;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan #<?php echo $laporan_id; ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            font-size: 11pt; 
            line-height: 1.6; 
        }
        .container { width: 100%; margin: auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h3 { margin: 0; text-decoration: underline; font-size: 14pt; }
        .content-table { width: 100%; border-collapse: collapse; }
        .content-table td { vertical-align: top; padding: 1px 0; }
        .label { width: 35%; font-weight: bold; }
        .separator { width: 5%; text-align: center; }
        .value { width: 60%; }
        .contoh-list { margin: 0; padding-left 0: 18px; }
        .contoh-list li { margin-bottom: 20px; }
        .contoh-detail-table { margin-left: 0; }
        .ttd-section { margin-top: 40px; width: 100%; font-size: 11pt; }
        .ttd-left { float: left; width: 40%; text-align: center; }
        .ttd-right { float: right; width: 45%; text-align: center; }
        .ttd-space { height: 70px; }
        .ttd-penyelia { margin-top: 20px; }
        .no-print { margin-top: 40px; padding: 20px; border: 2px dashed #ccc; background-color: #f9f9f9; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h3>HASIL LAPORAN KEGIATAN SAMPLING</h3>
        </div>

        <section class="info-kegiatan">
            <table class="content-table">
                <tr>
                    <td class="label">JENIS KEGIATAN</td>
                    <td class="separator">:</td>
                    <td class="value"><?php echo htmlspecialchars($laporan['jenis_kegiatan']); ?> <?php echo htmlspecialchars($nama_contoh_utama); ?></td>
                </tr>
                <tr>
                    <td class="label">NAMA PERUSAHAAN</td>
                    <td class="separator">:</td>
                    <td class="value"><?php echo htmlspecialchars($laporan['perusahaan']); ?></td>
                </tr>
                <tr>
                    <td class="label">ALAMAT PERUSAHAAN</td>
                    <td class="separator">:</td>
                    <td class="value"><?php echo htmlspecialchars($laporan['alamat']); ?></td>
                </tr>
                <tr>
                    <td class="label">TANGGAL PELAKSANAAN</td>
                    <td class="separator">:</td>
                    <td class="value">
                        <?php
                        $mulai_cetak = new DateTime($laporan['tanggal_mulai']);
                        $selesai_cetak = new DateTime($laporan['tanggal_selesai']);
                        $formatter_cetak = new IntlDateFormatter('id_ID', IntlDateFormatter::LONG, IntlDateFormatter::NONE);

                        if ($mulai_cetak->format('Y-m-d') == $selesai_cetak->format('Y-m-d')) {
                            echo $formatter_cetak->format($mulai_cetak);
                        } else {
                            echo $formatter_cetak->format($mulai_cetak) . " s/d " . $formatter_cetak->format($selesai_cetak);
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">PENGAMBIL SAMPEL</td>
                    <td class="separator">:</td>
                    <td class="value">
                        <?php 
                        echo htmlspecialchars($laporan['pengambil_sampel']); 
                        if ($laporan['pengambil_sampel'] == 'Sub Kontrak' && !empty($laporan['sub_kontrak_nama'])) {
                            echo " (" . htmlspecialchars($laporan['sub_kontrak_nama']) . ")";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">TUJUAN PEMERIKSAAN</td>
                    <td class="separator">:</td>
                    <td class="value">
                        <?php 
                        $tujuan_cetak = htmlspecialchars($laporan['tujuan_pemeriksaan'] ?? '-');
                        if ($tujuan_cetak === 'Lainnya' && !empty($laporan['tujuan_pemeriksaan_lainnya'])) {
                            echo htmlspecialchars($laporan['tujuan_pemeriksaan_lainnya']);
                        } else {
                            echo $tujuan_cetak;
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </section>

        <section class="info-contoh" style="margin-top: 20px;">
            <h4 style="margin-bottom: 5px;"><?php echo strtoupper(htmlspecialchars($nama_contoh_utama)); ?> : <?php echo $jumlah_titik . " (" . ucfirst(terbilang($jumlah_titik)) . ") Titik Pengujian"; ?></h4>
            
            <ol class="contoh-list">
                <?php foreach ($daftar_contoh as $contoh): ?>
                <li>
                    <table class="content-table contoh-detail-table">
                        <tr><td class="label">Jenis Contoh</td><td class="separator">:</td><td class="value"><?php echo htmlspecialchars($contoh['jenis_contoh']); ?></td></tr>
                        <tr><td class="label">Etiket / Merek</td><td class="separator">:</td><td class="value"><?php echo htmlspecialchars($contoh['merek']); ?></td></tr>
                        <tr><td class="label">Kode Contoh</td><td class="separator">:</td><td class="value"><?php echo htmlspecialchars($contoh['kode']); ?></td></tr>
                        <tr><td class="label">Prosedur Pengambilan Contoh</td><td class="separator">:</td><td class="value"><?php echo htmlspecialchars($contoh['prosedur']); ?></td></tr>
                        <tr><td class="label">Parameter</td><td class="separator">:</td><td class="value"><?php echo htmlspecialchars($contoh['parameter']); ?></td></tr>
                        <tr><td class="label">Baku Mutu</td><td class="separator">:</td><td class="value"><?php echo htmlspecialchars($contoh['baku_mutu']); ?></td></tr>
                    </table>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="ttd-section">
            <div class="ttd-penyelia" style="width: 60%; margin-left: 0; margin-bottom: 10px; text-align: left;">
                <div style="display: flex; align-items: flex-end; justify-content: center; height: 100px;">

                    <div style="line-height: 5; padding-left: 20px;">
                        Diperiksa - Penyelia
                        <strong>( <?php echo htmlspecialchars($laporan['nama_penyelia'] ?? '.........................'); ?> )</strong>
                    </div>
                    <div style="width: 90px; flex-shrink: 0; text-align: center;">
                        <?php if (!empty($laporan['ttd_penyelia'])): ?>
                            <img src="<?php echo BASE_URL . '/uploads/ttd/' . htmlspecialchars($laporan['ttd_penyelia']); ?>" style="max-height: 60px;">
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="ttd-left">
                Menyetujui, <br>
                Manajer Teknis
                <div class="ttd-space">
                    <?php if (!empty($laporan['ttd_mt'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/ttd/' . htmlspecialchars($laporan['ttd_mt']); ?>" style="max-height: 70px;">
                    <?php endif; ?>
                </div>
                <strong>( <?php echo htmlspecialchars($laporan['nama_mt_tercetak'] ?? '.........................'); ?> )</strong>
            </div>
            <div class="ttd-right">
                <?php
                    $tanggal_ttd = !empty($laporan['waktu_persetujuan_mt']) ? strtotime($laporan['waktu_persetujuan_mt']) : strtotime($laporan['tanggal_mulai']);
                    $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'Asia/Jakarta');
                ?>
                Medan, <?php echo $fmt->format($tanggal_ttd); ?><br>
                Petugas Sampling
                <div class="ttd-space">
                    <?php if (!empty($laporan['ttd_ppc'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/ttd/' . htmlspecialchars($laporan['ttd_ppc']); ?>" style="max-height: 70px;">
                    <?php endif; ?>
                </div>
                <strong>( <?php echo htmlspecialchars($laporan['nama_pembuat_laporan'] ?? '.........................'); ?> )</strong>
            </div>
        </section>
        
        <?php if ($role_id == 4): ?>
        <div class="no-print">
            <h4>Pengaturan Tanda Tangan</h4>
            <p>Silakan isi nama Petugas Sampling dan pilih Manajer Teknis sebelum mencetak.</p>
            <form method="POST" action="">
                <div style="margin-bottom: 15px;">
                    <label for="nama_ppc"><strong>Nama Petugas Sampling:</strong></label><br>
                    <input type="text" id="nama_ppc" name="nama_ppc" value="<?php echo htmlspecialchars($laporan['nama_ppc_tercetak'] ?? $laporan['nama_pembuat_laporan']); ?>" required style="width: 250px; padding: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="nama_mt_select"><strong>Nama Manajer Teknis:</strong></label><br>
                    <select id="nama_mt_select" name="nama_mt_select" style="width: 250px; padding: 5px;">
                        <option value="Rossi Evana" <?php echo (isset($laporan['nama_mt_tercetak']) && $laporan['nama_mt_tercetak'] == 'Rossi Evana') ? 'selected' : ''; ?>>Rossi Evana</option>
                        <option value="add_new">Lainnya...</option>
                    </select>
                </div>
                <div id="nama_mt_lainnya_div" style="display:none; margin-bottom: 15px;">
                    <label for="nama_mt_lainnya">Ketik Nama Manajer Teknis:</label><br>
                    <input type="text" id="nama_mt_lainnya" name="nama_mt_lainnya" style="width: 250px; padding: 5px;">
                </div>
                <button type="submit" name="simpan_nama_ttd">Simpan Nama</button>
                <button type="button" onclick="window.print();">Cetak Laporan</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('nama_mt_select').addEventListener('change', function() {
            var lainnyaDiv = document.getElementById('nama_mt_lainnya_div');
            var lainnyaInput = document.getElementById('nama_mt_lainnya');
            if (this.value === 'add_new') {
                lainnyaDiv.style.display = 'block';
                lainnyaInput.required = true;
            } else {
                lainnyaDiv.style.display = 'none';
                lainnyaInput.required = false;
            }
        });
        document.getElementById('nama_mt_select').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
