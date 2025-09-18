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

<div class="container-dashboard" style="padding: 2rem;">
    
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="back-to-dashboard">« Kembali ke Dashboard</a>

    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h2>Formulir Pengambilan Contoh</h2>
        </div>
        <div class="card-body">
            <form action="../actions/simpan_sampling.php" method="post">

                <div class="form-section" style="border:none; padding-bottom:1rem;">
                    <h3>Informasi Kegiatan Sampling</h3>
                    
                    <div class="form-group">
                        <label for="jenis_kegiatan">Jenis Kegiatan</label>
                        <select id="jenis_kegiatan" name="jenis_kegiatan" class="form-control" required>
                            <option value="">-- Pilih Jenis Kegiatan --</option>
                            <option value="Sampling">Sampling</option>
                            <option value="Pengujian">Pengujian</option>
                        </select>
                        <small id="kegiatan_detail_text" class="form-text text-muted"></small>
                    </div>

                    <div class="form-group">
                        <label for="perusahaan">Nama Perusahaan</label>
                        <input type="text" id="perusahaan" name="perusahaan" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat Perusahaan</label>
                        <textarea id="alamat" name="alamat" rows="3" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tanggal">Tanggal Pelaksanaan</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pengambil_sampel">Pengambil Sampel</label>
                        <select id="pengambil_sampel" name="pengambil_sampel" class="form-control" required>
                            <option value="BSPJI Medan">BSPJI Medan</option>
                            <option value="Sub Kontrak">Sub Kontrak</option>
                        </select>
                    </div>

                    <div class="form-group" id="sub_kontrak_wrapper" style="display:none;">
                        <label for="sub_kontrak_nama">Nama Perusahaan Sub Kontrak:</label>
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
                    <button type="submit" class="btn btn-success">
                        Simpan & Ajukan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('pengambil_sampel').addEventListener('change', function() {
        // ... (Fungsi ini tidak diubah)
        const subKontrakWrapper = document.getElementById('sub_kontrak_wrapper');
        if (this.value === 'Sub Kontrak') {
            subKontrakWrapper.style.display = 'block';
        } else {
            subKontrakWrapper.style.display = 'none';
        }
    });

    const dataSampling = {
        // ... (Objek dataSampling tidak diubah)
        "Air Limbah": {
            jenisContoh: ["Air Limbah Industri", "Air Limbah Domestik"],
            parameter: ["Total Suspensi Solid", "pH", "Amonia sebagai NH3-N", "COD sebagai O2", "Minyak dan Lemak", "Sulfat", "Cadmium (Cd)total", "Nikel (Ni) total", "Chrom (Cr) total", "Seng (Zn) total", "Mangan (Mn) terlarut", "Tembaga (Cu) total", "Timbal (Pb) total", "Besi (Fe) Terlarut", "Barium (Ba) total", "Cobalt (Co) total", "Padatan Terlarut Total (TDS Meter)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Krom Hexavalen (Cr-VI)", "Nitrit ( NO2 )", "Total Coliform"],
            prosedur: ["SNI 8990 : 2021 (Metode Pangambilan Contoh Air Limbah)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Air Permukaan": {
            jenisContoh: ["Air Sungai", "Air Danau", "Air Waduk", "Air Kolam", "Air Parit", "Air Irigasi"],
            parameter: ["Total Suspensi Solid", "pH", "Amonia sebagai NH3-N", "Sulfat", "Cadmium (Cd) terlarut", "Nikel (Ni) terlarut", "Chrom (Cr) Terlarut", "Seng (Zn) terlarut", "Mangan (Mn) terlarut", "Tembaga (Cu) terlarut", "Timbal (Pb) Terlarut", "Besi (Fe) terlarut", "Barium (Ba) terlarut", "Kobalt (Co) terlarut", "Padatan Terlarut Total (TDS)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Argentum (Ag) terlarut", "Krom Hexavalen (Cr-VI)", "Khlorida ( CI )", "Angka Permanganat (KMnO4)", "Fluorida", "Nitrit ( NO2 )", "Total Coliform", "Fecal Coliform"],
            prosedur: ["SNI 8995 : 2021 (Metode Pengambilan Contoh Uji Air Untuk Pengujian Fisika dan Kimia)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Air Tanah": {
            jenisContoh: ["Air Sumur Bor", "Air Sumur", "Sumur Artesis", "Sumber Mata Air"],
            parameter: ["Total Suspensi Solid", "pH", "Amonia sebagai NH3-N", "Sulfat", "Cadmium (Cd) terlarut", "Nikel (Ni) terlarut", "Chrom (Cr) Terlarut", "Seng (Zn) terlarut", "Mangan (Mn) terlarut", "Tembaga (Cu) terlarut", "Timbal (Pb) Terlarut", "Besi (Fe) terlarut", "Barium (Ba) terlarut", "Aluminium (Al) terlarut", "Kalium (K) terlarut", "Kobalt (Co) terlarut", "Padatan Terlarut Total (TDS)", "Temperatur", "OrthoPosfat", "Total Fosfor", "Padatan Terlarut Total (TDS) Grav", "Daya Hantar Listrik (DHL)", "Argentum (Ag) terlarut", "Krom Hexavalen (Cr-VI)", "Khlorida ( CI )", "Angka Permanganat (KMnO4)", "Fluorida", "Nitrit ( NO2 )", "Escherichia coli", "Total Coliform"],
            prosedur: ["SNI 8995 : 2021 (Metode Pengambilan Contoh Uji Air Untuk Pengujian Fisika dan Kimia)", "SNI 9063:2022 (Metode pengambilan contoh uji air dan air limbah untuk parameter mikrobiologi)"]
        },
        "Udara": {
            jenisContoh: ["Udara Emisi dari sumber bergerak", "Udara Emisi dari sumber tidak bergerak", "Udara Ambien"],
            parameter: { "Udara Emisi dari sumber bergerak": ["Opasitas"], "Udara Ambien": ["Sulfur dioksida (SO2)", "Nitrogen dioksida (NO2)", "Carbon monoksida (CO)", "TSP", "Timbal (Pb)", "Oksidan (O3)", "Amoniak (NH3)", "Hidrogen sulfida (H2S)"], "Udara Emisi dari sumber tidak bergerak": ["Sulfur dioksida (SO2)", "Nitrogen dioksida (NO2)", "Karbon monoksida (CO)", "Nitrogen Oksida (NOx)", "Oksigen (O2)", "Karbon dioksida (CO2)", "Opasitas", "Kecepatan Linier/Laju alir (velocity)", "Berat Molekul kering", "Kadar Uap Air", "Partikulat"] },
            prosedur: { "Udara Emisi dari sumber bergerak": ["M-LP-714-SMO (Smoke Meter Opacity) (Pengambilan contoh uji udara sumber emisi bergerak)"], "Udara Ambien": ["SNI 19-7119.6-2005 (Metode Pengambilan Contoh Udara Ambien)", "SNI 19-7119.9-2005 (Metode Pengambilan Contoh Udara Roadside)"], "Udara Emisi dari sumber tidak bergerak": ["SNI 7117.13-2009 (Pengambilan contoh uji udara emisi tidak bergerak)"] }
        },
        "Tingkat Kebisingan": { jenisContoh: null, parameter: ["Kebisingan"], prosedur: ["SNI 8427 : 2017 (Metode Pengambilan contoh uji kebisingan)"] },
        "Tingkat Getaran": { jenisContoh: null, parameter: ["Getaran"], prosedur: ["M-LP-711-GET (Vibration Meter) (Metode Pengambilan contoh uji Getaran)"] }
    };

    let contohCounter = 0;

    function tambahContoh() {
        const container = document.getElementById("contohContainer");
        const div = document.createElement("div");
        div.className = "contoh-item card card-body mb-3";
        div.id = `contoh_item_${contohCounter}`;
        const currentCounter = contohCounter;
        const namaContohOptions = Object.keys(dataSampling).map(key => `<option value="${key}">${key}</option>`).join('');

        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Contoh Uji #${currentCounter + 1}</h5>
                <button type="button" class="btn btn-danger btn-sm" onclick="hapusContoh(${currentCounter})">Hapus</button>
            </div>
            <input type="hidden" name="contoh[${currentCounter}][tipe_laporan]" id="tipe_laporan_${currentCounter}">
            
            <div class="form-group">
                <label for="nama_contoh_${currentCounter}">Nama Contoh</label>
                <select class="form-control" name="contoh[${currentCounter}][nama_contoh]" id="nama_contoh_${currentCounter}" onchange="updateDynamicFields(${currentCounter})" required>
                    <option value="">-- Pilih Bahan --</option>${namaContohOptions}
                </select>
            </div>
            
            <div class="form-group">
                <label for="jenis_contoh_${currentCounter}">Jenis Contoh</label>
                <select class="form-control" name="contoh[${currentCounter}][jenis_contoh]" id="jenis_contoh_${currentCounter}" onchange="updateParameters(${currentCounter})" disabled>
                    <option value="">-- Pilih Nama Contoh terlebih dahulu --</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="merek_${currentCounter}">Etiket / Merek</label>
                    <input type="text" id="merek_${currentCounter}" class="form-control" name="contoh[${currentCounter}][merek]">
                </div>
                <div class="form-group col-md-6">
                    <label for="kode_${currentCounter}">Kode</label>
                    <input type="text" id="kode_${currentCounter}" class="form-control" name="contoh[${currentCounter}][kode]">
                </div>
            </div>

            <div class="form-group">
                <label for="prosedur_${currentCounter}">Prosedur Pengambilan Contoh</label>
                <select class="form-control" name="contoh[${currentCounter}][prosedur]" id="prosedur_${currentCounter}" required disabled>
                     <option value="">-- Pilih Nama Contoh terlebih dahulu --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Parameter Uji</label>
                <div class="parameter-grid" id="parameter_container_${currentCounter}"><small class="text-muted">Pilih Nama dan Jenis Contoh terlebih dahulu.</small></div>
            </div>

            <div class="form-group">
                <label for="baku_mutu_${currentCounter}">Baku Mutu</label>
                <select class="form-control" name="contoh[${currentCounter}][baku_mutu]" id="baku_mutu_${currentCounter}">
                    <option value="">-- Pilih Baku Mutu --</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran I">PP RI No. 22 Tahun 2021, Lampiran I</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran III">PP RI No. 22 Tahun 2021, Lampiran III</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran IV">PP RI No. 22 Tahun 2021, Lampiran IV</option>
                    <option value="PP RI No. 22 Tahun 2021, Lampiran VII">PP RI No. 22 Tahun 2021, Lampiran VII</option>
                    <option value="PERMENLH No. 05 Tahun 2014 Lampiran III">PERMENLH No. 05 Tahun 2014 Lampiran III</option>
                    <option value="PERMEN LHK No. 14 Tahun 2020">Permen LHK No. 14 Tahun 2020</option>
                    <option value="Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996">Keputusan Menteri Lingkungan Hidup No. 48 Tahun 1996</option>
                    <option value="Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996">Keputusan Menteri Lingkungan Hidup No. 49 Tahun 1996</option>                
                    <option value="Lainnya">Lainnya...</option>
                </select>
                <input type="text" class="form-control mt-2" name="contoh[${currentCounter}][baku_mutu_lainnya]" id="baku_mutu_lainnya_${currentCounter}" style="display:none;" placeholder="Masukkan baku mutu lainnya">
            </div>

            <div class="form-group">
                <label for="catatan_${currentCounter}">Catatan Tambahan</label>
                <textarea id="catatan_${currentCounter}" class="form-control" name="contoh[${currentCounter}][catatan]" rows="2"></textarea>
            </div>
        `;
        container.appendChild(div);
        document.getElementById(`baku_mutu_${currentCounter}`).addEventListener('change', function() {
            // ... (Fungsi ini tidak diubah)
            const lainnyaInput = document.getElementById(`baku_mutu_lainnya_${this.id.split('_')[2]}`);
            lainnyaInput.style.display = (this.value === 'Lainnya') ? 'block' : 'none';
            if (this.value !== 'Lainnya') lainnyaInput.value = '';
        });
        contohCounter++;
    }

    // Semua fungsi JavaScript di bawah ini tidak diubah
    function hapusContoh(id) {
        // ...
        document.getElementById(`contoh_item_${id}`).remove();
    }

    function updateDynamicFields(id) {
        // ...
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const jenisContohSelect = document.getElementById(`jenis_contoh_${id}`);
        const tipeLaporanInput = document.getElementById(`tipe_laporan_${id}`);

        tipeLaporanInput.value = (selectedNamaContoh.includes("Udara") || selectedNamaContoh.includes("Kebisingan") || selectedNamaContoh.includes("Getaran")) ? 'udara' : 'air';

        const jenisKegiatanSelect = document.getElementById('jenis_kegiatan');
        const kegiatanDetailText = document.getElementById('kegiatan_detail_text');
        if (jenisKegiatanSelect.value === 'Pengujian' && selectedNamaContoh) {
            kegiatanDetailText.textContent = `Hasil: Pengujian ${selectedNamaContoh}`;
        } else {
            kegiatanDetailText.textContent = '';
        }

        if (data && data.jenisContoh) {
            jenisContohSelect.disabled = false;
            jenisContohSelect.innerHTML = '<option value="">-- Pilih Jenis --</option>' + data.jenisContoh.map(jc => `<option value="${jc}">${jc}</option>`).join('');
        } else {
            jenisContohSelect.disabled = true;
            jenisContohSelect.innerHTML = '<option value="">-- Tidak ada jenis contoh --</option>';
        }
        updateParameters(id);
        updateProsedur(id);
    }

    function updateParameters(id) {
        // Fungsi ini diubah untuk menangani parameter yang berbasis objek
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const parameterContainer = document.getElementById(`parameter_container_${id}`);
        let parameters = [];

        if (data) {
            if (Array.isArray(data.parameter)) {
                parameters = data.parameter;
            } else if (typeof data.parameter === 'object' && selectedJenisContoh && data.parameter[selectedJenisContoh]) {
                parameters = data.parameter[selectedJenisContoh];
            } else if (typeof data.parameter === 'object' && !data.jenisContoh) {
                 parameters = data.parameter;
            }
        }
        if (parameters.length > 0) {
            parameterContainer.innerHTML = parameters.map(p => `<label class="form-check"><input class="form-check-input" type="checkbox" name="contoh[${id}][parameter][]" value="${p}"><span class="form-check-label">${p}</span></label>`).join('');
        } else {
            parameterContainer.innerHTML = '<small class="text-muted">Pilih Nama dan Jenis Contoh terlebih dahulu.</small>';
        }
    }
    
    function updateProsedur(id) {
        // Fungsi ini untuk memperbarui prosedur berdasarkan nama dan jenis contoh
        const selectedNamaContoh = document.getElementById(`nama_contoh_${id}`).value;
        const selectedJenisContoh = document.getElementById(`jenis_contoh_${id}`).value;
        const data = dataSampling[selectedNamaContoh];
        const prosedurSelect = document.getElementById(`prosedur_${id}`);
        let prosedur = [];

        if (data) {
            prosedurSelect.disabled = false;
            if (Array.isArray(data.prosedur)) {
                prosedur = data.prosedur;
            } else if (typeof data.prosedur === 'object' && selectedJenisContoh && data.prosedur[selectedJenisContoh]) {
                prosedur = data.prosedur[selectedJenisContoh];
            } else  if (typeof data.prosedur === 'object' && !data.jenisContoh) {
                 prosedur = data.prosedur;
            }
        }
        
        if (prosedur.length > 0) {
            prosedurSelect.innerHTML = '<option value="">-- Pilih Prosedur --</option>' + prosedur.map(p => `<option value="${p}">${p}</option>`).join('');
        } else {
             prosedurSelect.disabled = true;
            prosedurSelect.innerHTML = '<option value="">-- Pilihan tidak tersedia --</option>';
        }
    }
</script>

</body>
</html>