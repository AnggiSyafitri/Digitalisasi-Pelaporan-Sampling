<?php
// public/formulir_sampling.php

require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$page_title = 'Formulir Pengambilan Contoh';
require_once '../templates/header.php';
?>
<!-- ===== TAMBAHAN CSS UNTUK LOADING OVERLAY ===== -->
<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: none; /* Disembunyikan secara default */
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
        font-size: 1.2rem;
        font-family: sans-serif;
    }
    .file-error-message {
        font-size: 0.8em;
    }
</style>

<!-- ===== TAMBAHAN HTML UNTUK LOADING OVERLAY (Diletakkan setelah <body>) ===== -->
<div class="loading-overlay" id="loadingOverlay">
    <p>Menyimpan data dan mengunggah file, mohon tunggu...</p>
</div>

<div class="container-dashboard" style="padding: 2rem;">
    
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="back-to-dashboard">« Kembali ke Dashboard</a>

    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h2>Formulir Pengambilan Contoh</h2>
        </div>

        <div class="card-body">
            <!-- Container untuk menampilkan error validasi JS -->
            <div id="validation-error-container" class="alert alert-danger" style="display:none;"></div>

            <!-- Menampilkan pesan error dari server (PHP) -->
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['flash_error']; 
                        unset($_SESSION['flash_error']);
                    ?>
                </div>
            <?php endif; ?>

            <form id="samplingForm" action="../actions/simpan_sampling.php" method="post" enctype="multipart/form-data">

                <div class="form-section" style="border:none; padding-bottom:1rem;">
                    <h3>Informasi Kegiatan Sampling</h3>
                    
                    <div class="form-group">
                        <label for="jenis_kegiatan">Jenis Kegiatan <span class="text-danger">*</span></label>
                        <select id="jenis_kegiatan" name="jenis_kegiatan" class="form-control" required>
                            <option value="">-- Pilih Jenis Kegiatan --</option>
                            <option value="Sampling">Sampling</option>
                            <option value="Pengujian">Pengujian</option>
                        </select>
                        <small id="kegiatan_detail_text" class="form-text text-muted"></small>
                    </div>

                    <div class="form-group">
                        <label for="perusahaan">Nama Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" id="perusahaan" name="perusahaan" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat Perusahaan <span class="text-danger">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tanggal">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pengambil_sampel">Pengambil Sampel <span class="text-danger">*</span></label>
                        <select id="pengambil_sampel" name="pengambil_sampel" class="form-control" required>
                            <option value="">-- Pilih Pengambil Sampel --</option>
                            <option value="BSPJI Medan">BSPJI Medan</option>
                            <option value="Sub Kontrak">Sub Kontrak</option>
                        </select>
                    </div>

                    <div class="form-group" id="sub_kontrak_wrapper" style="display:none;">
                        <label for="sub_kontrak_nama">Nama Perusahaan Sub Kontrak <span class="text-danger">*</span></label>
                        <input type="text" id="sub_kontrak_nama" name="sub_kontrak_nama" class="form-control">
                    </div>
                </div>

                <hr class="mb-4">

                <div class="form-section" style="border:none; padding-bottom:0;">
                    <h3>Data Contoh Uji</h3>
                    <p>Tambahkan satu atau lebih contoh uji yang diambil selama kegiatan sampling.</p>
                    <button type="button" class="btn btn-primary mb-3" onclick="tambahContoh()">
                        Tambah Contoh Uji
                    </button>
                    <div id="contohContainer"></div>
                </div>

                <div class="button-group mt-4">
                    <button type="submit" name="aksi" value="draft" class="btn btn-secondary">
                        Simpan sebagai Draft
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
    let jenisLaporanTerpilih = null;

    function getTipeLaporanDariNama(namaContoh) {
        if (!namaContoh) return '';
        if (namaContoh.includes("Air")) return 'air';
        if (namaContoh.includes("Udara")) return 'udara';
        if (namaContoh.includes("Kebisingan")) return 'kebisingan';
        if (namaContoh.includes("Getaran")) return 'getaran';
        return '';
    }

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

    const dataSampling = {
        "Air Limbah": {
            jenisContoh: ["Air Limbah Industri", "Air Limbah Domestik"],
            parameter: ["Total Suspensi Solid", "pH", "Kekeruhan", "Amonia sebagai NH3-N", "COD sebagai O2", "Minyak dan Lemak", "Sulfat", "Cadmium (Cd)total", "Nikel (Ni) total", "Chrom (Cr) total", "Seng (Zn) total", "Mangan (Mn) terlarut", "Tembaga (Cu) total", "Timbal (Pb) total", "Besi (Fe) Terlarut", "Barium (Ba) total", "Cobalt (Co) total", "Padatan Terlarut Total (TDS Meter)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Krom Hexavalen (Cr-VI)", "Nitrit ( NO2 )", "Total Coliform"],
            prosedur: ["SNI 8990 : 2021 (Metode Pangambilan Contoh Air Limbah)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Air Permukaan": {
            jenisContoh: ["Air Sungai", "Air Danau", "Air Waduk", "Air Kolam", "Air Parit", "Air Irigasi"],
            parameter: ["Total Suspensi Solid", "pH", "Kekeruhan", "Amonia sebagai NH3-N", "Sulfat", "Cadmium (Cd) terlarut", "Nikel (Ni) terlarut", "Chrom (Cr) Terlarut", "Seng (Zn) terlarut", "Mangan (Mn) terlarut", "Tembaga (Cu) terlarut", "Timbal (Pb) Terlarut", "Besi (Fe) terlarut", "Barium (Ba) terlarut", "Kobalt (Co) terlarut", "Padatan Terlarut Total (TDS)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Argentum (Ag) terlarut", "Krom Hexavalen (Cr-VI)", "Khlorida ( CI )", "Angka Permanganat (KMnO4)", "Fluorida", "Nitrit ( NO2 )", "Total Coliform", "Fecal Coliform"],
            prosedur: ["SNI 6989.57:2008 (Metode Pengambilan Contoh Air Permukaan)", "SNI 8995 : 2021 (Metode Pengambilan Contoh Uji Air Untuk Pengujian Fisika dan Kimia)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Air Tanah": {
            jenisContoh: ["Air Sumur Bor", "Air Sumur", "Sumur Artesis", "Sumber Mata Air"],
            parameter: ["Total Suspensi Solid", "pH", "Kekeruhan", "Amonia sebagai NH3-N", "Sulfat", "Cadmium (Cd) terlarut", "Nikel (Ni) terlarut", "Chrom (Cr) Terlarut", "Seng (Zn) terlarut", "Mangan (Mn) terlarut", "Tembaga (Cu) terlarut", "Timbal (Pb) Terlarut", "Besi (Fe) terlarut", "Barium (Ba) terlarut", "Aluminium (Al) terlarut", "Kalium (K) terlarut", "Kobalt (Co) terlarut", "Padatan Terlarut Total (TDS)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Argentum (Ag) terlarut", "Krom Hexavalen (Cr-VI)", "Khlorida ( CI )", "Angka Permanganat (KMnO4)", "Fluorida", "Nitrit ( NO2 )", "Escherichia coli", "Total Coliform"],
            prosedur: ["SNI 8995 : 2021 (Metode Pengambilan Contoh Uji Air Untuk Pengujian Fisika dan Kimia)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Udara": {
            jenisContoh: ["Udara Emisi dari sumber bergerak", "Udara Emisi dari sumber tidak bergerak", "Udara Ambien"],
            parameter: {
                "Udara Emisi dari sumber bergerak": ["Opasitas"],
                "Udara Ambien": ["Sulfur dioksida (SO2)", "Nitrogen dioksida (NO2)", "Carbon monoksida (CO)", "TSP", "Timbal (Pb)", "Oksidan (O3)", "Amoniak (NH3)", "Hidrogen sulfida (H2S)"],
                "Udara Emisi dari sumber tidak bergerak": ["Sulfur dioksida (SO2)", "Nitrogen dioksida (NO2)", "Karbon monoksida (CO)", "Nitrogen Oksida (NOx)", "Oksigen (O2)", "Karbon dioksida (CO2)", "Opasitas", "Kecepatan Linier/Laju alir (velocity)", "Berat Molekul kering", "Kadar Uap Air", "Partikulat"]
            },
            prosedur: ["M-LP-714-SMO (Smoke Meter Opacity) (Pengambilan contoh uji udara sumber emisi bergerak)", "SNI 19-7119.6-2005 (Metode Pengambilan Contoh Udara Ambien)", "SNI 19-7119.9-2005 (Metode Pengambilan Contoh Udara Roadside)", "SNI 7117.13-2009 (Pengambilan contoh uji udara emisi tidak bergerak)"]
        },
        "Tingkat Kebisingan": { 
            jenisContoh: ["Tingkat kebisingan"], 
            parameter: ["Tingkat Kebisingan", "Tingkat kebisingan sesaat"], 
            prosedur: ["SNI 19-7119.6-2005 (Metode Pengambilan Contoh Udara Ambien)", "SNI 19-7119.9-2005 (Metode Pengambilan Contoh Udara Roadside)", "SNI 7231:2009 (Tingkat Kebisingan Lingkungan)", "SNI 8427 : 2017 (Metode Pengambilan contoh uji kebisingan)"] 
        },
        "Tingkat Getaran": { 
            jenisContoh: ["Tingkat Getaran", "Tingkat Getaran Lingkungan Kerja"], 
            parameter: ["Tingkat Getaran", "Getaran Pemaparan Seluruh Tubuh", "Getaran Pemaparan Tangan dan Lengan"], 
            prosedur: ["M-LP-711-GET (Vibration Meter) (Metode Pengambilan contoh uji Getaran)", "SNI IEC 60034-14-2009 (Getaran Mesin)", "SNI 8428 : 2017 (Metode Pengambilan contoh uji getaran)"] 
        }
    };

    let contohCounter = 0;

    function tambahContoh() {
        const container = document.getElementById("contohContainer");
        const div = document.createElement("div");
        div.className = "contoh-item card card-body mb-3";
        div.id = `contoh_item_${contohCounter}`;
        const currentCounter = contohCounter;

        let namaContohOptions = '';
        const semuaNamaContoh = Object.keys(dataSampling);
        if (jenisLaporanTerpilih) {
            const filteredNamaContoh = semuaNamaContoh.filter(nama => getTipeLaporanDariNama(nama) === jenisLaporanTerpilih);
            namaContohOptions = filteredNamaContoh.map(key => `<option value="${key}">${key}</option>`).join('');
        } else {
            namaContohOptions = semuaNamaContoh.map(key => `<option value="${key}">${key}</option>`).join('');
        }

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Contoh Uji #${currentCounter + 1}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="hapusContoh(${currentCounter})">Hapus</button>
            </div>
            <input type="hidden" name="contoh[${currentCounter}][tipe_laporan]" id="tipe_laporan_${currentCounter}">
            
            <div class="form-group">
                <label for="nama_contoh_${currentCounter}">Nama Contoh <span class="text-danger">*</span></label>
                <select class="form-control" name="contoh[${currentCounter}][nama_contoh]" id="nama_contoh_${currentCounter}" onchange="updateDynamicFields(${currentCounter})" required>
                    <option value="">-- Pilih Bahan --</option>${namaContohOptions}
                </select>
            </div>
            
            <div class="form-group">
                <label for="jenis_contoh_${currentCounter}">Jenis Contoh <span id="jenis_contoh_star_${currentCounter}" class="text-danger">*</span></label>
                <select class="form-control" name="contoh[${currentCounter}][jenis_contoh]" id="jenis_contoh_${currentCounter}" onchange="updateParameters(${currentCounter})" disabled required>
                    <option value="">-- Pilih Nama Contoh terlebih dahulu --</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="merek_${currentCounter}">Etiket / Merek <span class="text-danger">*</span></label>
                    <input type="text" id="merek_${currentCounter}" class="form-control" name="contoh[${currentCounter}][merek]" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="kode_${currentCounter}">Kode <span class="text-danger">*</span></label>
                    <input type="text" id="kode_${currentCounter}" class="form-control" name="contoh[${currentCounter}][kode]" required>
                </div>
            </div>

            <div class="form-group">
                <label for="prosedur_${currentCounter}">Prosedur Pengambilan Contoh <span class="text-danger">*</span></label>
                <select class="form-control" name="contoh[${currentCounter}][prosedur]" id="prosedur_${currentCounter}" required disabled>
                     <option value="">-- Pilih Prosedur --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Parameter Uji <span class="text-danger">*</span></label>
                <div class="parameter-grid" id="parameter_container_${currentCounter}"><small class="text-muted">Pilih Nama dan Jenis Contoh terlebih dahulu.</small></div>
            </div>

            <div class="form-group">
                <label for="baku_mutu_${currentCounter}">Baku Mutu <span class="text-danger">*</span></label>
                <select class="form-control" name="contoh[${currentCounter}][baku_mutu]" id="baku_mutu_${currentCounter}" required>
                    <option value="">-- Pilih Baku Mutu --</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran I">PP RI No. 22 Tahun 2021, Lampiran I</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran III">PP RI No. 22 Tahun 2021, Lampiran III</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran IV">PP RI No. 22 Tahun 2021, Lampiran IV</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran VI">PP RI No. 22 Tahun 2021, Lampiran VI</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran VII">PP RI No. 22 Tahun 2021, Lampiran VII</option>
                    <option value="PERMENLH No. 05 Tahun 2014 Lampiran III">PERMENLH No. 05 Tahun 2014 Lampiran III</option>
                    <option value="PERMEN LHK No. 14 Tahun 2020">Permen LHK No. 14 Tahun 2020</option>
                    <option value="Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996 (Tingkat Kebisingan)">Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996 (Tingkat Kebisingan)</option>
                    <option value="Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996 (Tingkat Getaran)">Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996 (Tingkat Getaran)</option>                
                    <option value="Lainnya">Lainnya...</option>
                </select>
                <input type="text" class="form-control mt-2" name="contoh[${currentCounter}][baku_mutu_lainnya]" id="baku_mutu_lainnya_${currentCounter}" style="display:none;" placeholder="Masukkan baku mutu lainnya">
            </div>

            <div class="form-group">
                <label for="catatan_${currentCounter}">Catatan Tambahan <span class="text-danger">*</span></label>
                <textarea id="catatan_${currentCounter}" class="form-control" name="contoh[${currentCounter}][catatan]" rows="2" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="file_berita_acara_${currentCounter}">Upload Berita Acara (Opsional)</label>
                    <input type="file" id="file_berita_acara_${currentCounter}" name="contoh[${currentCounter}][file_berita_acara]" class="form-control-file" onchange="validateFile(this)">
                    <small class="form-text text-muted">PDF, JPG, JPEG, PNG (Maks 5MB)</small>
                    <div class="file-error-message text-danger small mt-1"></div>
                </div>
                <div class="form-group col-md-6">
                    <label for="file_sppc_${currentCounter}">Upload SPPC (Opsional)</label>
                    <input type="file" id="file_sppc_${currentCounter}" name="contoh[${currentCounter}][file_sppc]" class="form-control-file" onchange="validateFile(this)">
                    <small class="form-text text-muted">PDF, JPG, JPEG, PNG (Maks 5MB)</small>
                    <div class="file-error-message text-danger small mt-1"></div>
                </div>
            </div>
        `;
        container.appendChild(div);
        document.getElementById(`baku_mutu_${currentCounter}`).addEventListener('change', function() {
            const lainnyaInput = document.getElementById(`baku_mutu_lainnya_${this.id.split('_')[2]}`);
            lainnyaInput.style.display = (this.value === 'Lainnya') ? 'block' : 'none';
            lainnyaInput.required = (this.value === 'Lainnya');
            if (this.value !== 'Lainnya') lainnyaInput.value = '';
        });
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

    function updateDynamicFields(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const jenisContohSelect = document.getElementById(`jenis_contoh_${id}`);
        const jenisContohStar = document.getElementById(`jenis_contoh_star_${id}`);
        const tipeLaporanInput = document.getElementById(`tipe_laporan_${id}`);

        const tipeLaporan = getTipeLaporanDariNama(selectedNamaContoh);
        tipeLaporanInput.value = tipeLaporan;
        
        const sisaContoh = document.querySelectorAll('.contoh-item');
        if (sisaContoh.length > 0 && jenisLaporanTerpilih === null && tipeLaporan) {
            jenisLaporanTerpilih = tipeLaporan;
            const container = document.getElementById('contohContainer');
            const infoDivLama = document.getElementById('jenis-terpilih-info');
            if(infoDivLama) infoDivLama.remove();
            const infoDiv = document.createElement("div");
            infoDiv.id = 'jenis-terpilih-info';
            infoDiv.className = 'alert alert-info';
            infoDiv.innerHTML = `Jenis laporan telah diatur sebagai <strong>${tipeLaporan.charAt(0).toUpperCase() + tipeLaporan.slice(1)}</strong>. Anda hanya dapat menambahkan contoh uji dari jenis yang sama.`;
            container.prepend(infoDiv);
        }

        const jenisKegiatanSelect = document.getElementById('jenis_kegiatan');
        const kegiatanDetailText = document.getElementById('kegiatan_detail_text');
        if (jenisKegiatanSelect.value === 'Pengujian' && selectedNamaContoh) {
            kegiatanDetailText.textContent = `Hasil: Pengujian ${selectedNamaContoh}`;
        } else {
            kegiatanDetailText.textContent = '';
        }

        if (data && data.jenisContoh) {
            jenisContohSelect.disabled = false;
            jenisContohSelect.required = true;
            jenisContohStar.style.display = 'inline';
            jenisContohSelect.innerHTML = '<option value="">-- Pilih Jenis --</option>' + data.jenisContoh.map(jc => `<option value="${jc}">${jc}</option>`).join('');
        } else {
            jenisContohSelect.disabled = true;
            jenisContohSelect.required = false;
            jenisContohStar.style.display = 'none';
            jenisContohSelect.innerHTML = '<option value="">-- Tidak Ada --</option>';
        }
        updateParameters(id);
        updateProsedur(id);
    }
    
    function updateParameters(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const parameterContainer = document.getElementById(`parameter_container_${id}`);
        let parameters = [];

        if (data && data.parameter) {
            if (Array.isArray(data.parameter)) {
                parameters = data.parameter;
            } 
            else if (typeof data.parameter === 'object' && selectedJenisContoh) {
                parameters = data.parameter[selectedJenisContoh] || [];
            }
        }
        
        if (parameters.length > 0) {
            parameterContainer.innerHTML = parameters.map(p => `<label class="form-check"><input class="form-check-input" type="checkbox" name="contoh[${id}][parameter][]" value="${p}"><span class="form-check-label">${p}</span></label>`).join('');
        } else {
            parameterContainer.innerHTML = '<small class="text-muted">Pilih Nama dan Jenis Contoh terlebih dahulu.</small>';
        }
    }
    
    function updateProsedur(id) {
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const prosedurSelect = document.getElementById(`prosedur_${id}`);
        let prosedur = [];

        if (data && data.prosedur) {
            prosedurSelect.disabled = false;
            prosedur = data.prosedur;
        }
        
        if (prosedur.length > 0) {
            prosedurSelect.innerHTML = '<option value="">-- Pilih Prosedur --</option>' + prosedur.map(p => `<option value="${p}">${p}</option>`).join('');
        } else {
            prosedurSelect.disabled = true;
            prosedurSelect.innerHTML = '<option value="">-- Pilihan tidak tersedia --</option>';
        }
    }

    // ===== FUNGSI BARU UNTUK VALIDASI FILE REAL-TIME =====
    function validateFile(input) {
        const file = input.files[0];
        const errorMessageContainer = input.parentElement.querySelector('.file-error-message');
        errorMessageContainer.textContent = ''; // Kosongkan pesan error

        if (!file) {
            return true; // Tidak ada file, valid karena opsional
        }

        const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.pdf)$/i;
        if (!allowedExtensions.exec(file.name)) {
            errorMessageContainer.textContent = 'Format salah! Hanya .pdf, .jpg, .jpeg, .png';
            input.value = ''; // Batalkan pilihan file
            return false;
        }

        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            errorMessageContainer.textContent = 'Ukuran terlalu besar! Maksimal 5MB.';
            input.value = ''; // Batalkan pilihan file
            return false;
        }
        
        return true; // File valid
    }

    document.getElementById('samplingForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Selalu hentikan submit untuk validasi

        // Dapatkan tombol mana yang diklik untuk memicu submit
        const aksi = document.activeElement.value;

        // --- VALIDASI (tetap sama seperti sebelumnya) ---
        const errors = [];
        const errorContainer = document.getElementById('validation-error-container');
        errorContainer.innerHTML = '';

        // Validasi isian wajib
        const requiredFields = {
            'jenis_kegiatan': 'Jenis Kegiatan',
            'perusahaan': 'Nama Perusahaan',
            'alamat': 'Alamat Perusahaan',
            'tanggal': 'Tanggal Pelaksanaan',
            'pengambil_sampel': 'Pengambil Sampel'
        };

        for (const id in requiredFields) {
            const field = document.getElementById(id);
            if (!field.value.trim()) {
                errors.push(`<b>Informasi Kegiatan:</b> ${requiredFields[id]} wajib diisi.`);
            }
        }

        const pengambilSampel = document.getElementById('pengambil_sampel').value;
        const subKontrakNama = document.getElementById('sub_kontrak_nama').value;
        if (pengambilSampel === 'Sub Kontrak' && !subKontrakNama.trim()) {
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
                [`prosedur_${counter}`]: 'Prosedur Pengambilan Contoh',
                [`baku_mutu_${counter}`]: 'Baku Mutu',
                [`catatan_${counter}`]: 'Catatan Tambahan'
            };

            for (const id in requiredContohFields) {
                const field = document.getElementById(id);
                if (field && field.required && !field.value.trim()) {
                    errors.push(`${prefix} ${requiredContohFields[id]} wajib diisi.`);
                }
            }

            const checkedParams = item.querySelectorAll(`#parameter_container_${counter} input[type="checkbox"]:checked`);
            if (checkedParams.length === 0) {
                errors.push(`${prefix} Parameter Uji wajib dipilih minimal satu.`);
            }
        });
        
        // Hanya lakukan validasi ketat jika aksi adalah 'ajukan'
        if (aksi === 'ajukan') {
            const requiredFields = {
                'jenis_kegiatan': 'Jenis Kegiatan', 'perusahaan': 'Nama Perusahaan', 'alamat': 'Alamat Perusahaan', 'tanggal': 'Tanggal Pelaksanaan', 'pengambil_sampel': 'Pengambil Sampel'
            };
            for (const id in requiredFields) {
                if (!document.getElementById(id).value.trim()) {
                    errors.push(`<b>Informasi Kegiatan:</b> ${requiredFields[id]} wajib diisi.`);
                }
            }
            if (document.getElementById('pengambil_sampel').value === 'Sub Kontrak' && !document.getElementById('sub_kontrak_nama').value.trim()) {
                errors.push('<b>Informasi Kegiatan:</b> Nama Perusahaan Sub Kontrak wajib diisi.');
            }
            const contohItems = document.querySelectorAll('.contoh-item');
            if (contohItems.length === 0) {
                errors.push('<b>Data Contoh Uji:</b> Anda harus menambahkan minimal satu Contoh Uji.');
            }
            contohItems.forEach((item, index) => {
                const counter = item.id.split('_')[2];
                if (item.querySelectorAll(`#parameter_container_${counter} input[type="checkbox"]:checked`).length === 0) {
                    errors.push(`<b>Contoh Uji #${index + 1}:</b> Parameter Uji wajib dipilih minimal satu.`);
                }
            });
        }

        let allFilesAreValid = true;
        this.querySelectorAll('input[type="file"]').forEach(input => {
            if (!validateFile(input)) allFilesAreValid = false;
        });
        if (!allFilesAreValid) {
            errors.push("<b>Dokumen Pendukung:</b> Terdapat file yang tidak sesuai ketentuan.");
        }
        // --- AKHIR VALIDASI ---

        // Tampilkan error atau submit form
        if (errors.length > 0) {
            errorContainer.style.display = 'block';
            errorContainer.innerHTML = '<strong>Harap perbaiki kesalahan berikut:</strong><ul>' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
            window.scrollTo(0, 0);
        } else {
            errorContainer.style.display = 'none';
            document.getElementById('loadingOverlay').style.display = 'flex';

            // BUAT INPUT TERSEMBUNYI UNTUK MEMBAWA NILAI 'aksi'
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'aksi';
            hiddenInput.value = aksi;
            this.appendChild(hiddenInput);

            // Kirim form
            this.submit();
        }
    });

</script>

<?php
require_once '../templates/footer.php';
?>

</body>
</html>
