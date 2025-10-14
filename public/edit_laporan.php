<?php
// public/edit_laporan.php

require_once '../app/config.php';

// Keamanan: Pastikan hanya PPC yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Pastikan ada ID laporan yang dikirim
if (!isset($_GET['laporan_id'])) {
    die("Error: ID Laporan tidak ditemukan.");
}

$laporan_id = (int)$_GET['laporan_id'];
$user_id = $_SESSION['user_id'];

// Ambil data laporan dan formulir, izinkan status 'Draft' dan 'Revisi PPC'
$sql_laporan = "
    SELECT l.*, f.*
    FROM laporan l
    JOIN formulir f ON l.form_id = f.id
    WHERE l.id = ? AND l.ppc_id = ? AND l.status IN ('Draft', 'Revisi PPC')";

$stmt_laporan = $conn->prepare($sql_laporan);
$stmt_laporan->bind_param("ii", $laporan_id, $user_id);
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();
$data_laporan = $result_laporan->fetch_assoc();

if (!$data_laporan) {
    die("Laporan tidak ditemukan, tidak dapat diakses, atau statusnya tidak valid untuk diedit.");
}

// Ambil data contoh-contoh yang terkait
$sql_contoh = "SELECT * FROM contoh WHERE formulir_id = ?";
$stmt_contoh = $conn->prepare($sql_contoh);
$stmt_contoh->bind_param("i", $data_laporan['form_id']);
$stmt_contoh->execute();
$result_contoh = $stmt_contoh->get_result();
$data_contoh = [];
while ($row = $result_contoh->fetch_assoc()) {
    $data_contoh[] = $row;
}

$page_title = 'Edit Laporan #' . $laporan_id;
require_once '../templates/header.php';
?>

<style>
    .loading-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.7); display: none;
        justify-content: center; align-items: center; z-index: 9999;
        color: white; font-size: 1.2rem; font-family: sans-serif;
    }
    .file-error-message { font-size: 0.8em; }
    .parameter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 0.5rem;
    }
    .option-box {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 10px;
        background-color: #f8f9fa;
        max-height: 200px;
        overflow-y: auto;
    }
</style>

<div class="loading-overlay" id="loadingOverlay">
    <p>Memperbarui data, mohon tunggu...</p>
</div>

<div class="container-dashboard" style="padding: 2rem;">
    
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="back-to-dashboard">« Kembali ke Dashboard</a>

    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h2>Edit Laporan Sampling #<?php echo htmlspecialchars($laporan_id); ?></h2>
            <p class="mb-0">
                <?php echo $data_laporan['status'] == 'Draft' ? 'Anda sedang mengedit draft laporan.' : 'Anda sedang mengedit laporan yang dikembalikan untuk revisi.'; ?>
            </p>
        </div>

        <div class="card-body">
            <div id="validation-error-container" class="alert alert-danger" style="display:none;"></div>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['flash_error']; 
                        unset($_SESSION['flash_error']);
                    ?>
                </div>
            <?php endif; ?>

            <form id="editForm" action="../actions/update_laporan.php" method="post" enctype="multipart/form-data">
                
                <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
                <input type="hidden" name="form_id" value="<?php echo $data_laporan['form_id']; ?>">

                <div class="form-section" style="border:none; padding-bottom:1rem;">
                    <h3>Informasi Kegiatan Sampling</h3>
                    
                    <div class="form-group">
                        <label for="jenis_kegiatan">Jenis Kegiatan <span class="text-danger">*</span></label>
                        <select id="jenis_kegiatan" name="jenis_kegiatan" class="form-control" required>
                            <option value="">-- Pilih Jenis Kegiatan --</option>
                            <option value="Sampling dan Pengujian" <?php echo ($data_laporan['jenis_kegiatan'] == 'Sampling dan Pengujian') ? 'selected' : ''; ?>>Sampling dan Pengujian</option>
                            <option value="Sampling" <?php echo ($data_laporan['jenis_kegiatan'] == 'Sampling') ? 'selected' : ''; ?>>Sampling</option>
                            <option value="Pengujian" <?php echo ($data_laporan['jenis_kegiatan'] == 'Pengujian') ? 'selected' : ''; ?>>Pengujian</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="perusahaan">Nama Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" id="perusahaan" name="perusahaan" class="form-control" value="<?php echo htmlspecialchars($data_laporan['perusahaan']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat Perusahaan <span class="text-danger">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" class="form-control" required><?php echo htmlspecialchars($data_laporan['alamat']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tanggal_mulai">Tanggal Mulai Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?php echo htmlspecialchars($data_laporan['tanggal_mulai']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="tanggal_selesai">Tanggal Selesai Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control" value="<?php echo htmlspecialchars($data_laporan['tanggal_selesai']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pengambil_sampel">Pengambil Sampel <span class="text-danger">*</span></label>
                        <select id="pengambil_sampel" name="pengambil_sampel" class="form-control" required>
                            <option value="BSPJI Medan" <?php echo ($data_laporan['pengambil_sampel'] == 'BSPJI Medan') ? 'selected' : ''; ?>>BSPJI Medan</option>
                            <option value="Sub Kontrak" <?php echo ($data_laporan['pengambil_sampel'] == 'Sub Kontrak') ? 'selected' : ''; ?>>Sub Kontrak</option>
                        </select>
                    </div>

                    <div class="form-group" id="sub_kontrak_wrapper" style="display:<?php echo ($data_laporan['pengambil_sampel'] == 'Sub Kontrak') ? 'block' : 'none'; ?>;">
                        <label for="sub_kontrak_nama">Nama Perusahaan Sub Kontrak <span class="text-danger">*</span></label>
                        <input type="text" id="sub_kontrak_nama" name="sub_kontrak_nama" class="form-control" value="<?php echo htmlspecialchars($data_laporan['sub_kontrak_nama']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="tujuan_pemeriksaan">Tujuan Pemeriksaan <span class="text-danger">*</span></label>
                    <select id="tujuan_pemeriksaan" name="tujuan_pemeriksaan" class="form-control" required>
                        <option value="">-- Pilih Tujuan --</option>
                        <option value="Pemantauan / Pelaporan" <?php echo ($data_laporan['tujuan_pemeriksaan'] == 'Pemantauan / Pelaporan') ? 'selected' : ''; ?>>Pemantauan / Pelaporan</option>
                        <option value="Pembuktian / Penegakan Hukum" <?php echo ($data_laporan['tujuan_pemeriksaan'] == 'Pembuktian / Penegakan Hukum') ? 'selected' : ''; ?>>Pembuktian / Penegakan Hukum</option>
                        <option value="Kepentingan Internal / Perusahaan" <?php echo ($data_laporan['tujuan_pemeriksaan'] == 'Kepentingan Internal / Perusahaan') ? 'selected' : ''; ?>>Kepentingan Internal / Perusahaan</option>
                        <option value="Lainnya" <?php echo ($data_laporan['tujuan_pemeriksaan'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>

                <div class="form-group" id="tujuan_lainnya_wrapper" style="display:<?php echo ($data_laporan['tujuan_pemeriksaan'] == 'Lainnya') ? 'block' : 'none'; ?>;">
                    <label for="tujuan_pemeriksaan_lainnya">Tujuan Lainnya <span class="text-danger">*</span></label>
                    <input type="text" id="tujuan_pemeriksaan_lainnya" name="tujuan_pemeriksaan_lainnya" class="form-control" value="<?php echo htmlspecialchars($data_laporan['tujuan_pemeriksaan_lainnya']); ?>">
                </div>

                <hr class="mb-4">

                <div class="form-section" style="border:none; padding-bottom:0;">
                    <h3>Data Contoh Uji</h3>
                    <p>Edit data contoh uji di bawah ini. Anda juga dapat menambah atau menghapus contoh uji jika diperlukan.</p>
                    <button type="button" class="btn btn-primary mb-3" onclick="tambahContoh()">
                        Tambah Contoh Uji Baru
                    </button>
                    <div id="contohContainer">
                        </div>
                </div>

                <hr class="mb-4">
                <div class="form-section" style="border:none; padding-bottom:0;">
                    <h3>Dokumen Pendukung</h3>
                    <p>Ganti atau unggah dokumen Berita Acara dan SPPC untuk laporan ini (jika ada).</p>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="file_berita_acara">Upload Berita Acara (Opsional)</label>
                            <input type="hidden" name="file_berita_acara_lama" value="<?php echo htmlspecialchars($data_laporan['file_berita_acara']); ?>">
                            <?php if (!empty($data_laporan['file_berita_acara'])): ?>
                                <p class="form-text text-muted mb-1 small">File saat ini: <a href="<?php echo BASE_URL; ?>/uploads/<?php echo htmlspecialchars($data_laporan['file_berita_acara']); ?>" target="_blank">Lihat File</a></p>
                            <?php endif; ?>
                            <input type="file" id="file_berita_acara" name="file_berita_acara" class="form-control-file" onchange="validateFile(this)">
                            <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah file. | PDF, JPG, PNG (Maks 5MB)</small>
                            <div class="file-error-message text-danger small mt-1"></div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="file_sppc">Upload SPPC (Opsional)</label>
                            <input type="hidden" name="file_sppc_lama" value="<?php echo htmlspecialchars($data_laporan['file_sppc']); ?>">
                            <?php if (!empty($data_laporan['file_sppc'])): ?>
                                <p class="form-text text-muted mb-1 small">File saat ini: <a href="<?php echo BASE_URL; ?>/uploads/<?php echo htmlspecialchars($data_laporan['file_sppc']); ?>" target="_blank">Lihat File</a></p>
                            <?php endif; ?>
                            <input type="file" id="file_sppc" name="file_sppc" class="form-control-file" onchange="validateFile(this)">
                            <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah file. | PDF, JPG, PNG (Maks 5MB)</small>
                            <div class="file-error-message text-danger small mt-1"></div>
                        </div>
                    </div>
                </div>

                <div class="button-group mt-4">
                     <button type="submit" name="aksi" value="draft" class="btn btn-secondary">
                        Simpan Perubahan Draft
                    </button>
                    <button type="submit" name="aksi" value="ajukan" class="btn btn-success">
                        Ajukan ke Penyelia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    const dataContohLama = <?php echo json_encode($data_contoh); ?>;
    
    document.getElementById('pengambil_sampel').addEventListener('change', function() {
        const subKontrakWrapper = document.getElementById('sub_kontrak_wrapper');
        const subKontrakInput = document.getElementById('sub_kontrak_nama');
        if (this.value === 'Sub Kontrak') {
            subKontrakWrapper.style.display = 'block';
            subKontrakInput.required = true;
        } else {
            subKontrakWrapper.style.display = 'none';
            subKontrakInput.required = false;
        }
    });

    document.getElementById('tujuan_pemeriksaan').addEventListener('change', function() {
        const lainnyaWrapper = document.getElementById('tujuan_lainnya_wrapper');
        const lainnyaInput = document.getElementById('tujuan_pemeriksaan_lainnya');
        if (this.value === 'Lainnya') {
            lainnyaWrapper.style.display = 'block';
            lainnyaInput.required = true;
        } else {
            lainnyaWrapper.style.display = 'none';
            lainnyaInput.required = false;
        }
    });

    const dataSampling = {
        "Air Limbah": {
            tipeLaporan: "air",
            parameter: ["Padatan tersuspensi total (TSS)", "derajat keasaman (pH)", "amonia (NH₃-N)", "kebutuhan oksigen kimiawi (COD)", "minyak dan lemak", "sulfat (SO₄²⁻)", "kadmium total (Cd)", "nikel total (Ni)", "krom total (Cr-T)", "seng total (Zn)", "mangan terlarut (Mn)", "tembaga total (Cu)", "timbal total (Pb)", "besi terlarut (Fe)", "barium total (Ba)", "kobalt total (Co)", "padatan terlarut total (TDS)", "suhu", "ortofosfat", "total fosfor", "padatan terlarut total (TDS) secara gravimetri", "daya hantar listrik (DHL)", "krom heksavalen (Cr⁶⁺)", "nitrit (NO₂-N)", "total coliform", "air raksa total (Hg)", "timah total (Sn)", "arsen total (As)", "selenium total (Se)", "sianida (CN⁻)", "sulfida", "fluorida (F⁻)", "klor bebas", "nitrat (NO₃-N)", "surfaktan anionik (MBAS)", "kebutuhan oksigen biokimiawi (BOD)", "total nitrogen (sebagai N)", "fenol"],
            prosedur: ["SNI 06-6989.3:2019 (pH)", "SNI 06-6989.9:2004 (COD)", "SNI 06-6989.10:2019 (TSS)", "SNI 06-6989.11:2019 (TDS)", "SNI 06-6989.15:2019 (NH₃-N)", "SNI 06-6989.23:2005 (minyak & lemak)", "SNI 06-6989.27:2019 (Cd, AAS)", "SNI 06-6989.30:2005 (BOD)", "SNI 6989.1:2019 (pengambilan contoh uji)", "SNI 6989.20:2019 (NO₂-N)", "SNI 6989.68:2009 (S²⁻)", "SNI 6989.71:2009 (total P)", "SNI 6989.84:2019 (logam berat, AAS)", "SNI 8990:2021 (MBAS)", "SNI 9063:2022 (total coliform & E. coli, MPN)", "SNI 6989.31:2021 (NO₃-N)", "M-LP-720-TDS (padatan terlarut total)", "SM APHA 23rd Ed. 9221B & C:2017 (coliform & E. coli, tabung ganda)"]
        },
        "Air Tanah": {
            tipeLaporan: "air",
            parameter: ["Padatan tersuspensi total (TSS)", "derajat keasaman (pH)", "amonia (NH₃-N)", "sulfat (SO₄²⁻)", "kadmium terlarut (Cd)", "nikel terlarut (Ni)", "krom terlarut (Cr)", "seng terlarut (Zn)", "mangan terlarut (Mn)", "tembaga terlarut (Cu)", "timbal terlarut (Pb)", "besi terlarut (Fe)", "barium terlarut (Ba)", "aluminium terlarut (Al)", "kalium terlarut (K)", "kobalt terlarut (Co)", "padatan terlarut total (TDS) secara gravimetri", "suhu", "perak terlarut (Ag)", "ortofosfat", "total fosfor", "daya hantar listrik (DHL)", "krom heksavalen (Cr⁶⁺)", "klorida (Cl⁻)", "nilai permanganat", "fluorida (F⁻)", "nitrit terlarut (NO₂-N)", "total coliform", "Escherichia coli", "kekeruhan", "bau", "air raksa terlarut (Hg)", "arsen terlarut (As)", "selenium terlarut (Se)", "sianida terlarut (CN⁻)", "sulfida terlarut", "klor bebas", "nitrat terlarut (NO₃-N)", "surfaktan anionik (MBAS)", "fenol", "warna", "boron terlarut (B)", "toluena", "benzena", "dieldrin", "karbon organik total/hidrokarbon poliaromatis (PAH)", "parakuat diklorida", "aluminium fosfida", "magnesium fosfida", "sulfuril fluorida", "metil bromida", "seng fosfida", "dikuat dibromida", "etil format", "fosfin", "asam sulfat", "formaldehida", "metanol", "N-metilpirolidon", "piridin basa", "lindan", "heptaklor", "eldrin", "endosulfan", "residu karbamat", "organoklorin", "α-BHC", "4,4′-DDT", "khlordan", "toksafen", "mirex", "polychlorinated biphenyl (PCB)", "heksaklorobenzena (HCB)", "organofosfat", "piretroid", "profenofos", "senyawa diazo (zat pewarna sintetik)", "radioaktivitas gross-α", "radioaktivitas gross-β"],
            prosedur: ["SNI 06-6989.3:2019 (pH)", "SNI 06-6989.9:2004 (COD)", "SNI 06-6989.11:2019 (TDS)", "SNI 06-6989.23:2005 (minyak & lemak)", "SNI 06-6989.27:2019 (Cd, AAS)", "SNI 06-6989.29:2005 (fenol)", "SNI 06-6989.30:2005 (BOD)", "SNI 06-6989.38:2005 (DO)", "SNI 6989.1:2019 (pengambilan contoh uji)", "SNI 6989.19:2009 (F⁻)", "SNI 6989.20:2019 (NO₂-N)", "SNI 6989.22:2004 (Cl⁻)", "SNI 6989.34:2009 (bau)", "SNI 6989.46:2009 (warna)", "SNI 6989.68:2009 (S²⁻)", "SNI 6989.69:2009 (NO₃-N)", "SNI 6989.71:2009 (total P)", "SNI 6989.84:2019 (logam berat, AAS)", "SNI 8995:2021 (MBAS)", "SNI 6989.31:2021 (NO₃-N)", "SNI 9063:2022 (total coliform & E. coli, MPN)", "M-LP-720-TDS (padatan terlarut total)", "ISO 9308-1:2014 (E. coli & coliform, filtrasi membran)"]
        },
        "Air Minum": {
            tipeLaporan: "air",
            parameter: ["Padatan tersuspensi total (TSS)", "derajat keasaman (pH)", "amonia (NH₃-N)", "sulfat (SO₄²⁻)", "kadmium terlarut (Cd)", "nikel terlarut (Ni)", "krom terlarut (Cr)", "seng terlarut (Zn)", "mangan terlarut (Mn)", "tembaga terlarut (Cu)", "timbal terlarut (Pb)", "besi terlarut (Fe)", "barium terlarut (Ba)", "aluminium terlarut (Al)", "kalium terlarut (K)", "kobalt terlarut (Co)", "padatan terlarut total (TDS) secara gravimetri", "suhu", "perak terlarut (Ag)", "ortofosfat", "total fosfor", "daya hantar listrik (DHL)", "krom heksavalen (Cr⁶⁺)", "klorida (Cl⁻)", "nilai permanganat", "fluorida (F⁻)", "nitrit terlarut (NO₂-N)", "total coliform", "Escherichia coli", "kekeruhan", "bau", "air raksa terlarut (Hg)", "arsen terlarut (As)", "selenium terlarut (Se)", "sianida terlarut (CN⁻)", "sulfida terlarut", "klor bebas", "nitrat terlarut (NO₃-N)", "surfaktan anionik (MBAS)", "fenol", "warna", "boron terlarut (B)", "toluena", "benzena", "dieldrin", "karbon organik total/hidrokarbon poliaromatis (PAH)", "parakuat diklorida", "aluminium fosfida", "magnesium fosfida", "sulfuril fluorida", "metil bromida", "seng fosfida", "dikuat dibromida", "etil format", "fosfin", "asam sulfat", "formaldehida", "metanol", "N-metilpirolidon", "piridin basa", "lindan", "heptaklor", "eldrin", "endosulfan", "residu karbamat", "organoklorin", "α-BHC", "4,4′-DDT", "khlordan", "toksafen", "mirex", "polychlorinated biphenyl (PCB)", "heksaklorobenzena (HCB)", "organofosfat", "piretroid", "profenofos", "senyawa diazo (zat pewarna sintetik)", "radioaktivitas gross-α", "radioaktivitas gross-β"],
            prosedur: ["SNI 06-6989.3:2019 (pH)", "SNI 06-6989.9:2004 (COD)", "SNI 06-6989.11:2019 (TDS)", "SNI 06-6989.23:2005 (minyak & lemak)", "SNI 06-6989.27:2019 (Cd, AAS)", "SNI 06-6989.29:2005 (fenol)", "SNI 06-6989.30:2005 (BOD)", "SNI 06-6989.38:2005 (DO)", "SNI 6989.1:2019 (pengambilan contoh uji)", "SNI 6989.19:2009 (F⁻)", "SNI 6989.20:2019 (NO₂-N)", "SNI 6989.22:2004 (Cl⁻)", "SNI 6989.34:2009 (bau)", "SNI 6989.46:2009 (warna)", "SNI 6989.68:2009 (S²⁻)", "SNI 6989.69:2009 (NO₃-N)", "SNI 6989.71:2009 (total P)", "SNI 6989.84:2019 (logam berat, AAS)", "SNI 8995:2021 (MBAS)", "SNI 6989.31:2021 (NO₃-N)", "SNI 9063:2022 (total coliform & E. coli, MPN)", "M-LP-720-TDS (padatan terlarut total)", "ISO 9308-1:2014 (E. coli & coliform, filtrasi membran)"]
        },
        "Air Permukaan": {
            tipeLaporan: "air",
            parameter: ["Padatan tersuspensi total (TSS)", "derajat keasaman (pH)", "amonia (NH₃-N)", "kebutuhan oksigen kimiawi (COD)", "minyak dan lemak", "sulfat (SO₄²⁻)", "kadmium terlarut (Cd)", "nikel terlarut (Ni)", "krom terlarut (Cr)", "seng terlarut (Zn)", "mangan terlarut (Mn)", "tembaga terlarut (Cu)", "timbal terlarut (Pb)", "besi terlarut (Fe)", "barium terlarut (Ba)", "kobalt terlarut (Co)", "padatan terlarut total (TDS)", "suhu", "perak terlarut (Ag)", "ortofosfat", "total fosfor", "padatan terlarut total (TDS) secara gravimetri", "daya hantar listrik (DHL)", "krom heksavalen (Cr⁶⁺)", "klorida (Cl⁻)", "nilai permanganat", "fluorida (F⁻)", "nitrit (NO₂-N)", "total coliform", "fecal coliform", "air raksa terlarut (Hg)", "arsen terlarut (As)", "selenium terlarut (Se)", "sianida (CN⁻)", "sulfida", "klor bebas", "nitrat (NO₃-N)", "surfaktan anionik (MBAS)", "fenol", "warna", "kebutuhan oksigen biokimiawi (BOD)", "oksigen terlarut (DO)", "total nitrogen (sebagai N)", "boron terlarut (B)", "aldrin/dieldrin", "BHC", "khlordan", "DDT", "endrin", "heptaklor", "lindan", "metoksiklor", "toksafen", "radioaktivitas gross-α", "radioaktivitas gross-β", "klorofil-a", "sampah"],
            prosedur: ["SNI 06-6989.3:2019 (pH)", "SNI 06-6989.9:2004 (COD)", "SNI 06-6989.11:2019 (TDS)", "SNI 06-6989.23:2005 (minyak & lemak)", "SNI 06-6989.27:2019 (Cd, AAS)", "SNI 06-6989.29:2005 (fenol)", "SNI 06-6989.30:2005 (BOD)", "SNI 06-6989.38:2005 (DO)", "SNI 6989.1:2019 (pengambilan contoh uji)", "SNI 6989.19:2009 (F⁻)", "SNI 6989.20:2019 (NO₂-N)", "SNI 6989.22:2004 (Cl⁻)", "SNI 6989.46:2009 (warna)", "SNI 6989.68:2009 (S²⁻)", "SNI 6989.71:2009 (total P)", "SNI 6989.84:2019 (logam berat, AAS)", "SNI 8995:2021 (MBAS)", "SNI 6989.31:2021 (NO₃-N)", "SNI 9063:2022 (total coliform & E. coli, MPN)", "M-LP-720-TDS (padatan terlarut total)", "SM APHA 23rd Ed., 9221B&C (2017) (coliform & E. coli, metode tabung ganda)", "SM APHA 23rd Ed., 9221B, C & E (2017) (total coliform, fecal coliform & E. coli, metode MPN)"]
        },
        "Air Bersih": {
            tipeLaporan: "air",
            parameter: ["Padatan tersuspensi total (TSS)", "derajat keasaman (pH)", "amonia (NH₃-N)", "sulfat (SO₄²⁻)", "kadmium terlarut (Cd)", "nikel terlarut (Ni)", "krom terlarut (Cr)", "seng terlarut (Zn)", "mangan terlarut (Mn)", "tembaga terlarut (Cu)", "timbal terlarut (Pb)", "besi terlarut (Fe)", "barium terlarut (Ba)", "aluminium terlarut (Al)", "kalium terlarut (K)", "kobalt terlarut (Co)", "padatan terlarut total (TDS) secara gravimetri", "suhu", "perak terlarut (Ag)", "ortofosfat", "total fosfor", "daya hantar listrik (DHL)", "krom heksavalen terlarut (Cr⁶⁺)", "klorida (Cl⁻)", "nilai permanganat", "fluorida (F⁻)", "nitrit terlarut (NO₂-N)", "total coliform", "Escherichia coli", "kekeruhan", "warna", "bau", "nitrat terlarut (NO₃-N)"],
            prosedur: ["SNI 06-6989.3:2019 (pH)", "SNI 06-6989.9:2004 (COD)", "SNI 06-6989.11:2019 (TDS)", "SNI 06-6989.23:2005 (minyak & lemak)", "SNI 06-6989.27:2019 (Cd, AAS)", "SNI 06-6989.29:2005 (fenol)", "SNI 06-6989.30:2005 (BOD)", "SNI 06-6989.38:2005 (DO)", "SNI 6989.1:2019 (pengambilan contoh uji)", "SNI 6989.19:2009 (F⁻)", "SNI 6989.20:2019 (NO₂-N)", "SNI 6989.22:2004 (Cl⁻)", "SNI 6989.34:2009 (bau)", "SNI 6989.46:2009 (warna)", "SNI 6989.68:2009 (S²⁻)", "SNI 6989.69:2009 (NO₃-N)", "SNI 6989.71:2009 (total P)", "SNI 6989.84:2019 (logam berat, AAS)", "SNI 8995:2021 (MBAS)", "SNI 6989.31:2021 (NO₃-N)", "SNI 9063:2022 (total coliform & E. coli, MPN)", "M-LP-720-TDS (padatan terlarut total)", "ISO 9308-1:2014 (E. coli & coliform, metode filtrasi membran)"]
        },
        "Udara Ambien": {
            tipeLaporan: "udara",
            parameter: ["Sulfur dioksida (SO₂)", "Nitrogen dioksida (NO₂)", "Carbon Monoksida (CO)", "Oksidan fotokimia (Ox) sebagai ozon (O₃)", "Hidrokarbon Non Metana (NMHC)", "Partikel tersuspensi total (TSP)", "Partikel dengan ukuran ≤ 10 µm (PM 10)", "Partikel dengan ukuran ≤ 2,5 µm (PM 2,5)", "Timbal (Pb)", "Temperatur", "Kelembaban", "Amoniak (NH₃)", "Metil merkaptan (CH₃SH)**", "Hidrogen sulfida (H₂S)", "Metil sulfida ((CH₃)₂)S**", "Stirena (C₆H₈CHCH₂)**"],
            prosedur: ["SNI 7119-7:2017", "SNI 7119-2:2017", "M-LP-713-AMB (Portable Gas Monitor)", "SNI 7119-3:2017", "SNI 7119-4:2017", "SNI 7119-8:2017", "SNI 19-7119.1-2005", "M-LP-721-HRS (Spektrofotometri)", "SNI 19-7119.6-2005 (Lokasi sampling ambient)", "SNI 19-7119.9-2005 (Roadside)"]
        },
        "Udara Lingkungan Kerja": {
            tipeLaporan: "udara",
            parameter: ["Sulfur dioksida (SO₂)", "Nitrogen dioksida (NO₂)", "Carbon Monoksida (CO)", "Oksidan fotokimia (Ox) sebagai ozon (O₃)", "Hidrokarbon Non Metana (NMHC)", "Partikel tersuspensi total (TSP)", "Partikel dengan ukuran ≤ 10 µm (PM 10)", "Partikel dengan ukuran ≤ 2,5 µm (PM 2,5)", "Timbal (Pb)", "Temperatur", "Kelembaban", "Amoniak (NH₃)", "Metil merkaptan (CH₃SH)**", "Hidrogen sulfida (H₂S)", "Metil sulfida ((CH₃)₂)S**", "Stirena (C₆H₈CHCH₂)**"],
            prosedur: ["SNI 7119-7:2017", "SNI 7119-2:2017", "M-LP-713-AMB (Portable Gas Monitor)", "SNI 7119-3:2017", "SNI 7119-4:2017", "SNI 7119-8:2017", "SNI 19-7119.1-2005", "M-LP-721-HRS (Spektrofotometri)", "SNI 19-7119.6-2005 (Lokasi sampling ambient)", "SNI 19-7119.9-2005 (Roadside)"]
        },
        "Udara dalam Ruang di Fasilitas Pelayanan Kesehatan": {
            tipeLaporan: "udara",
            parameter: ["Suhu", "Kelembaban", "Pencahayaan", "Debu", "Kebisingan", "Sulfur dioksida (SO₂)", "Nitrogen dioksida (NO₂)", "Carbon Monoksida (CO)", "Oksidan fotokimia (Ox) sebagai ozon (O₃)", "Hidrokarbon Non Metana (NMHC)", "Partikel tersuspensi total (TSP)", "Partikel dengan ukuran ≤ 10 µm (PM 10)", "Partikel dengan ukuran ≤ 2,5 µm (PM 2,5)", "Timbal (Pb)", "Temperatur", "Kelembaban", "Amoniak (NH₃)", "Metil merkaptan (CH₃SH)**", "Hidrogen sulfida (H₂S)", "Metil sulfida ((CH₃)₂)S**", "Stirena (C₆H₈CHCH₂)**"],
            prosedur: ["SNI 7230:2009"]
        },
        "Emisi sumber bergerak": {
            tipeLaporan: "udara",
            parameter: ["Opasitas"],
            prosedur: ["SNI 09-7118.2-2005"]
        },
        "Emisi sumber tidak bergerak": {
            tipeLaporan: "udara",
            parameter: ["Sulfur dioksida (SO₂)", "Nitrogen dioksida (NO₂)", "Carbon Monoksida (CO)", "Carbon dioksida (CO₂)", "Nitrogen oksida (NOx)", "Oksigen (O₂)", "Opasitas", "Kecepatan Linier/Laju alir (velocity)", "Kadar Uap Air", "Partikulat", "Hidrogen flourida (HF)", "Hidrogen sulfida (H₂S)", "Hidrogen klorida (HCl)", "Amoniak (NH₃)", "Gas Klorin (Cl₂)", "Total sulfur tereduksi (TRS)", "Air Raksa (Hg)", "Timbal (Pb)", "Arsen (As)", "Cadmium (Cd)", "Seng (Zn)", "Antimony (Sb)", "Talium (TI)", "Dioksin dan Furan"],
            prosedur: ["SNI 7117.13-2009", "M-LV-712-OPS"]
        },
        "Tingkat Kebisingan": {
            tipeLaporan: "udara",
            parameter: ["Tingkat kebisingan", "Tingkat kebisingan sesaat", "Tingkat kebisingan lingkungan"],
            prosedur: ["SNI 8427 : 2017", "SNI 19-7119.9-2005", "SNI 19-7119.6-2005"]
        },
        "Tingkat Kebisingan Lingkungan": {
            tipeLaporan: "udara",
            parameter: ["Tingkat kebisingan", "Tingkat kebisingan sesaat", "Tingkat kebisingan lingkungan"],
            prosedur: ["SNI 8427 : 2017", "SNI 19-7119.9-2005", "SNI 19-7119.6-2005"]
        },
        "Tingkat Kebisingan Lingkungan Kerja": {
            tipeLaporan: "udara",
            parameter: ["Tingkat kebisingan", "Tingkat kebisingan sesaat", "Tingkat kebisingan lingkungan"],
            prosedur: ["SNI 8427 : 2017", "SNI 19-7119.9-2005", "SNI 19-7119.6-2005"]
        },
        "Tingkat Getaran": {
            tipeLaporan: "udara",
            parameter: ["Tingkat Getaran", "Getaran untuk pemaparan lengan dan tangan (HAV)", "Getaran untuk pemaparan seluruh tubuh (WBV)"],
            prosedur: ["M-LP-711-GET (Vibration Meter)", "SNI IEC 60034-14-2009"]
        },
        "Tingkat Getaran Lingkungan Kerja": {
            tipeLaporan: "udara",
            parameter: ["Tingkat Getaran", "Getaran untuk pemaparan lengan dan tangan (HAV)", "Getaran untuk pemaparan seluruh tubuh (WBV)"],
            prosedur: ["M-LP-711-GET (Vibration Meter)", "SNI IEC 60034-14-2009"]
        }
    };

let jenisLaporanTerpilih = null;
let contohCounter = 0;

function getTipeLaporanDariJenis(jenisContoh) {
    if (!jenisContoh) return '';
    const data = dataSampling[jenisContoh];
    return data ? data.tipeLaporan : '';
}

function tambahContoh(data = null) {
    const container = document.getElementById("contohContainer");
    const div = document.createElement("div");
    div.className = "contoh-item card card-body mb-3";
    div.id = `contoh_item_${contohCounter}`;
    const currentCounter = contohCounter;
    
    // Tentukan jenis laporan yang sudah terpilih dari data lama atau baru
    if(data && data.jenis_contoh && jenisLaporanTerpilih === null) {
        jenisLaporanTerpilih = getTipeLaporanDariJenis(data.jenis_contoh);
    }
    
    let jenisContohOptions = '';
    const semuaJenisContoh = Object.keys(dataSampling);

    if (jenisLaporanTerpilih) {
        const filteredJenisContoh = semuaJenisContoh.filter(nama => dataSampling[nama].tipeLaporan === jenisLaporanTerpilih);
        jenisContohOptions = filteredJenisContoh.map(key => `<option value="${key}" ${data && data.jenis_contoh === key ? 'selected' : ''}>${key}</option>`).join('');
    } else {
        jenisContohOptions = semuaJenisContoh.map(key => `<option value="${key}" ${data && data.jenis_contoh === key ? 'selected' : ''}>${key}</option>`).join('');
    }

    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Contoh Uji #${currentCounter + 1}</h5>
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusContoh(${currentCounter})">Hapus</button>
        </div>
        <input type="hidden" name="contoh[${currentCounter}][tipe_laporan]" id="tipe_laporan_${currentCounter}">
        
        <div class="form-group">
            <label for="nama_contoh_${currentCounter}">Nama Contoh <span class="text-danger">*</span></label>
            <input type="text" id="nama_contoh_${currentCounter}" name="contoh[${currentCounter}][nama_contoh]" class="form-control" value="${data ? data.nama_contoh : ''}" required>
        </div>
        
        <div class="form-group">
            <label for="jenis_contoh_${currentCounter}">Jenis Contoh <span class="text-danger">*</span></label>
            <select class="form-control" name="contoh[${currentCounter}][jenis_contoh]" id="jenis_contoh_${currentCounter}" onchange="updateDynamicFields(${currentCounter})" required>
                <option value="">-- Pilih Jenis Contoh --</option>${jenisContohOptions}
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="merek_${currentCounter}">Etiket / Merek <span class="text-danger">*</span></label>
                <input type="text" id="merek_${currentCounter}" class="form-control" name="contoh[${currentCounter}][merek]" value="${data ? data.merek : ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="kode_${currentCounter}">Kode Contoh <span class="text-danger">*</span></label>
                <input type="text" id="kode_${currentCounter}" class="form-control" name="contoh[${currentCounter}][kode]" value="${data ? data.kode : ''}" required>
            </div>
        </div>

        <div class="form-group">
            <label for="prosedur_${currentCounter}">Prosedur Pengambilan Contoh <span class="text-danger">*</span></label>
            <div class="option-box" id="prosedur_container_${currentCounter}"><small class="text-muted">Pilih Jenis Contoh terlebih dahulu.</small></div>
        </div>
        
        <div class="form-group">
            <label>Parameter Uji <span class="text-danger">*</span></label>
            <div class="option-box" id="parameter_container_${currentCounter}"><small class="text-muted">Pilih Jenis Contoh terlebih dahulu.</small></div>
        </div>

        <div class="form-group">
            <label for="baku_mutu_${currentCounter}">Baku Mutu <span class="text-danger">*</span></label>
            <select class="form-control" name="contoh[${currentCounter}][baku_mutu]" id="baku_mutu_${currentCounter}" required>
                <option value="">-- Pilih Baku Mutu --</option>
                <option value="KepMen LH No. 50 Tahun 1996">KepMen LH No. 50 Tahun 1996</option>
                <option value="Permenaker No. 25 Tahun 2018">Permenaker No. 25 Tahun 2018</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran VI">PP RI No. 22 Tahun 2021, Lampiran VI</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran VII">PP RI No. 22 Tahun 2021, Lampiran VII</option>
                <option value="PP RI No. 22 Tahun 2021, Lampiran VIII">PP RI No. 22 Tahun 2021, Lampiran VIII</option>
                <option value="PERMENLH No. 05 Tahun 2014 Lampiran III">PERMENLH No. 05 Tahun 2014 Lampiran III</option>
                <option value="PERMEN LHK No. 14 Tahun 2020">Permen LHK No. 14 Tahun 2020</option>
                <option value="PerMen LH No. 07 Tahun 2007, Lampiran I - VII">PerMen LH No. 07 Tahun 2007, Lampiran I - VII</option>
                <option value="Permenkes No. 22 Tahun 2023">Permenkes No. 22 Tahun 2023</option>
                <option value="Permen LHK No. 11 Tahun 2021, Lampiran I">Permen LHK No. 11 Tahun 2021, Lampiran I</option>
                <option value="KepMen LH No. 13 Tahun 1995, Lampiran Vb">KepMen LH No. 13 Tahun 1995, Lampiran Vb</option>
                <option value="Permen LHK P.56 Tahun 2015">Permen LHK P.56 Tahun 2015</option>
                <option value="Permen LHK No. P.17 Tahun 2018">Permen LHK No. P.17 Tahun 2018</option>
                <option value="Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996 (Tingkat Kebisingan)">Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996 (Tingkat Kebisingan)</option>
                <option value="Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996 (Tingkat Getaran)">Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996 (Tingkat Getaran)</option>                
                <option value="Lainnya">Lainnya...</option>
            </select>
            <input type="text" class="form-control mt-2" name="contoh[${currentCounter}][baku_mutu_lainnya]" id="baku_mutu_lainnya_${currentCounter}" style="display:none;" value="${data && data.baku_mutu_lainnya ? data.baku_mutu_lainnya : ''}" placeholder="Masukkan baku mutu lainnya">
        </div>

        <div class="form-group">
            <label for="catatan_${currentCounter}">Catatan Tambahan <span class="text-danger">*</span></label>
            <textarea id="catatan_${currentCounter}" class="form-control" name="contoh[${currentCounter}][catatan]" rows="2" required>${data ? data.catatan : ''}</textarea>
        </div>

    `;
    container.appendChild(div);
    
    // Event listener untuk baku mutu
    document.getElementById(`baku_mutu_${currentCounter}`).addEventListener('change', function() {
        const lainnyaInput = document.getElementById(`baku_mutu_lainnya_${this.id.split('_')[2]}`);
        lainnyaInput.style.display = (this.value === 'Lainnya') ? 'block' : 'none';
        lainnyaInput.required = (this.value === 'Lainnya');
        if (this.value !== 'Lainnya') lainnyaInput.value = '';
    });
    
    // Panggil fungsi-fungsi update untuk mengisi data
    if(data){
         document.getElementById(`jenis_contoh_${currentCounter}`).value = data.jenis_contoh;
         updateDynamicFields(currentCounter, data.parameter, data.prosedur);
         
         const bakuMutuSelect = document.getElementById(`baku_mutu_${currentCounter}`);
         const bakuMutuLainnya = document.getElementById(`baku_mutu_lainnya_${currentCounter}`);
         const bakuMutuOptions = Array.from(bakuMutuSelect.options).map(opt => opt.value);
         if (!bakuMutuOptions.includes(data.baku_mutu)) {
             bakuMutuSelect.value = 'Lainnya';
             bakuMutuLainnya.value = data.baku_mutu;
             bakuMutuLainnya.style.display = 'block';
         } else {
             bakuMutuSelect.value = data.baku_mutu;
         }
    }
    contohCounter++;
}

function hapusContoh(id) {
    document.getElementById(`contoh_item_${id}`).remove();
    const sisaContoh = document.querySelectorAll('.contoh-item');
    if (sisaContoh.length === 0) {
        jenisLaporanTerpilih = null;
        const infoDiv = document.getElementById('jenis-terpilih-info');
        if(infoDiv) infoDiv.remove();
    }
}

function updateDynamicFields(id, selectedParams = null, selectedProsedur = null) {
    const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
    const data = dataSampling[selectedJenisContoh];
    const tipeLaporanInput = document.getElementById(`tipe_laporan_${id}`);
    const jenisLaporan = getTipeLaporanDariJenis(selectedJenisContoh);
    tipeLaporanInput.value = jenisLaporan;
    
    if (selectedJenisContoh) {
        // Hapus pesan jika ada
        const infoDiv = document.getElementById('jenis-terpilih-info');
        if(infoDiv) infoDiv.remove();
        
        // Tampilkan info tipe laporan jika belum ada
        if (jenisLaporanTerpilih === null) {
             jenisLaporanTerpilih = jenisLaporan;
            const container = document.getElementById('contohContainer');
            const newInfoDiv = document.createElement("div");
            newInfoDiv.id = 'jenis-terpilih-info';
            newInfoDiv.className = 'alert alert-info';
            newInfoDiv.innerHTML = `Jenis laporan telah diatur sebagai <strong>${jenisLaporan.charAt(0).toUpperCase() + jenisLaporan.slice(1)}</strong>. Anda hanya dapat menambahkan contoh uji dari jenis yang sama.`;
            container.prepend(newInfoDiv);
        }
    }

    updateProsedur(id, selectedProsedur);
    updateParameters(id, selectedParams);
}

function updateParameters(id, selectedParams = null) {
    const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
    const data = dataSampling[selectedJenisContoh];
    const parameterContainer = document.getElementById(`parameter_container_${id}`);
    let parameters = [];

    if (data && data.parameter) {
        parameters = data.parameter;
    }
    
    if (parameters.length > 0) {
        const paramsArray = selectedParams ? selectedParams.split(', ') : [];
        parameterContainer.innerHTML = `<div class="parameter-grid">${parameters.map(p => `<label class="form-check"><input class="form-check-input" type="checkbox" name="contoh[${id}][parameter][]" value="${p}" ${paramsArray.includes(p) ? 'checked' : ''}><span class="form-check-label">${p}</span></label>`).join('')}</div>`;
    } else {
        parameterContainer.innerHTML = '<small class="text-muted">Pilih Jenis Contoh terlebih dahulu.</small>';
    }
}

function updateProsedur(id, selectedProsedur = null) {
    const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
    const data = dataSampling[selectedJenisContoh];
    const prosedurContainer = document.getElementById(`prosedur_container_${id}`);
    let prosedur = [];

    if (data && data.prosedur) {
        prosedur = data.prosedur;
    }
    
    if (prosedur.length > 0) {
        const prosedurArray = selectedProsedur ? selectedProsedur.split(', ') : [];
        prosedurContainer.innerHTML = `<div class="parameter-grid">${prosedur.map(p => `<label class="form-check"><input class="form-check-input" type="checkbox" name="contoh[${id}][prosedur][]" value="${p}" ${prosedurArray.includes(p) ? 'checked' : ''}><span class="form-check-label">${p}</span></label>`).join('')}</div>`;
    } else {
        prosedurContainer.innerHTML = '<small class="text-muted">Pilih Jenis Contoh terlebih dahulu.</small>';
    }
}
    
document.addEventListener('DOMContentLoaded', function() {
    dataContohLama.forEach(contoh => {
        tambahContoh(contoh);
    });
    // Menghandle kasus tidak ada contoh lama
    if (dataContohLama.length === 0) {
        const container = document.getElementById('contohContainer');
        const infoDiv = document.createElement("div");
        infoDiv.id = 'jenis-terpilih-info';
        infoDiv.className = 'alert alert-info';
        infoDiv.innerHTML = `Laporan ini belum memiliki contoh uji. Setelah menambahkan contoh uji, jenis laporan akan otomatis ditetapkan berdasarkan pilihan Anda dan tidak dapat diubah.`;
        container.prepend(infoDiv);
    }
});

function validateFile(input) {
    const file = input.files[0];
    const errorMessageContainer = input.parentElement.querySelector('.file-error-message');
    errorMessageContainer.textContent = '';
    if (!file) return true;
    const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.pdf)$/i;
    if (!allowedExtensions.exec(file.name)) {
        errorMessageContainer.textContent = 'Format salah! Hanya .pdf, .jpg, .jpeg, .png';
        input.value = '';
        return false;
    }
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        errorMessageContainer.textContent = 'Ukuran terlalu besar! Maksimal 5MB.';
        input.value = '';
        return false;
    }
    return true;
}

document.getElementById('editForm').addEventListener('submit', function(event) {
    event.preventDefault(); // <-- Selalu hentikan submit untuk validasi

    // Dapatkan tombol mana yang diklik
    const submitter = event.submitter;
    const aksi = submitter ? submitter.value : 'draft';

    const errors = [];
    const errorContainer = document.getElementById('validation-error-container');
    errorContainer.innerHTML = '';

    // Validasi field utama
    const requiredFields = {
        'jenis_kegiatan': 'Jenis Kegiatan',
        'perusahaan': 'Nama Perusahaan',
        'alamat': 'Alamat Perusahaan',
        'tanggal_mulai': 'Tanggal Mulai Pelaksanaan',
        'tanggal_selesai': 'Tanggal Selesai Pelaksanaan',
        'pengambil_sampel': 'Pengambil Sampel'
    };

    for (const id in requiredFields) {
        const field = document.getElementById(id);
        if (!field.value.trim()) {
            errors.push(`<b>Informasi Kegiatan:</b> ${requiredFields[id]} wajib diisi.`);
        }
    }

    const pengambilSampel = document.getElementById('pengambil_sampel').value;
    const subKontrakNama = document.getElementById('sub_kontrak_nama');
    if (pengambilSampel === 'Sub Kontrak' && subKontrakNama && !subKontrakNama.value.trim()) {
        errors.push('<b>Informasi Kegiatan:</b> Nama Perusahaan Sub Kontrak wajib diisi.');
    }

    const contohItems = document.querySelectorAll('.contoh-item');
    if (contohItems.length === 0) {
        errors.push('<b>Data Contoh Uji:</b> Anda harus menambahkan minimal satu Contoh Uji.');
    }

    contohItems.forEach((item, index) => {
        const counter = item.id.split('_')[2];
        const prefix = `<b>Contoh Uji #${index + 1}:</b>`;
        
        const requiredContohFields = {
            [`nama_contoh_${counter}`]: 'Nama Contoh',
            [`jenis_contoh_${counter}`]: 'Jenis Contoh',
            [`merek_${counter}`]: 'Etiket / Merek',
            [`kode_${counter}`]: 'Kode',
            [`baku_mutu_${counter}`]: 'Baku Mutu',
            [`catatan_${counter}`]: 'Catatan Tambahan'
        };

        for (const id in requiredContohFields) {
            const field = document.getElementById(id);
            // Perbaikan logika: Cek apakah field ada dan diperlukan sebelum validasi
            if (field && !field.value.trim()) {
                errors.push(`${prefix} ${requiredContohFields[id]} wajib diisi.`);
            }
        }
        
        // Validasi checkbox untuk prosedur dan parameter
        const checkedProsedur = item.querySelectorAll(`#prosedur_container_${counter} input[type="checkbox"]:checked`);
        if (checkedProsedur.length === 0) {
            errors.push(`${prefix} Prosedur Pengambilan Contoh wajib dipilih minimal satu.`);
        }
        const checkedParams = item.querySelectorAll(`#parameter_container_${counter} input[type="checkbox"]:checked`);
        if (checkedParams.length === 0) {
            errors.push(`${prefix} Parameter Uji wajib dipilih minimal satu.`);
        }
    });

    let allFilesAreValid = true;
    this.querySelectorAll('input[type="file"]').forEach(input => {
        if (!validateFile(input)) allFilesAreValid = false;
    });
    if (!allFilesAreValid) {
        errors.push("<b>Dokumen Pendukung:</b> Terdapat file yang tidak sesuai ketentuan.");
    }
    
    // Tampilkan error atau submit form
    if (errors.length > 0) {
        document.getElementById('loadingOverlay').style.display = 'none';
        errorContainer.style.display = 'block';
        errorContainer.innerHTML = '<strong>Harap perbaiki kesalahan berikut:</strong><ul>' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        window.scrollTo(0, 0);
    } else {
        document.getElementById('loadingOverlay').style.display = 'flex';
        // Tambahkan input tersembunyi untuk aksi
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'aksi';
        hiddenInput.value = aksi;
        this.appendChild(hiddenInput);
        this.submit();
    }
});
</script>

<?php
require_once '../templates/footer.php';
?>
</body>
</html>